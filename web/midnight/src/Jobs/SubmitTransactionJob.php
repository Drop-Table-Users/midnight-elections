<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Events\MidnightTransactionConfirmed;
use VersionTwo\Midnight\Events\MidnightTransactionFailed;
use VersionTwo\Midnight\Exceptions\MidnightException;

/**
 * Queue job for submitting transactions to the Midnight network.
 *
 * This job handles asynchronous transaction submission with automatic retries,
 * exponential backoff, and event dispatching on success or failure. It implements
 * ShouldBeUnique to prevent duplicate submissions of the same transaction.
 *
 * Features:
 * - Automatic retry with exponential backoff
 * - Unique job enforcement to prevent duplicates
 * - Event dispatching on success/failure
 * - Comprehensive error handling and logging
 * - Configurable queue connection and timeout
 *
 * Usage:
 * ```php
 * $call = ContractCall::write(
 *     contractAddress: '0x123...',
 *     entrypoint: 'transfer',
 *     publicArgs: ['to' => '0x456...', 'amount' => 100]
 * );
 *
 * SubmitTransactionJob::dispatch($call);
 * ```
 */
class SubmitTransactionJob implements ShouldQueue, ShouldBeUnique
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
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 3600; // 1 hour

    /**
     * Create a new job instance.
     *
     * @param ContractCall $contractCall The contract call to submit to the network
     */
    public function __construct(
        public readonly ContractCall $contractCall,
    ) {
        // Configure queue from config
        $this->onConnection(config('midnight.queue.connection', 'redis'));
        $this->onQueue(config('midnight.queue.queue', 'midnight'));

        // Configure retry behavior from config
        $this->tries = config('midnight.retry.times', 3);
        $this->timeout = config('midnight.bridge.timeout', 10) * 2; // Double the bridge timeout

        // Calculate exponential backoff
        $baseSleep = config('midnight.retry.sleep', 100) / 1000; // Convert to seconds
        $multiplier = config('midnight.retry.backoff_multiplier', 2);
        $this->backoff = $this->calculateBackoff($baseSleep, $multiplier, $this->tries);
    }

    /**
     * Calculate exponential backoff delays.
     *
     * @param float $baseSleep The base sleep time in seconds
     * @param int $multiplier The backoff multiplier
     * @param int $attempts The number of retry attempts
     * @return array<int>
     */
    private function calculateBackoff(float $baseSleep, int $multiplier, int $attempts): array
    {
        $backoff = [];
        for ($i = 1; $i < $attempts; $i++) {
            $backoff[] = (int) ($baseSleep * pow($multiplier, $i - 1));
        }
        return $backoff;
    }

    /**
     * Get the unique ID for the job.
     *
     * This prevents duplicate submissions of the same transaction by creating
     * a unique identifier based on the contract call parameters.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return sprintf(
            'midnight:submit:%s:%s:%s',
            $this->contractCall->contractAddress,
            $this->contractCall->entrypoint,
            md5(json_encode([
                'publicArgs' => $this->contractCall->publicArgs,
                'privateArgs' => $this->contractCall->privateArgs,
            ]))
        );
    }

    /**
     * Execute the job.
     *
     * Submits the transaction to the Midnight network via the MidnightClient.
     * On success, dispatches MidnightTransactionConfirmed event.
     * On failure, the job will be retried according to the retry configuration.
     *
     * @param MidnightClient $client The Midnight client for submitting transactions
     * @return void
     * @throws MidnightException If transaction submission fails after all retries
     */
    public function handle(MidnightClient $client): void
    {
        try {
            $this->logAttempt();

            // Submit the transaction to the Midnight network
            $txHash = $client->submitTransaction($this->contractCall);

            $this->logSuccess($txHash);

            // Dispatch success event
            event(new MidnightTransactionConfirmed(
                contractCall: $this->contractCall,
                txHash: $txHash,
                metadata: [
                    'attempts' => $this->attempts(),
                    'submitted_at' => now()->toIso8601String(),
                ],
            ));
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            // If we still have retries left, let the queue handler retry
            if ($this->attempts() < $this->tries) {
                throw $exception;
            }

            // No more retries - dispatch failure event
            $this->failed($exception);
        }
    }

    /**
     * Handle a job failure.
     *
     * This method is called when the job has failed after all retry attempts.
     * It dispatches the MidnightTransactionFailed event and logs the failure.
     *
     * @param Throwable|null $exception The exception that caused the failure
     * @return void
     */
    public function failed(?Throwable $exception = null): void
    {
        $failureReason = $exception
            ? sprintf('Transaction submission failed: %s', $exception->getMessage())
            : 'Transaction submission failed after all retry attempts';

        $this->logPermanentFailure($exception);

        // Dispatch failure event
        event(new MidnightTransactionFailed(
            contractCall: $this->contractCall,
            exception: $exception ?? new MidnightException($failureReason),
            failureReason: $failureReason,
            metadata: [
                'attempts' => $this->attempts(),
                'failed_at' => now()->toIso8601String(),
            ],
        ));
    }

    /**
     * Log a transaction submission attempt.
     *
     * @return void
     */
    private function logAttempt(): void
    {
        Log::channel($this->getLogChannel())->info('Submitting Midnight transaction', [
            'contract_address' => $this->contractCall->contractAddress,
            'entrypoint' => $this->contractCall->entrypoint,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'has_private_args' => $this->contractCall->hasPrivateArgs(),
        ]);
    }

    /**
     * Log a successful transaction submission.
     *
     * @param TxHash $txHash The transaction hash
     * @return void
     */
    private function logSuccess(TxHash $txHash): void
    {
        Log::channel($this->getLogChannel())->info('Midnight transaction submitted successfully', [
            'contract_address' => $this->contractCall->contractAddress,
            'entrypoint' => $this->contractCall->entrypoint,
            'tx_hash' => $txHash->value,
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Log a transaction submission failure (but retries remain).
     *
     * @param Throwable $exception The exception that occurred
     * @return void
     */
    private function logFailure(Throwable $exception): void
    {
        Log::channel($this->getLogChannel())->warning('Midnight transaction submission failed, will retry', [
            'contract_address' => $this->contractCall->contractAddress,
            'entrypoint' => $this->contractCall->entrypoint,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Log a permanent transaction submission failure.
     *
     * @param Throwable|null $exception The exception that occurred
     * @return void
     */
    private function logPermanentFailure(?Throwable $exception): void
    {
        Log::channel($this->getLogChannel())->error('Midnight transaction submission failed permanently', [
            'contract_address' => $this->contractCall->contractAddress,
            'entrypoint' => $this->contractCall->entrypoint,
            'attempts' => $this->attempts(),
            'exception' => $exception ? get_class($exception) : null,
            'message' => $exception?->getMessage(),
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
}
