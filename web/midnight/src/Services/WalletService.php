<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\Contracts\WalletGateway;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\MidnightException;
use VersionTwo\Midnight\Exceptions\NetworkException;

/**
 * Implementation of the WalletGateway interface.
 *
 * This service provides wallet operations including retrieving the wallet
 * address, checking balances, and transferring assets. All operations are
 * proxied through the Midnight bridge service.
 *
 * Features:
 * - Session-based caching for wallet address
 * - Balance caching (short TTL)
 * - Transfer validation
 * - Comprehensive logging
 * - Error handling with detailed messages
 */
class WalletService implements WalletGateway
{
    /**
     * Cache key for wallet address.
     */
    private const CACHE_KEY_ADDRESS = 'midnight:wallet:address';

    /**
     * Cache key prefix for wallet balance.
     */
    private const CACHE_KEY_BALANCE_PREFIX = 'midnight:wallet:balance:';

    /**
     * Cache tag for wallet operations.
     */
    private const CACHE_TAG_WALLET = 'midnight:wallet';

    /**
     * Create a new WalletService instance.
     *
     * @param MidnightClient $client The Midnight client for bridge communication
     * @param CacheRepository|null $cache The cache repository (defaults to Laravel cache)
     */
    public function __construct(
        private readonly MidnightClient $client,
        private readonly ?CacheRepository $cache = null,
    ) {
    }

    /**
     * Get the cache repository instance.
     *
     * @return CacheRepository
     */
    private function getCache(): CacheRepository
    {
        return $this->cache ?? Cache::store(config('midnight.cache.store'));
    }

