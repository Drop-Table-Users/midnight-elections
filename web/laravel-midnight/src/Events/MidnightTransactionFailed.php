<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;
use VersionTwo\Midnight\DTO\ContractCall;

/**
 * Event dispatched when a Midnight transaction submission or confirmation fails.
 *
 * This event is fired when a transaction cannot be submitted to the Midnight network,
 * fails during processing, or fails to confirm. It contains information about the
 * original contract call and the error that occurred.
 *
 * Listeners can use this event to:
 * - Log transaction failures for debugging
 * - Implement fallback or retry mechanisms
 * - Update application state to reflect failures
 * - Send notifications about failed transactions
 * - Trigger alternative workflows
 */
class MidnightTransactionFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param ContractCall $contractCall The original contract call that failed
     * @param Throwable $exception The exception that caused the failure
     * @param string $failureReason Human-readable description of why the transaction failed
     * @param array<string, mixed> $metadata Additional metadata about the failure
     */
    public function __construct(
        public readonly ContractCall $contractCall,
        public readonly Throwable $exception,
        public readonly string $failureReason,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * Get the contract address from the contract call.
     *
     * @return string
     */
    public function getContractAddress(): string
    {
        return $this->contractCall->contractAddress;
    }

    /**
     * Get the entrypoint name from the contract call.
     *
     * @return string
     */
    public function getEntrypoint(): string
    {
        return $this->contractCall->entrypoint;
    }

    /**
     * Get the exception message.
     *
     * @return string
     */
    public function getExceptionMessage(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * Get the exception class name.
     *
     * @return string
     */
    public function getExceptionClass(): string
    {
        return get_class($this->exception);
    }

    /**
     * Convert the event to an array for logging or serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contract_address' => $this->contractCall->contractAddress,
            'entrypoint' => $this->contractCall->entrypoint,
            'failure_reason' => $this->failureReason,
            'exception_class' => $this->getExceptionClass(),
            'exception_message' => $this->getExceptionMessage(),
            'public_args' => $this->contractCall->publicArgs,
            'metadata' => $this->metadata,
        ];
    }
}
