<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\Address;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the Address DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\Address
 */
final class AddressTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_valid_address(): void
    {
        $address = new Address('midnight1abc123def456');

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('midnight1abc123def456', $address->value);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $address = new Address('midnight1abc123def456');

        $this->assertClassIsFinal($address);
        $this->assertClassIsReadonly($address);
    }

    #[Test]
    public function it_throws_exception_when_address_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address cannot be empty');

        new Address('');
    }

    #[Test]
    #[DataProvider('emptyAddressProvider')]
    public function it_validates_empty_addresses_in_constructor(string $invalidAddress): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address cannot be empty');

        new Address($invalidAddress);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $address = Address::fromString('midnight1xyz789');

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('midnight1xyz789', $address->value);
    }

    #[Test]
    #[DataProvider('validAddressProvider')]
    public function it_can_be_created_from_various_valid_strings(string $validAddress): void
    {
        $address = Address::fromString($validAddress);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame($validAddress, $address->value);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $address = new Address('midnight1test');

        $this->assertSame('midnight1test', (string) $address);
        $this->assertSame('midnight1test', $address->__toString());
    }

    #[Test]
    public function it_can_compare_with_another_address_for_equality(): void
    {
        $address1 = new Address('midnight1same');
        $address2 = new Address('midnight1same');
        $address3 = new Address('midnight1different');

        $this->assertTrue($address1->equals($address2));
        $this->assertFalse($address1->equals($address3));
    }

    #[Test]
    #[DataProvider('addressEqualityProvider')]
    public function it_correctly_determines_equality(string $address1, string $address2, bool $expectedEqual): void
    {
        $addr1 = new Address($address1);
        $addr2 = new Address($address2);

        $this->assertSame($expectedEqual, $addr1->equals($addr2));
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $originalValue = 'midnight1original';
        $address = new Address($originalValue);

        // Verify that the value property is readonly
        $this->assertPropertyIsReadonly($address, 'value');
        $this->assertSame($originalValue, $address->value);
    }

    #[Test]
    public function it_maintains_exact_value_without_modification(): void
    {
        $testCases = [
            'midnight1abc',
            'MIDNIGHT1XYZ',
            'midnight1_special-chars.test',
            'midnight1' . str_repeat('a', 100),
        ];

        foreach ($testCases as $testValue) {
            $address = new Address($testValue);
            $this->assertSame($testValue, $address->value);
            $this->assertSame($testValue, (string) $address);
        }
    }

    #[Test]
    public function fromString_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address cannot be empty');

        Address::fromString('');
    }

    #[Test]
    public function multiple_instances_with_same_value_are_equal(): void
    {
        $value = 'midnight1test123';
        $address1 = new Address($value);
        $address2 = Address::fromString($value);

        $this->assertTrue($address1->equals($address2));
        $this->assertSame($address1->value, $address2->value);
    }

    #[Test]
    public function it_preserves_whitespace_in_address_value(): void
    {
        // Note: This might not be desired behavior in production, but tests actual behavior
        $addressWithSpaces = 'midnight1 has spaces';
        $address = new Address($addressWithSpaces);

        $this->assertSame($addressWithSpaces, $address->value);
    }

    /**
     * Data provider for empty address values.
     *
     * @return array<string, array<string>>
     */
    public static function emptyAddressProvider(): array
    {
        return [
            'empty string' => [''],
        ];
    }

    /**
     * Data provider for valid address values.
     *
     * @return array<string, array<string>>
     */
    public static function validAddressProvider(): array
    {
        return [
            'simple address' => ['midnight1abc123'],
            'long address' => ['midnight1' . str_repeat('x', 50)],
            'address with uppercase' => ['MIDNIGHT1ABC'],
            'address with mixed case' => ['MiDnIgHt1AbC'],
            'address with special chars' => ['midnight1-test_address.123'],
            'short address' => ['m1'],
            'numeric address' => ['123456789'],
        ];
    }

    /**
     * Data provider for address equality tests.
     *
     * @return array<string, array{string, string, bool}>
     */
    public static function addressEqualityProvider(): array
    {
        return [
            'identical addresses' => ['midnight1abc', 'midnight1abc', true],
            'different addresses' => ['midnight1abc', 'midnight1xyz', false],
            'case sensitive mismatch' => ['midnight1abc', 'MIDNIGHT1ABC', false],
            'different lengths' => ['midnight1a', 'midnight1ab', false],
            'similar but different' => ['midnight1test', 'midnight1test2', false],
        ];
    }
}
