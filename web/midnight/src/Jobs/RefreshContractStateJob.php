<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Exceptions\MidnightException;

/**
 * Queue job for refreshing cached contract state from the Midnight network.
 *
 * This job handles asynchronous contract state refresh operations by clearing
 * cached data and reading fresh state from the network. It supports refreshing
 * specific state selectors or all state for a contract.
 *
 * Features:
 * - Selective or full contract state refresh
 * - Cache clearing for stale data
 * - Comprehensive error handling and logging
 * - Configurable queue connection and timeout
 *
 * Usage:
 * ```php
 * // Refresh all state for a contract
 * RefreshContractStateJob::dispatch('0x123...');
 *
 * // Refresh specific selectors only
 * RefreshContractStateJob::dispatch('0x123...', ['balance', 'totalSupply']);
 * ```
 */
class RefreshContractStateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout;

    /**
     * Create a new job instance.
     *
     * @param string $contractAddress The address of the contract to refresh state for
     * @param array<string>|null $selectors Optional array of specific selectors to refresh.
     *                                     If null, all cached state will be cleared.
     */
    public function __construct(
        public readonly string $contractAddress,
        public readonly ?array $selectors = null,
    ) {
        // Configure queue from config
        $this->onConnection(config('midnight.queue.connection', 'redis'));
        $this->onQueue(config('midnight.queue.queue', 'midnight'));

        // Configure retry and timeout behavior from config
        $this->tries = config('midnight.retry.times', 3);
        $this->timeout = config('midnight.bridge.timeout', 10) * 2; // Double the bridge timeout
    }

    /**
     * Execute the job.
     *
     * Clears cached contract state and optionally reads fresh data from the network.
     * If specific selectors are provided, only those will be cleared and refreshed.
     * Otherwise, all state for the contract will be cleared.
     *
     * @param ContractGateway $gateway The contract gateway for reading state
     * @return void
     * @throws MidnightException If state refresh fails
     */
    public function handle(ContractGateway $gateway): void
    {
        try {
            $this->logRefreshStart();

            // Clear cached state
            $this->clearCachedState();

            // If specific selectors are provided, refresh them
            if ($this->selectors !== null) {
                $this->refreshSelectors($gateway);
            }

            $this->logRefreshSuccess();
        } catch (Throwable $exception) {
            $this->logRefreshFailure($exception);
            throw $exception;
        }
    }

    /**
     * Clear cached state for the contract.
     *
     * If specific selectors are provided, only those cache entries are cleared.
     * Otherwise, all state for the contract is cleared using a wildcard pattern.
     *
     * @return void
     */
    private function clearCachedState(): void
    {
        $cacheStore = Cache::store(config('midnight.cache.store', 'redis'));
        $prefix = config('midnight.cache.prefix', 'midnight');

        if ($this->selectors === null) {
            // Clear all state for this contract using wildcard pattern
            $pattern = "{$prefix}:contract:{$this->contractAddress}:*";

            // Different cache stores have different clearing mechanisms
            // For Redis, we can use tags or patterns
            if (method_exists($cacheStore, 'tags')) {
                $cacheStore->tags(["contract:{$this->contractAddress}"])->flush();
            } else {
                // Fallback: manually clear known keys (less efficient)
                $cacheStore->forget("{$prefix}:contract:{$this->contractAddress}");
            }

            Log::channel($this->getLogChannel())->debug('Cleared all cached state', [
                'contract_address' => $this->contractAddress,
                'pattern' => $pattern,
            ]);
        } else {
            // Clear specific selectors
            foreach ($this->selectors as $selector) {
                $cacheKey = $this->getCacheKey($selector);
                $cacheStore->forget($cacheKey);

                Log::channel($this->getLogChannel())->debug('Cleared cached selector', [
                    'contract_address' => $this->contractAddress,
                    'selector' => $selector,
                    'cache_key' => $cacheKey,
                ]);
            }
        }
    }

    /**
     * Refresh specific selectors by reading fresh data from the network.
     *
     * @param ContractGateway $gateway The contract gateway
     * @return void
     * @throws MidnightException If reading state fails
     */
    private function refreshSelectors(ContractGateway $gateway): void
    {
        foreach ($this->selectors as $selector) {
            try {
                // Read fresh data - this will automatically cache it
                $result = $gateway->read(
                    contractAddress: $this->contractAddress,
                    selector: $selector,
                );

                Log::channel($this->getLogChannel())->debug('Refreshed selector', [
                    'contract_address' => $this->contractAddress,
                    'selector' => $selector,
                    'has_value' => $result->hasValue(),
                ]);
            } catch (Throwable $exception) {
                // Log the error but continue with other selectors
                Log::channel($this->getLogChannel())->warning('Failed to refresh selector', [
                    'contract_address' => $this->contractAddress,
                    'selector' => $selector,
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate a cache key for a specific selector.
     *
     * @param string $selector The selector name
     * @return string
     */
    private function getCacheKey(string $selector): string
    {
        $prefix = config('midnight.cache.prefix', 'midnight');
        return "{$prefix}:contract:{$this->contractAddress}:{$selector}";
    }

    /**
     * Log the start of a state refresh operation.
     *
     * @return void
     */
    private function logRefreshStart(): void
    {
        Log::channel($this->getLogChannel())->info('Refreshing contract state', [
            'contract_address' => $this->contractAddress,
            'selectors' => $this->selectors,
            'refresh_type' => $this->selectors === null ? 'full' : 'selective',
        ]);
    }

    /**
     * Log a successful state refresh.
     *
     * @return void
     */
    private function logRefreshSuccess(): void
    {
        Log::channel($this->getLogChannel())->info('Contract state refreshed successfully', [
            'contract_address' => $this->contractAddress,
            'selectors' => $this->selectors,
        ]);
    }

    /**
     * Log a state refresh failure.
     *
     * @param Throwable $exception The exception that occurred
     * @return void
     */
    private function logRefreshFailure(Throwable $exception): void
    {
        Log::channel($this->getLogChannel())->error('Contract state refresh failed', [
            'contract_address' => $this->contractAddress,
            'selectors' => $this->selectors,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the log channel for Midnight operations.
     *
     * @return string|null
     */
    private function getLogChannel(): ?string
    {
        return config('midnight.log_channel');
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable|null $exception The exception that caused the failure
     * @return void
     */
    public function failed(?Throwable $exception = null): void
    {
        Log::channel($this->getLogChannel())->error('Contract state refresh failed permanently', [
            'contract_address' => $this->contractAddress,
            'selectors' => $this->selectors,
            'attempts' => $this->attempts(),
            'exception' => $exception ? get_class($exception) : null,
            'message' => $exception?->getMessage(),
        ]);
    }
}