    /**
     * {@inheritDoc}
     */
    public function getAddress(): string
    {
        Log::debug('WalletService: Fetching wallet address');

        try {
            // Check session cache first (cached for entire session)
            $cached = $this->getCache()
                ->tags([self::CACHE_TAG_WALLET])
                ->get(self::CACHE_KEY_ADDRESS);

            if ($cached !== null) {
                Log::debug('WalletService: Wallet address retrieved from cache', [
                    'address' => $this->maskAddress($cached),
                ]);
                return $cached;
            }

            // Fetch from bridge via a read-only call or dedicated endpoint
            $call = ContractCall::readOnly(
                contractAddress: '__wallet__',
                entrypoint: 'getAddress'
            );

            $result = $this->client->callReadOnly($call);
            $address = $result->asString();

            if (empty($address)) {
                throw MidnightException::withContext(
                    'Failed to retrieve wallet address: empty response',
                    ['result' => $result->toArray()]
                );
            }

            // Cache for the session (no expiration, cleared on service restart)
            $this->getCache()
                ->tags([self::CACHE_TAG_WALLET])
                ->put(self::CACHE_KEY_ADDRESS, $address, 86400); // 24 hours

            Log::info('WalletService: Wallet address fetched', [
                'address' => $this->maskAddress($address),
            ]);

            return $address;
        } catch (NetworkException $e) {
            Log::error('WalletService: Failed to fetch wallet address', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WalletService: Unexpected error fetching wallet address', [
                'error' => $e->getMessage(),
            ]);
            throw MidnightException::fromPrevious(
                'Failed to retrieve wallet address',
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(): string
    {
        Log::debug('WalletService: Fetching wallet balance');

        $cacheKey = self::CACHE_KEY_BALANCE_PREFIX . 'native';

        try {
            // Check cache with short TTL
            $cached = $this->getCache()
                ->tags([self::CACHE_TAG_WALLET])
                ->get($cacheKey);

            if ($cached !== null) {
                Log::debug('WalletService: Balance retrieved from cache', [
                    'balance' => $cached,
                ]);
                return $cached;
            }

            // Fetch from bridge
            $call = ContractCall::readOnly(
                contractAddress: '__wallet__',
                entrypoint: 'getBalance'
            );

            $result = $this->client->callReadOnly($call);
            $balance = $result->asString();

            // Cache for 10 seconds
            $this->getCache()
                ->tags([self::CACHE_TAG_WALLET])
                ->put($cacheKey, $balance, 10);

            Log::info('WalletService: Wallet balance fetched', [
                'balance' => $balance,
            ]);

            return $balance;
        } catch (NetworkException $e) {
            Log::error('WalletService: Failed to fetch wallet balance', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WalletService: Unexpected error fetching wallet balance', [
                'error' => $e->getMessage(),
            ]);
            throw MidnightException::fromPrevious(
                'Failed to retrieve wallet balance',
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function transfer(string $toAddress, string $amount, ?string $asset = null): TxHash
    {
        Log::debug('WalletService: Initiating transfer', [
            'to' => $this->maskAddress($toAddress),
            'amount' => $amount,
            'asset' => $asset ?? 'native',
        ]);

        // Validate inputs
        $this->validateAddress($toAddress);
        $this->validateAmount($amount);

        try {
            $call = new ContractCall(
                contractAddress: '__wallet__',
                entrypoint: 'transfer',
                publicArgs: [
                    'to_address' => $toAddress,
                    'amount' => $amount,
                    'asset' => $asset,
                ],
                readOnly: false,
            );

            $txHash = $this->client->submitTransaction($call);

            // Invalidate balance cache since it will change
            $this->invalidateBalanceCache();

            Log::info('WalletService: Transfer submitted successfully', [
                'tx_hash' => $txHash->value,
                'to' => $this->maskAddress($toAddress),
                'amount' => $amount,
            ]);

            return $txHash;
        } catch (NetworkException $e) {
            Log::error('WalletService: Transfer failed', [
                'error' => $e->getMessage(),
                'to' => $this->maskAddress($toAddress),
                'amount' => $amount,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WalletService: Unexpected error during transfer', [
                'error' => $e->getMessage(),
                'to' => $this->maskAddress($toAddress),
            ]);
            throw MidnightException::fromPrevious(
                "Failed to transfer {$amount} to {$toAddress}",
                $e
            );
        }
    }

    /**
     * Invalidate the balance cache.
     *
     * This is called automatically when a transfer is performed.
     *
     * @return void
     */
    private function invalidateBalanceCache(): void
    {
        Log::debug('WalletService: Invalidating balance cache');

        // Flush all wallet-related cache entries
        $this->getCache()->tags([self::CACHE_TAG_WALLET])->flush();
    }

    /**
     * Validate a Midnight address.
     *
     * @param string $address The address to validate
     * @return void
     * @throws InvalidArgumentException If the address is invalid
     */
    private function validateAddress(string $address): void
    {
        if (empty($address)) {
            throw new InvalidArgumentException('Address cannot be empty');
        }

        // Additional validation could be added here for Midnight-specific address formats
        // For example, checking length, prefix, checksum, etc.
        if (strlen($address) < 10) {
            throw new InvalidArgumentException(
                "Address is too short to be valid: {$address}"
            );
        }
    }

    /**
     * Validate a transfer amount.
     *
     * @param string $amount The amount to validate
     * @return void
     * @throws InvalidArgumentException If the amount is invalid
     */
    private function validateAmount(string $amount): void
    {
        if (empty($amount)) {
            throw new InvalidArgumentException('Amount cannot be empty');
        }

        // Validate that the amount is a valid numeric string
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException(
                "Amount must be a valid numeric string: {$amount}"
            );
        }

        // Validate that the amount is positive
        if (bccomp($amount, '0', 18) <= 0) {
            throw new InvalidArgumentException(
                "Amount must be greater than zero: {$amount}"
            );
        }
    }

    /**
     * Mask an address for logging (shows first 6 and last 4 characters).
     *
     * @param string $address The address to mask
     * @return string The masked address
     */
    private function maskAddress(string $address): string
    {
        if (strlen($address) <= 10) {
            return $address;
        }

        return substr($address, 0, 6) . '...' . substr($address, -4);
    }

    /**
     * Clear the wallet address cache.
     *
     * This method is useful when switching wallets or reloading wallet configuration.
     *
     * @return void
     */
    public function clearAddressCache(): void
    {
        Log::debug('WalletService: Clearing address cache');

        $this->getCache()
            ->tags([self::CACHE_TAG_WALLET])
            ->forget(self::CACHE_KEY_ADDRESS);

        Log::info('WalletService: Wallet address cache cleared');
    }
}
