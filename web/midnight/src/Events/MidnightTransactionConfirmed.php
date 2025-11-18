<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\TxHash;

/**
 * Event dispatched when a Midnight transaction is successfully confirmed on-chain.
 *
 * This event is fired after a transaction has been submitted to the Midnight network
 * and confirmed in a block. It contains information about the original contract call
 * and the resulting transaction hash.
 *
 * Listeners can use this event to:
 * - Update application state based on confirmed transactions
 * - Trigger follow-up actions or workflows
 * - Log successful transaction completions
 * - Update UI notifications
 */
class MidnightTransactionConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param ContractCall $contractCall The original contract call that was submitted
     * @param TxHash $txHash The transaction hash of the confirmed transaction
     * @param array<string, mixed> $metadata Additional metadata about the transaction confirmation
     */
    public function __construct(
        public readonly ContractCall $contractCall,
        public readonly TxHash $txHash,
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
     * Get the transaction hash as a string.
     *
     * @return string
     */
    public function getTxHash(): string
    {
        return $this->txHash->value;
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
            'tx_hash' => $this->txHash->value,
            'public_args' => $this->contractCall->publicArgs,
            'metadata' => $this->metadata,
        ];
    }
}
