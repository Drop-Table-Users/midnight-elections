<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

use InvalidArgumentException;

/**
 * Represents a Midnight blockchain address.
 *
 * This is an immutable value object that encapsulates a Midnight network address
 * with basic validation to ensure it's not empty.
 */
final readonly class Address
{
    /**
     * Create a new Address instance.
     *
     * @param string $value The Midnight address value
     * @throws InvalidArgumentException If the address is empty
     */
    public function __construct(public string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Address cannot be empty');
        }
    }

    /**
     * Create an Address instance from a string.
     *
     * @param string $address The address string
     * @return self
     */
    public static function fromString(string $address): self
    {
        return new self($address);
    }

    /**
     * Convert the Address to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check if this address equals another address.
     *
     * @param Address $other The address to compare with
     * @return bool
     */
    public function equals(Address $other): bool
    {
        return $this->value === $other->value;
    }
}
