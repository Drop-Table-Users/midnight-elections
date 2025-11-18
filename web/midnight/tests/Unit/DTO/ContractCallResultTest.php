<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the ContractCallResult DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\ContractCallResult
 */
final class ContractCallResultTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_required_fields(): void
    {
        $result = new ContractCallResult(value: 'test_value');

        $this->assertInstanceOf(ContractCallResult::class, $result);
        $this->assertSame('test_value', $result->value);
        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame([], $result->rawResponse);
        $this->assertSame([], $result->metadata);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_fields(): void
    {
        $rawResponse = ['data' => 'response'];
        $metadata = ['timestamp' => '2024-01-01'];

        $result = new ContractCallResult(
            value: 42,
            success: false,
            error: 'Failed',
            rawResponse: $rawResponse,
            metadata: $metadata
        );

        $this->assertSame(42, $result->value);
        $this->assertFalse($result->success);
        $this->assertSame('Failed', $result->error);
        $this->assertSame($rawResponse, $result->rawResponse);
        $this->assertSame($metadata, $result->metadata);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $result = new ContractCallResult(value: 'test');

        $this->assertClassIsFinal($result);
        $this->assertClassIsReadonly($result);
    }

    #[Test]
    public function it_can_be_created_from_array_with_value_key(): void
    {
        $data = [
            'value' => 'test_data',
            'success' => true,
            'error' => null,
            'raw_response' => ['key' => 'value'],
            'metadata' => ['info' => 'data'],
        ];

        $result = ContractCallResult::fromArray($data);

        $this->assertSame('test_data', $result->value);
        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame(['key' => 'value'], $result->rawResponse);
        $this->assertSame(['info' => 'data'], $result->metadata);
    }

    #[Test]
    public function it_can_be_created_from_array_with_result_key(): void
    {
        $data = [
            'result' => 'test_result',
            'success' => false,
            'error' => 'Error message',
        ];

        $result = ContractCallResult::fromArray($data);

        $this->assertSame('test_result', $result->value);
    }

    #[Test]
    public function it_prefers_value_over_result_in_fromArray(): void
    {
        $data = [
            'value' => 'preferred',
            'result' => 'ignored',
        ];

        $result = ContractCallResult::fromArray($data);

        $this->assertSame('preferred', $result->value);
    }

    #[Test]
    public function it_handles_camel_case_keys_in_fromArray(): void
    {
        $data = [
            'value' => 'test',
            'rawResponse' => ['data' => 'camelCase'],
        ];

        $result = ContractCallResult::fromArray($data);

        $this->assertSame(['data' => 'camelCase'], $result->rawResponse);
    }

    #[Test]
    public function it_prefers_snake_case_over_camel_case_in_fromArray(): void
    {
        $data = [
            'raw_response' => ['snake' => 'case'],
            'rawResponse' => ['camel' => 'case'],
        ];

        $result = ContractCallResult::fromArray($data);

        $this->assertSame(['snake' => 'case'], $result->rawResponse);
    }

    #[Test]
    public function it_can_create_successful_result(): void
    {
        $result = ContractCallResult::success('success_value', ['raw' => 'data']);

        $this->assertSame('success_value', $result->value);
        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame(['raw' => 'data'], $result->rawResponse);
    }

    #[Test]
    public function it_can_create_failed_result(): void
    {
        $result = ContractCallResult::failure('Error occurred', ['raw' => 'error_data']);

        $this->assertNull($result->value);
        $this->assertFalse($result->success);
        $this->assertSame('Error occurred', $result->error);
        $this->assertSame(['raw' => 'error_data'], $result->rawResponse);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $result = new ContractCallResult(
            value: 'test',
            success: true,
            error: null,
            rawResponse: ['data' => 'value'],
            metadata: ['meta' => 'info']
        );

        $array = $result->toArray();

        $this->assertSame([
            'value' => 'test',
            'success' => true,
            'error' => null,
            'raw_response' => ['data' => 'value'],
            'metadata' => ['meta' => 'info'],
        ], $array);
    }

    #[Test]
    public function it_returns_value_when_successful(): void
    {
        $result = new ContractCallResult(value: 'success_data', success: true);

        $value = $result->getValueOrFail();

        $this->assertSame('success_data', $value);
    }

    #[Test]
    public function it_throws_exception_when_getting_value_from_failed_result(): void
    {
        $result = new ContractCallResult(
            value: null,
            success: false,
            error: 'Operation failed'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contract call failed: Operation failed');

        $result->getValueOrFail();
    }

    #[Test]
    public function it_throws_exception_with_unknown_error_when_error_is_null(): void
    {
        $result = new ContractCallResult(value: null, success: false, error: null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contract call failed: Unknown error');

        $result->getValueOrFail();
    }

    #[Test]
    #[DataProvider('hasValueProvider')]
    public function it_detects_if_result_has_value(mixed $value, bool $expected): void
    {
        $result = new ContractCallResult(value: $value);

        $this->assertSame($expected, $result->hasValue());
    }

    #[Test]
    #[DataProvider('typeConversionProvider')]
    public function it_converts_value_to_array(mixed $value, array $expected): void
    {
        $result = new ContractCallResult(value: $value);

        $this->assertSame($expected, $result->asArray());
    }

    #[Test]
    #[DataProvider('stringConversionProvider')]
    public function it_converts_value_to_string(mixed $value, string $expected): void
    {
        $result = new ContractCallResult(value: $value);

        $this->assertSame($expected, $result->asString());
    }

    #[Test]
    #[DataProvider('intConversionProvider')]
    public function it_converts_value_to_int(mixed $value, int $expected): void
    {
        $result = new ContractCallResult(value: $value);

        $this->assertSame($expected, $result->asInt());
    }

    #[Test]
    #[DataProvider('boolConversionProvider')]
    public function it_converts_value_to_bool(mixed $value, bool $expected): void
    {
        $result = new ContractCallResult(value: $value);

        $this->assertSame($expected, $result->asBool());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $result = new ContractCallResult(value: 'test');

        $this->assertPropertyIsReadonly($result, 'value');
        $this->assertPropertyIsReadonly($result, 'success');
        $this->assertPropertyIsReadonly($result, 'error');
        $this->assertPropertyIsReadonly($result, 'rawResponse');
        $this->assertPropertyIsReadonly($result, 'metadata');
    }

    #[Test]
    public function it_roundtrips_through_array_conversion(): void
    {
        $original = new ContractCallResult(
            value: ['complex' => 'data'],
            success: true,
            error: null,
            rawResponse: ['raw' => 'response'],
            metadata: ['meta' => 'data']
        );

        $array = $original->toArray();
        $restored = ContractCallResult::fromArray($array);

        $this->assertSame($original->value, $restored->value);
        $this->assertSame($original->success, $restored->success);
        $this->assertSame($original->error, $restored->error);
        $this->assertSame($original->rawResponse, $restored->rawResponse);
        $this->assertSame($original->metadata, $restored->metadata);
    }

    #[Test]
    public function it_handles_various_value_types(): void
    {
        $values = [
            'string' => 'text',
            'integer' => 42,
            'float' => 3.14,
            'boolean' => true,
            'array' => ['key' => 'value'],
            'null' => null,
        ];

        foreach ($values as $name => $value) {
            $result = new ContractCallResult(value: $value);
            $this->assertSame($value, $result->value, "Failed for type: {$name}");
        }
    }

    #[Test]
    public function success_factory_defaults_to_empty_raw_response(): void
    {
        $result = ContractCallResult::success('value');

        $this->assertSame([], $result->rawResponse);
    }

    #[Test]
    public function failure_factory_defaults_to_empty_raw_response(): void
    {
        $result = ContractCallResult::failure('error');

        $this->assertSame([], $result->rawResponse);
    }

    /**
     * Data provider for hasValue tests.
     *
     * @return array<string, array{mixed, bool}>
     */
    public static function hasValueProvider(): array
    {
        return [
            'string value' => ['test', true],
            'integer value' => [42, true],
            'zero' => [0, true],
            'false' => [false, true],
            'empty string' => ['', true],
            'empty array' => [[], true],
            'null value' => [null, false],
        ];
    }

    /**
     * Data provider for array conversion tests.
     *
     * @return array<string, array{mixed, array<mixed>}>
     */
    public static function typeConversionProvider(): array
    {
        return [
            'array value' => [['key' => 'value'], ['key' => 'value']],
            'string value' => ['test', ['test']],
            'integer value' => [42, [42]],
            'null value' => [null, []],
        ];
    }

    /**
     * Data provider for string conversion tests.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function stringConversionProvider(): array
    {
        return [
            'string value' => ['test', 'test'],
            'integer value' => [42, '42'],
            'float value' => [3.14, '3.14'],
            'true value' => [true, '1'],
            'false value' => [false, ''],
            'null value' => [null, ''],
        ];
    }

    /**
     * Data provider for int conversion tests.
     *
     * @return array<string, array{mixed, int}>
     */
    public static function intConversionProvider(): array
    {
        return [
            'integer value' => [42, 42],
            'string number' => ['123', 123],
            'float value' => [3.14, 3],
            'true value' => [true, 1],
            'false value' => [false, 0],
            'null value' => [null, 0],
        ];
    }

    /**
     * Data provider for bool conversion tests.
     *
     * @return array<string, array{mixed, bool}>
     */
    public static function boolConversionProvider(): array
    {
        return [
            'true value' => [true, true],
            'false value' => [false, false],
            'non-empty string' => ['test', true],
            'empty string' => ['', false],
            'positive integer' => [1, true],
            'zero' => [0, false],
            'null value' => [null, false],
        ];
    }
}
