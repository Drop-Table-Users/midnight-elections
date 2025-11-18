<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the TxHash DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\TxHash
 */
final class TxHashTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_valid_hash(): void
    {
        $txHash = new TxHash('0x1234567890abcdef');

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0x1234567890abcdef', $txHash->value);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $txHash = new TxHash('0xabc123');

        $this->assertClassIsFinal($txHash);
        $this->assertClassIsReadonly($txHash);
    }

    #[Test]
    public function it_throws_exception_when_hash_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction hash cannot be empty');

        new TxHash('');
    }

    #[Test]
    #[DataProvider('emptyHashProvider')]
    public function it_validates_empty_hashes_in_constructor(string $invalidHash): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction hash cannot be empty');

        new TxHash($invalidHash);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $txHash = TxHash::fromString('0xdeadbeef');

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xdeadbeef', $txHash->value);
    }

    #[Test]
    #[DataProvider('validHashProvider')]
    public function it_can_be_created_from_various_valid_strings(string $validHash): void
    {
        $txHash = TxHash::fromString($validHash);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame($validHash, $txHash->value);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $txHash = new TxHash('0xtest123');

        $this->assertSame('0xtest123', (string) $txHash);
        $this->assertSame('0xtest123', $txHash->__toString());
    }

    #[Test]
    public function it_can_compare_with_another_hash_for_equality(): void
    {
        $hash1 = new TxHash('0xsame');
        $hash2 = new TxHash('0xsame');
        $hash3 = new TxHash('0xdifferent');

        $this->assertTrue($hash1->equals($hash2));
        $this->assertFalse($hash1->equals($hash3));
    }

    #[Test]
    #[DataProvider('hashEqualityProvider')]
    public function it_correctly_determines_equality(string $hash1, string $hash2, bool $expectedEqual): void
    {
        $txHash1 = new TxHash($hash1);
        $txHash2 = new TxHash($hash2);

        $this->assertSame($expectedEqual, $txHash1->equals($txHash2));
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $originalValue = '0xoriginal123';
        $txHash = new TxHash($originalValue);

        // Verify that the value property is readonly
        $this->assertPropertyIsReadonly($txHash, 'value');
        $this->assertSame($originalValue, $txHash->value);
    }

    #[Test]
    public function it_maintains_exact_value_without_modification(): void
    {
        $testCases = [
            '0xabc123',
            '0XABC123',
            'tx_hash_without_prefix',
            '0x' . str_repeat('a', 64),
        ];

        foreach ($testCases as $testValue) {
            $txHash = new TxHash($testValue);
            $this->assertSame($testValue, $txHash->value);
            $this->assertSame($testValue, (string) $txHash);
        }
    }

    #[Test]
    public function fromString_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction hash cannot be empty');

        TxHash::fromString('');
    }

    #[Test]
    public function multiple_instances_with_same_value_are_equal(): void
    {
        $value = '0xtesthash123';
        $hash1 = new TxHash($value);
        $hash2 = TxHash::fromString($value);

        $this->assertTrue($hash1->equals($hash2));
        $this->assertSame($hash1->value, $hash2->value);
    }

    #[Test]
    public function it_handles_various_hash_formats(): void
    {
        $formats = [
            'with_0x_prefix' => '0x1234567890abcdef',
            'without_prefix' => '1234567890abcdef',
            'uppercase' => '0X1234567890ABCDEF',
            'mixed_case' => '0x1234AbCd',
            'short_hash' => '0x123',
            'long_hash' => '0x' . str_repeat('f', 64),
        ];

        foreach ($formats as $name => $hash) {
            $txHash = new TxHash($hash);
            $this->assertSame($hash, $txHash->value, "Failed for format: {$name}");
        }
    }

    #[Test]
    public function it_preserves_case_sensitivity(): void
    {
        $lowerHash = new TxHash('0xabcdef');
        $upperHash = new TxHash('0XABCDEF');
        $mixedHash = new TxHash('0xAbCdEf');

        $this->assertFalse($lowerHash->equals($upperHash));
        $this->assertFalse($lowerHash->equals($mixedHash));
        $this->assertFalse($upperHash->equals($mixedHash));
    }

    /**
     * Data provider for empty hash values.
     *
     * @return array<string, array<string>>
     */
    public static function emptyHashProvider(): array
    {
        return [
            'empty string' => [''],
        ];
    }

    /**
     * Data provider for valid hash values.
     *
     * @return array<string, array<string>>
     */
    public static function validHashProvider(): array
    {
        return [
            'standard hex with prefix' => ['0x1234567890abcdef'],
            'long hash (64 chars)' => ['0x' . str_repeat('a', 64)],
            'uppercase hex' => ['0XABCDEF123456'],
            'mixed case' => ['0xAbCdEf123456'],
            'without prefix' => ['abcdef1234567890'],
            'short hash' => ['0x123'],
            'numeric only' => ['123456789'],
        ];
    }

    /**
     * Data provider for hash equality tests.
     *
     * @return array<string, array{string, string, bool}>
     */
    public static function hashEqualityProvider(): array
    {
        return [
            'identical hashes' => ['0xabc123', '0xabc123', true],
            'different hashes' => ['0xabc123', '0xdef456', false],
            'case sensitive mismatch' => ['0xabc', '0xABC', false],
            'with and without prefix' => ['0xabc', 'abc', false],
            'different lengths' => ['0xabc', '0xabcd', false],
        ];
    }
}
