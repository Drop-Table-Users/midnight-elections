<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

use InvalidArgumentException;

/**
 * Represents a zero-knowledge proof generation request.
 *
 * This immutable DTO encapsulates all information needed to generate a ZK proof
 * for a Midnight contract operation, including the circuit, public inputs, and
 * private witness data.
 */
final readonly class ProofRequest
{
    /**
     * Create a new ProofRequest instance.
     *
     * @param string $contractName The contract name or identifier
     * @param string $entrypoint The contract entrypoint/method requiring proof
     * @param array<string, mixed> $publicInputs Public inputs visible on-chain
     * @param array<string, mixed> $privateInputs Private witness data for the proof
     * @param string|null $circuitPath Optional path to the compiled circuit
     * @param array<string, mixed> $metadata Additional metadata for proof generation
     */
    public function __construct(
        public string $contractName,
        public string $entrypoint,
        public array $publicInputs = [],
        public array $privateInputs = [],
        public ?string $circuitPath = null,
        public array $metadata = [],
    ) {
        if (empty($this->contractName)) {
            throw new InvalidArgumentException('Contract name cannot be empty');
        }

        if (empty($this->entrypoint)) {
            throw new InvalidArgumentException('Entrypoint cannot be empty');
        }
    }

    /**
     * Create a ProofRequest instance from an array.
     *
     * @param array<string, mixed> $data The proof request data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contractName: $data['contract_name'] ?? $data['contractName'] ?? '',
            entrypoint: $data['entrypoint'] ?? '',
            publicInputs: $data['public_inputs'] ?? $data['publicInputs'] ?? [],
            privateInputs: $data['private_inputs'] ?? $data['privateInputs'] ?? [],
            circuitPath: $data['circuit_path'] ?? $data['circuitPath'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Create a ProofRequest from a ContractCall.
     *
     * @param ContractCall $call The contract call
     * @param string $contractName The contract name
     * @return self
     */
    public static function fromContractCall(ContractCall $call, string $contractName): self
    {
        return new self(
            contractName: $contractName,
            entrypoint: $call->entrypoint,
            publicInputs: $call->publicArgs,
            privateInputs: $call->privateArgs,
        );
    }

    /**
     * Convert the ProofRequest to an array suitable for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contract_name' => $this->contractName,
            'entrypoint' => $this->entrypoint,
            'public_inputs' => $this->publicInputs,
            'private_inputs' => $this->privateInputs,
            'circuit_path' => $this->circuitPath,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if this request has private inputs.
     *
     * @return bool
     */
    public function hasPrivateInputs(): bool
    {
        return !empty($this->privateInputs);
    }

    /**
     * Check if this request has a custom circuit path.
     *
     * @return bool
     */
    public function hasCircuitPath(): bool
    {
        return $this->circuitPath !== null;
    }

    /**
     * Create a new instance with additional metadata.
     *
     * @param array<string, mixed> $metadata Additional metadata to merge
     * @return self
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            contractName: $this->contractName,
            entrypoint: $this->entrypoint,
            publicInputs: $this->publicInputs,
            privateInputs: $this->privateInputs,
            circuitPath: $this->circuitPath,
            metadata: array_merge($this->metadata, $metadata),
        );
    }
}
