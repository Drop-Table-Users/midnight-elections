<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\NetworkException;

/**
 * Implementation of the ContractGateway interface.
 *
 * This service provides a high-level API for deploying contracts, joining
 * contracts, calling contract methods, and reading contract state. It abstracts
 * away the low-level details and provides developer-friendly methods.
 *
 * Features:
 * - Caching for read operations (10 second TTL)
 * - Tag-based cache per contract address
 * - Automatic cache invalidation on writes
 * - Comprehensive logging
 * - Input validation
 */
class ContractService implements ContractGateway
{
    /**
     * Cache tag prefix for contract operations.
     */
    private const CACHE_TAG_PREFIX = 'midnight:contract:';

    /**
     * Create a new ContractService instance.
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
     * Get the cache tag for a specific contract.
     *
     * @param string $contractAddress The contract address
     * @return string
     */
    private function getContractCacheTag(string $contractAddress): string
    {
        return self::CACHE_TAG_PREFIX . $contractAddress;
    }

    /**
     * {@inheritDoc}
     */
    public function deploy(string $compiledContractPath, array $initArgs = []): string
    {
        Log::debug('ContractService: Deploying contract', [
            'path' => $compiledContractPath,
            'init_args' => array_keys($initArgs),
        ]);

        // Validate contract path
        if (!file_exists($compiledContractPath)) {
            throw new InvalidArgumentException(
                "Compiled contract file not found: {$compiledContractPath}"
            );
        }

        if (!is_readable($compiledContractPath)) {
            throw new InvalidArgumentException(
                "Compiled contract file is not readable: {$compiledContractPath}"
            );
        }

        try {
            // Read the compiled contract
            $contractBytes = file_get_contents($compiledContractPath);
            if ($contractBytes === false) {
                throw new InvalidArgumentException(
                    "Failed to read compiled contract: {$compiledContractPath}"
                );
            }

            // Create a deployment call (this is a simplified approach - actual implementation
            // may need to use a different bridge endpoint specifically for deployment)
            $call = new ContractCall(
                contractAddress: '0x0', // Placeholder for deployment
                entrypoint: '__deploy__',
                publicArgs: array_merge([
                    'contract_bytes' => base64_encode($contractBytes),
                ], $initArgs),
                readOnly: false,
                metadata: [
                    'contract_path' => $compiledContractPath,
                ]
            );

            $txHash = $this->client->submitTransaction($call);

            // Note: In a real implementation, we would need to wait for the transaction
            // to be confirmed and extract the deployed contract address from the receipt.
            // For now, we'll return a placeholder that should be replaced with actual logic.
            Log::info('ContractService: Contract deployment submitted', [
                'tx_hash' => $txHash->value,
                'path' => $compiledContractPath,
            ]);

            // Placeholder - this should be replaced with actual contract address extraction
            // from the transaction receipt after confirmation
            $contractAddress = 'pending:' . $txHash->value;

            return $contractAddress;
        } catch (NetworkException | ContractException $e) {
            Log::error('ContractService: Contract deployment failed', [
                'error' => $e->getMessage(),
                'path' => $compiledContractPath,
            ]);
            throw ContractException::deploymentFailed(
                $e->getMessage(),
                $compiledContractPath
            );
        } catch (\Throwable $e) {
            Log::error('ContractService: Unexpected error during deployment', [
                'error' => $e->getMessage(),
                'path' => $compiledContractPath,
            ]);
            throw ContractException::deploymentFailed(
                $e->getMessage(),
                $compiledContractPath
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function join(string $contractAddress, array $args = []): ContractCallResult
    {
        Log::debug('ContractService: Joining contract', [
            'contract' => $contractAddress,
            'args' => array_keys($args),
        ]);

        $this->validateContractAddress($contractAddress);

        try {
            $call = new ContractCall(
                contractAddress: $contractAddress,
                entrypoint: 'join',
                publicArgs: $args,
                readOnly: false,
            );

            $txHash = $this->client->submitTransaction($call);

            Log::info('ContractService: Contract join submitted', [
                'contract' => $contractAddress,
                'tx_hash' => $txHash->value,
            ]);

            // Return a result indicating success
            // In a real implementation, you might wait for confirmation
            return ContractCallResult::success(
                ['tx_hash' => $txHash->value],
                ['status' => 'submitted']
            );
        } catch (NetworkException | ContractException $e) {
            Log::error('ContractService: Failed to join contract', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
            ]);
            throw ContractException::joinFailed($contractAddress, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('ContractService: Unexpected error joining contract', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
            ]);
            throw ContractException::joinFailed($contractAddress, $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function call(
        string $contractAddress,
        string $entrypoint,
        array $publicArgs = [],
        array $privateArgs = []
    ): TxHash {
        Log::debug('ContractService: Calling contract method', [
            'contract' => $contractAddress,
            'entrypoint' => $entrypoint,
            'has_private_args' => !empty($privateArgs),
        ]);

        $this->validateContractAddress($contractAddress);
        $this->validateEntrypoint($entrypoint);

        try {
            $call = ContractCall::write(
                contractAddress: $contractAddress,
                entrypoint: $entrypoint,
                publicArgs: $publicArgs,
                privateArgs: $privateArgs
            );

            $txHash = $this->client->submitTransaction($call);

            // Invalidate cache for this contract since state may have changed
            $this->invalidateContractCache($contractAddress);

            Log::info('ContractService: Contract call submitted', [
                'contract' => $contractAddress,
                'entrypoint' => $entrypoint,
                'tx_hash' => $txHash->value,
            ]);

            return $txHash;
        } catch (NetworkException | ContractException $e) {
            Log::error('ContractService: Contract call failed', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
                'entrypoint' => $entrypoint,
            ]);
            throw ContractException::callFailed(
                $contractAddress,
                $entrypoint,
                $e->getMessage()
            );
        } catch (\Throwable $e) {
            Log::error('ContractService: Unexpected error calling contract', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
                'entrypoint' => $entrypoint,
            ]);
            throw ContractException::callFailed(
                $contractAddress,
                $entrypoint,
                $e->getMessage()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function read(
        string $contractAddress,
        string $selector,
        array $args = []
    ): ContractCallResult {
        Log::debug('ContractService: Reading contract state', [
            'contract' => $contractAddress,
            'selector' => $selector,
        ]);

        $this->validateContractAddress($contractAddress);
        $this->validateEntrypoint($selector);

        // Generate cache key
        $cacheKey = $this->getReadCacheKey($contractAddress, $selector, $args);

        try {
            // Check cache first
            $cached = $this->getCache()
                ->tags([$this->getContractCacheTag($contractAddress)])
                ->get($cacheKey);

            if ($cached !== null) {
                Log::debug('ContractService: Read result retrieved from cache', [
                    'contract' => $contractAddress,
                    'selector' => $selector,
                ]);
                return is_array($cached) ? ContractCallResult::fromArray($cached) : $cached;
            }

            // Execute read-only call
            $call = ContractCall::readOnly(
                contractAddress: $contractAddress,
                entrypoint: $selector,
                args: $args
            );

            $result = $this->client->callReadOnly($call);

            // Cache the result for 10 seconds
            $ttl = config('midnight.cache.ttl.contract_state', 10);
            $this->getCache()
                ->tags([$this->getContractCacheTag($contractAddress)])
                ->put($cacheKey, $result->toArray(), $ttl);

            Log::debug('ContractService: Contract state read successfully', [
                'contract' => $contractAddress,
                'selector' => $selector,
            ]);

            return $result;
        } catch (NetworkException | ContractException $e) {
            Log::error('ContractService: Failed to read contract state', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
                'selector' => $selector,
            ]);
            throw ContractException::stateReadFailed(
                $contractAddress,
                $selector,
                $e->getMessage()
            );
        } catch (\Throwable $e) {
            Log::error('ContractService: Unexpected error reading contract state', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
                'selector' => $selector,
            ]);
            throw ContractException::stateReadFailed(
                $contractAddress,
                $selector,
                $e->getMessage()
            );
        }
    }

    /**
     * Invalidate the cache for a specific contract.
     *
     * This is called automatically when a state-changing operation is performed.
     *
     * @param string $contractAddress The contract address
     * @return void
     */
    private function invalidateContractCache(string $contractAddress): void
    {
        Log::debug('ContractService: Invalidating cache', [
            'contract' => $contractAddress,
        ]);

        $this->getCache()
            ->tags([$this->getContractCacheTag($contractAddress)])
            ->flush();
    }

    /**
     * Generate a cache key for a read operation.
     *
     * @param string $contractAddress The contract address
     * @param string $selector The state selector
     * @param array<string, mixed> $args The call arguments
     * @return string
     */
    private function getReadCacheKey(string $contractAddress, string $selector, array $args): string
    {
        $argsHash = md5(json_encode($args));
        return "midnight:read:{$contractAddress}:{$selector}:{$argsHash}";
    }

    /**
     * Validate a contract address.
     *
     * @param string $address The contract address to validate
     * @return void
     * @throws InvalidArgumentException If the address is invalid
     */
    private function validateContractAddress(string $address): void
    {
        if (empty($address)) {
            throw new InvalidArgumentException('Contract address cannot be empty');
        }

        // Additional validation could be added here for Midnight-specific address formats
    }

    /**
     * Validate an entrypoint/selector name.
     *
     * @param string $entrypoint The entrypoint to validate
     * @return void
     * @throws InvalidArgumentException If the entrypoint is invalid
     */
    private function validateEntrypoint(string $entrypoint): void
    {
        if (empty($entrypoint)) {
            throw new InvalidArgumentException('Entrypoint cannot be empty');
        }
    }
}
