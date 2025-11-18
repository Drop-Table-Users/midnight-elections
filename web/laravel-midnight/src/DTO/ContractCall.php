<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

use InvalidArgumentException;

/**
 * Represents a contract call request to the Midnight network.
 *
 * This immutable DTO encapsulates all information needed to call a contract method,
 * including both public and private arguments for zero-knowledge operations.
 */
final readonly class ContractCall
{
    /**
     * Create a new ContractCall instance.
     *
     * @param string $contractAddress The contract address to call
     * @param string $entrypoint The contract method/entrypoint name
     * @param array<string, mixed> $publicArgs Public arguments visible on-chain
     * @param array<string, mixed> $privateArgs Private arguments for ZK proofs
     * @param bool $readOnly Whether this is a read-only call (no state change)
     * @param array<string, mixed> $metadata Additional metadata for the call
     */
    public function __construct(
        public string $contractAddress,
        public string $entrypoint,
        public array $publicArgs = [],
        public array $privateArgs = [],
        public bool $readOnly = false,
        public array $metadata = [],
    ) {
        if (empty($this->contractAddress)) {
            throw new InvalidArgumentException('Contract address cannot be empty');
        }

        if (empty($this->entrypoint)) {
            throw new InvalidArgumentException('Entrypoint cannot be empty');
        }
    }

    /**
     * Create a ContractCall instance from an array.
     *
     * @param array<string, mixed> $data The contract call data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contractAddress: $data['contract_address'] ?? $data['contractAddress'] ?? '',
            entrypoint: $data['entrypoint'] ?? '',
            publicArgs: $data['public_args'] ?? $data['publicArgs'] ?? [],
            privateArgs: $data['private_args'] ?? $data['privateArgs'] ?? [],
            readOnly: $data['read_only'] ?? $data['readOnly'] ?? false,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Create a read-only contract call.
     *
     * @param string $contractAddress The contract address
     * @param string $entrypoint The method to call
     * @param array<string, mixed> $args The call arguments
     * @return self
     */
    public static function readOnly(string $contractAddress, string $entrypoint, array $args = []): self
    {
        return new self(
            contractAddress: $contractAddress,
            entrypoint: $entrypoint,
            publicArgs: $args,
            readOnly: true,
        );
    }

    /**
     * Create a write contract call (state-changing).
     *
     * @param string $contractAddress The contract address
     * @param string $entrypoint The method to call
     * @param array<string, mixed> $publicArgs Public arguments
     * @param array<string, mixed> $privateArgs Private arguments
     * @return self
     */
    public static function write(
        string $contractAddress,
        string $entrypoint,
        array $publicArgs = [],
        array $privateArgs = []
    ): self {
        return new self(
            contractAddress: $contractAddress,
            entrypoint: $entrypoint,
            publicArgs: $publicArgs,
            privateArgs: $privateArgs,
            readOnly: false,
        );
    }

    /**
     * Convert the ContractCall to an array suitable for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contract_address' => $this->contractAddress,
            'entrypoint' => $this->entrypoint,
            'public_args' => $this->publicArgs,
            'private_args' => $this->privateArgs,
            'read_only' => $this->readOnly,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if this call has private arguments.
     *
     * @return bool
     */
    public function hasPrivateArgs(): bool
    {
        return !empty($this->privateArgs);
    }

    /**
     * Check if this call requires ZK proof generation.
     *
     * @return bool
     */
    public function requiresProof(): bool
    {
        return $this->hasPrivateArgs() && !$this->readOnly;
    }
}
