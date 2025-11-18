<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use VersionTwo\Midnight\Contracts\BridgeHttpClient;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\NetworkException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;

/**
 * Implementation of the MidnightClient interface.
 *
 * This service provides low-level client functionality for communicating with
 * the Midnight bridge service. It handles network metadata retrieval, transaction
 * submission, status checking, read-only calls, and proof generation.
 *
 * Features:
 * - Caching for network metadata (1 hour TTL)
 * - Comprehensive logging for all operations
 * - Tag-based cache invalidation
 * - Error handling with specific exceptions
 */
class MidnightService implements MidnightClient
{
    /**
     * Cache tag for network metadata.
     */
    private const CACHE_TAG_NETWORK = 'midnight:network';

    /**
     * Cache key for network metadata.
     */
    private const CACHE_KEY_METADATA = 'midnight:network:metadata';

    /**
     * Create a new MidnightService instance.
     *
     * @param BridgeHttpClient $httpClient The HTTP client for bridge communication
     * @param CacheRepository|null $cache The cache repository (defaults to Laravel cache)
     */
    public function __construct(
        private readonly BridgeHttpClient $httpClient,
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
    public function getNetworkMetadata(): NetworkMetadata
    {
        Log::debug('MidnightService: Fetching network metadata');

        try {
            // Check cache first
            $cached = $this->getCache()->tags([self::CACHE_TAG_NETWORK])
                ->get(self::CACHE_KEY_METADATA);

            if ($cached !== null) {
                Log::debug('MidnightService: Network metadata retrieved from cache');
                return is_array($cached) ? NetworkMetadata::fromArray($cached) : $cached;
            }

            // Fetch from bridge
            $response = $this->httpClient->get('/api/network/metadata');

            if (!isset($response['data'])) {
                throw NetworkException::invalidBridgeResponse(
                    200,
                    '/api/network/metadata',
                    json_encode($response)
                );
            }

            $metadata = NetworkMetadata::fromArray($response['data']);

            // Cache for 1 hour
            $ttl = config('midnight.cache.ttl.network_metadata', 3600);
            $this->getCache()->tags([self::CACHE_TAG_NETWORK])
                ->put(self::CACHE_KEY_METADATA, $metadata->toArray(), $ttl);

            Log::info('MidnightService: Network metadata fetched', [
                'chain_id' => $metadata->chainId,
                'network' => $metadata->name,
            ]);

            return $metadata;
        } catch (NetworkException $e) {
            Log::error('MidnightService: Failed to fetch network metadata', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MidnightService: Unexpected error fetching network metadata', [
                'error' => $e->getMessage(),
            ]);
            throw NetworkException::fromPrevious(
                'Failed to retrieve network metadata',
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function submitTransaction(ContractCall $call): TxHash
    {
        Log::debug('MidnightService: Submitting transaction', [
            'contract' => $call->contractAddress,
            'entrypoint' => $call->entrypoint,
            'has_private_args' => $call->hasPrivateArgs(),
        ]);

        try {
            $response = $this->httpClient->post('/api/transactions/submit', [
                'contract_address' => $call->contractAddress,
                'entrypoint' => $call->entrypoint,
                'public_args' => $call->publicArgs,
                'private_args' => $call->privateArgs,
                'metadata' => $call->metadata,
            ]);

            if (!isset($response['tx_hash']) && !isset($response['txHash'])) {
                throw NetworkException::invalidBridgeResponse(
                    200,
                    '/api/transactions/submit',
                    json_encode($response)
                );
            }

            $txHash = new TxHash($response['tx_hash'] ?? $response['txHash']);

            Log::info('MidnightService: Transaction submitted successfully', [
                'tx_hash' => $txHash->value,
                'contract' => $call->contractAddress,
                'entrypoint' => $call->entrypoint,
            ]);

            return $txHash;
        } catch (NetworkException $e) {
            Log::error('MidnightService: Failed to submit transaction', [
                'error' => $e->getMessage(),
                'contract' => $call->contractAddress,
                'entrypoint' => $call->entrypoint,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MidnightService: Unexpected error submitting transaction', [
                'error' => $e->getMessage(),
                'contract' => $call->contractAddress,
            ]);
            throw ContractException::fromPrevious(
                "Failed to submit transaction to {$call->contractAddress}::{$call->entrypoint}",
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionStatus(string $txHash): array
    {
        Log::debug('MidnightService: Fetching transaction status', [
            'tx_hash' => $txHash,
        ]);

        try {
            $response = $this->httpClient->get("/api/transactions/{$txHash}/status");

            if (!isset($response['status'])) {
                throw NetworkException::invalidBridgeResponse(
                    200,
                    "/api/transactions/{$txHash}/status",
                    json_encode($response)
                );
            }

            Log::debug('MidnightService: Transaction status retrieved', [
                'tx_hash' => $txHash,
                'status' => $response['status'],
            ]);

            return $response;
        } catch (NetworkException $e) {
            Log::error('MidnightService: Failed to fetch transaction status', [
                'error' => $e->getMessage(),
                'tx_hash' => $txHash,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MidnightService: Unexpected error fetching transaction status', [
                'error' => $e->getMessage(),
                'tx_hash' => $txHash,
            ]);
            throw NetworkException::fromPrevious(
                "Failed to get status for transaction {$txHash}",
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function callReadOnly(ContractCall $call): ContractCallResult
    {
        Log::debug('MidnightService: Executing read-only call', [
            'contract' => $call->contractAddress,
            'entrypoint' => $call->entrypoint,
        ]);

        try {
            $response = $this->httpClient->post('/api/contracts/call/readonly', [
                'contract_address' => $call->contractAddress,
                'entrypoint' => $call->entrypoint,
                'public_args' => $call->publicArgs,
                'metadata' => $call->metadata,
            ]);

            $result = ContractCallResult::fromArray($response);

            if (!$result->success) {
                Log::warning('MidnightService: Read-only call returned failure', [
                    'contract' => $call->contractAddress,
                    'entrypoint' => $call->entrypoint,
                    'error' => $result->error,
                ]);
            } else {
                Log::debug('MidnightService: Read-only call successful', [
                    'contract' => $call->contractAddress,
                    'entrypoint' => $call->entrypoint,
                ]);
            }

            return $result;
        } catch (NetworkException $e) {
            Log::error('MidnightService: Failed to execute read-only call', [
                'error' => $e->getMessage(),
                'contract' => $call->contractAddress,
                'entrypoint' => $call->entrypoint,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MidnightService: Unexpected error executing read-only call', [
                'error' => $e->getMessage(),
                'contract' => $call->contractAddress,
            ]);
            throw ContractException::fromPrevious(
                "Failed to execute read-only call to {$call->contractAddress}::{$call->entrypoint}",
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function generateProof(ProofRequest $request): ProofResponse
    {
        Log::debug('MidnightService: Generating proof', [
            'contract' => $request->contractName,
            'entrypoint' => $request->entrypoint,
        ]);

        try {
            $response = $this->httpClient->post('/api/proofs/generate', [
                'contract_name' => $request->contractName,
                'entrypoint' => $request->entrypoint,
                'public_inputs' => $request->publicInputs,
                'private_inputs' => $request->privateInputs,
                'circuit_path' => $request->circuitPath,
                'metadata' => $request->metadata,
            ]);

            $proof = ProofResponse::fromArray($response);

            Log::info('MidnightService: Proof generated successfully', [
                'contract' => $request->contractName,
                'entrypoint' => $request->entrypoint,
                'generation_time' => $proof->generationTime,
            ]);

            return $proof;
        } catch (NetworkException $e) {
            Log::error('MidnightService: Failed to generate proof', [
                'error' => $e->getMessage(),
                'contract' => $request->contractName,
                'entrypoint' => $request->entrypoint,
            ]);
            throw ProofFailedException::fromPrevious(
                "Proof generation failed for {$request->contractName}::{$request->entrypoint}",
                $e
            );
        } catch (\Throwable $e) {
            Log::error('MidnightService: Unexpected error generating proof', [
                'error' => $e->getMessage(),
                'contract' => $request->contractName,
            ]);
            throw ProofFailedException::generationFailed(
                $request->contractName,
                $request->entrypoint,
                $e->getMessage()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function healthCheck(): bool
    {
        Log::debug('MidnightService: Performing health check');

        try {
            $healthy = $this->httpClient->healthCheck();

            Log::debug('MidnightService: Health check completed', [
                'healthy' => $healthy,
            ]);

            return $healthy;
        } catch (\Throwable $e) {
            Log::warning('MidnightService: Health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clear the network metadata cache.
     *
     * This method is useful when you need to force a refresh of the
     * network metadata, for example when switching networks.
     *
     * @return void
     */
    public function clearNetworkCache(): void
    {
        Log::debug('MidnightService: Clearing network metadata cache');

        $this->getCache()->tags([self::CACHE_TAG_NETWORK])->flush();

        Log::info('MidnightService: Network metadata cache cleared');
    }
}
