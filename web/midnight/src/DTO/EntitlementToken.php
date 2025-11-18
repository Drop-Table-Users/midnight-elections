<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

use InvalidArgumentException;

/**
 * Represents an entitlement token for voting or private actions on Midnight.
 *
 * This immutable DTO encapsulates a cryptographic token that proves eligibility
 * to perform certain actions (like voting) while maintaining privacy through
 * zero-knowledge proofs.
 */
final readonly class EntitlementToken
{
    /**
     * Create a new EntitlementToken instance.
     *
     * @param string $token The token value (typically hex or base64 encoded)
     * @param string $identity The identity this token was issued for
     * @param \DateTimeImmutable|null $issuedAt When the token was issued
     * @param \DateTimeImmutable|null $expiresAt When the token expires
     * @param array<string, mixed> $claims Additional claims embedded in the token
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $token,
        public string $identity,
        public ?\DateTimeImmutable $issuedAt = null,
        public ?\DateTimeImmutable $expiresAt = null,
        public array $claims = [],
        public array $metadata = [],
    ) {
        if (empty($this->token)) {
            throw new InvalidArgumentException('Entitlement token cannot be empty');
        }

        if (empty($this->identity)) {
            throw new InvalidArgumentException('Identity cannot be empty');
        }
    }

    /**
     * Create an EntitlementToken from a token string.
     *
     * @param string $token The token string
     * @param string $identity Optional identity (defaults to 'unknown')
     * @return self
     */
    public static function fromString(string $token, string $identity = 'unknown'): self
    {
        return new self(
            token: $token,
            identity: $identity,
        );
    }

    /**
     * Create an EntitlementToken instance from an array.
     *
     * @param array<string, mixed> $data The token data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $issuedAt = null;
        if (isset($data['issued_at']) || isset($data['issuedAt'])) {
            $timestamp = $data['issued_at'] ?? $data['issuedAt'];
            $issuedAt = is_string($timestamp)
                ? new \DateTimeImmutable($timestamp)
                : \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
        }

        $expiresAt = null;
        if (isset($data['expires_at']) || isset($data['expiresAt'])) {
            $timestamp = $data['expires_at'] ?? $data['expiresAt'];
            $expiresAt = is_string($timestamp)
                ? new \DateTimeImmutable($timestamp)
                : \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
        }

        return new self(
            token: $data['token'] ?? '',
            identity: $data['identity'] ?? '',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            claims: $data['claims'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert the EntitlementToken to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'identity' => $this->identity,
            'issued_at' => $this->issuedAt?->format(\DateTimeInterface::ATOM),
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'claims' => $this->claims,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert the token to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->token;
    }

    /**
     * Check if the token has expired.
     *
     * @param \DateTimeImmutable|null $now The current time (defaults to now)
     * @return bool
     */
    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now = $now ?? new \DateTimeImmutable();
        return $now > $this->expiresAt;
    }

    /**
     * Check if the token is currently valid (not expired).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get a specific claim value.
     *
     * @param string $key The claim key
     * @param mixed $default Default value if claim not found
     * @return mixed
     */
    public function getClaim(string $key, mixed $default = null): mixed
    {
        return $this->claims[$key] ?? $default;
    }

    /**
     * Check if the token has a specific claim.
     *
     * @param string $key The claim key
     * @return bool
     */
    public function hasClaim(string $key): bool
    {
        return isset($this->claims[$key]);
    }

    /**
     * Get the time remaining until expiration.
     *
     * @param \DateTimeImmutable|null $now The current time (defaults to now)
     * @return \DateInterval|null Null if no expiration set
     */
    public function getTimeToExpiration(?\DateTimeImmutable $now = null): ?\DateInterval
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now = $now ?? new \DateTimeImmutable();
        return $now->diff($this->expiresAt);
    }

    /**
     * Create a new instance with additional claims.
     *
     * @param array<string, mixed> $claims Additional claims to merge
     * @return self
     */
    public function withClaims(array $claims): self
    {
        return new self(
            token: $this->token,
            identity: $this->identity,
            issuedAt: $this->issuedAt,
            expiresAt: $this->expiresAt,
            claims: array_merge($this->claims, $claims),
            metadata: $this->metadata,
        );
    }
}
