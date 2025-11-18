<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

/**
 * Represents the result of a contract call on the Midnight network.
 *
 * This immutable DTO encapsulates the response from a contract method call,
 * including both the decoded return value and optional raw response data.
 */
final readonly class ContractCallResult
{
    /**
     * Create a new ContractCallResult instance.
     *
     * @param mixed $value The decoded return value from the contract call
     * @param bool $success Whether the call was successful
     * @param string|null $error Error message if the call failed
     * @param array<string, mixed> $rawResponse The raw response data from the bridge
     * @param array<string, mixed> $metadata Additional metadata about the call
     */
    public function __construct(
        public mixed $value,
        public bool $success = true,
        public ?string $error = null,
        public array $rawResponse = [],
        public array $metadata = [],
    ) {
    }

    /**
     * Create a ContractCallResult instance from an array.
     *
     * @param array<string, mixed> $data The result data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'] ?? $data['result'] ?? null,
            success: $data['success'] ?? true,
            error: $data['error'] ?? null,
            rawResponse: $data['raw_response'] ?? $data['rawResponse'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Create a successful result.
     *
     * @param mixed $value The return value
     * @param array<string, mixed> $rawResponse Optional raw response
     * @return self
     */
    public static function success(mixed $value, array $rawResponse = []): self
    {
        return new self(
            value: $value,
            success: true,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create a failed result.
     *
     * @param string $error The error message
     * @param array<string, mixed> $rawResponse Optional raw response
     * @return self
     */
    public static function failure(string $error, array $rawResponse = []): self
    {
        return new self(
            value: null,
            success: false,
            error: $error,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Convert the result to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'success' => $this->success,
            'error' => $this->error,
            'raw_response' => $this->rawResponse,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get the value or throw an exception if the call failed.
     *
     * @return mixed
     * @throws \RuntimeException If the call was not successful
     */
    public function getValueOrFail(): mixed
    {
        if (!$this->success) {
            throw new \RuntimeException(
                'Contract call failed: ' . ($this->error ?? 'Unknown error')
            );
        }

        return $this->value;
    }

    /**
     * Check if the result has a specific value.
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return $this->value !== null;
    }

    /**
     * Get the value as an array.
     *
     * @return array<mixed>
     */
    public function asArray(): array
    {
        return (array) $this->value;
    }

    /**
     * Get the value as a string.
     *
     * @return string
     */
    public function asString(): string
    {
        return (string) $this->value;
    }

    /**
     * Get the value as an integer.
     *
     * @return int
     */
    public function asInt(): int
    {
        return (int) $this->value;
    }

    /**
     * Get the value as a boolean.
     *
     * @return bool
     */
    public function asBool(): bool
    {
        return (bool) $this->value;
    }
}
