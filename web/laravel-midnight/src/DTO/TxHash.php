<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

use InvalidArgumentException;

/**
 * Represents a Midnight transaction hash.
 *
 * This is an immutable value object that encapsulates a transaction hash
 * identifier used to track and reference transactions on the Midnight network.
 */
final readonly class TxHash
{
    /**
     * Create a new TxHash instance.
     *
     * @param string $value The transaction hash value
     * @throws InvalidArgumentException If the hash is empty
     */
    public function __construct(public string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Transaction hash cannot be empty');
        }
    }

    /**
     * Create a TxHash instance from a string.
     *
     * @param string $hash The transaction hash string
     * @return self
     */
    public static function fromString(string $hash): self
    {
        return new self($hash);
    }

    /**
     * Convert the TxHash to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check if this hash equals another hash.
     *
     * @param TxHash $other The hash to compare with
     * @return bool
     */
    public function equals(TxHash $other): bool
    {
        return $this->value === $other->value;
    }
}
