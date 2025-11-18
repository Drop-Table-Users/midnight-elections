<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

/**
 * Represents the response from a zero-knowledge proof generation.
 *
 * This immutable DTO encapsulates the generated proof data, public outputs,
 * and metadata from the proof server.
 */
final readonly class ProofResponse
{
    /**
     * Create a new ProofResponse instance.
     *
     * @param string $proof The generated proof data (typically hex or base64 encoded)
     * @param array<string, mixed> $publicOutputs Public outputs from the proof computation
     * @param bool $verified Whether the proof has been verified
     * @param float|null $generationTime Time taken to generate the proof in seconds
     * @param array<string, mixed> $metadata Additional metadata from the proof server
     */
    public function __construct(
        public string $proof,
        public array $publicOutputs = [],
        public bool $verified = false,
        public ?float $generationTime = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Create a ProofResponse instance from an array.
     *
     * @param array<string, mixed> $data The proof response data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            proof: $data['proof'] ?? '',
            publicOutputs: $data['public_outputs'] ?? $data['publicOutputs'] ?? [],
            verified: $data['verified'] ?? false,
            generationTime: isset($data['generation_time']) || isset($data['generationTime'])
                ? (float) ($data['generation_time'] ?? $data['generationTime'])
                : null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert the ProofResponse to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'proof' => $this->proof,
            'public_outputs' => $this->publicOutputs,
            'verified' => $this->verified,
            'generation_time' => $this->generationTime,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if the proof is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->proof);
    }

    /**
     * Check if the proof has public outputs.
     *
     * @return bool
     */
    public function hasPublicOutputs(): bool
    {
        return !empty($this->publicOutputs);
    }

    /**
     * Get a specific public output by key.
     *
     * @param string $key The output key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function getPublicOutput(string $key, mixed $default = null): mixed
    {
        return $this->publicOutputs[$key] ?? $default;
    }

    /**
     * Get the proof as a hex string.
     *
     * @return string
     */
    public function asHex(): string
    {
        // If already hex, return as-is
        if (preg_match('/^[0-9a-fA-F]+$/', $this->proof)) {
            return $this->proof;
        }

        // Otherwise, assume base64 and convert
        return bin2hex(base64_decode($this->proof));
    }

    /**
     * Get the proof as a base64 string.
     *
     * @return string
     */
    public function asBase64(): string
    {
        // If looks like hex, convert to base64
        if (preg_match('/^[0-9a-fA-F]+$/', $this->proof)) {
            return base64_encode(hex2bin($this->proof));
        }

        // Otherwise, assume already base64
        return $this->proof;
    }

    /**
     * Get formatted generation time.
     *
     * @param int $decimals Number of decimal places
     * @return string|null
     */
    public function getFormattedGenerationTime(int $decimals = 3): ?string
    {
        if ($this->generationTime === null) {
            return null;
        }

        return number_format($this->generationTime, $decimals) . 's';
    }
}
