<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use VersionTwo\Midnight\Exceptions\MidnightException;

#[CoversClass(MidnightException::class)]
final class MidnightExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_message(): void
    {
        $exception = new MidnightException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters(): void
    {
        $previous = new RuntimeException('Previous error');
        $context = ['key' => 'value', 'count' => 42];

        $exception = new MidnightException('Test message', 500, $previous, $context);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
    }

    #[Test]
    public function it_stores_empty_context_by_default(): void
    {
        $exception = new MidnightException('Test message');

        $this->assertIsArray($exception->getContext());
        $this->assertEmpty($exception->getContext());
    }

    #[Test]
    #[DataProvider('contextDataProvider')]
    public function it_can_store_various_context_types(array $context): void
    {
        $exception = new MidnightException('Test', 0, null, $context);

        $this->assertSame($context, $exception->getContext());
    }

    #[Test]
    public function it_can_be_created_with_context_factory_method(): void
    {
        $context = [
            'user_id' => 123,
            'action' => 'contract_call',
            'timestamp' => '2025-11-15T12:00:00Z',
        ];

        $exception = MidnightException::withContext('Operation failed', $context);

        $this->assertInstanceOf(MidnightException::class, $exception);
        $this->assertSame('Operation failed', $exception->getMessage());
        $this->assertSame($context, $exception->getContext());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    #[Test]
    public function it_can_be_created_with_empty_context_using_factory_method(): void
    {
        $exception = MidnightException::withContext('Operation failed');

        $this->assertSame('Operation failed', $exception->getMessage());
        $this->assertSame([], $exception->getContext());
    }

    #[Test]
    public function it_can_be_created_from_previous_exception(): void
    {
        $previous = new RuntimeException('Network timeout');
        $context = ['url' => 'https://api.example.com', 'timeout' => 30];

        $exception = MidnightException::fromPrevious('Request failed', $previous, $context);

        $this->assertInstanceOf(MidnightException::class, $exception);
        $this->assertSame('Request failed', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
        $this->assertSame(0, $exception->getCode());
    }

    #[Test]
    public function it_can_be_created_from_previous_exception_without_context(): void
    {
        $previous = new RuntimeException('Database error');

        $exception = MidnightException::fromPrevious('Operation failed', $previous);

        $this->assertSame('Operation failed', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    #[Test]
    public function it_preserves_exception_chain(): void
    {
        $root = new RuntimeException('Root cause');
        $middle = new MidnightException('Middle error', 0, $root);
        $top = MidnightException::fromPrevious('Top level error', $middle);

        $this->assertSame($middle, $top->getPrevious());
        $this->assertSame($root, $top->getPrevious()?->getPrevious());
    }

    #[Test]
    public function it_can_be_caught_as_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test error');

        throw new MidnightException('Test error');
    }

    #[Test]
    public function it_can_be_caught_as_throwable(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Test error');

        throw new MidnightException('Test error');
    }

    #[Test]
    #[DataProvider('messageDataProvider')]
    public function it_handles_various_message_formats(string $message): void
    {
        $exception = new MidnightException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    #[Test]
    public function it_can_be_serialized(): void
    {
        $exception = new MidnightException('Test message', 100, null, ['key' => 'value']);

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(MidnightException::class, $unserialized);
        $this->assertSame('Test message', $unserialized->getMessage());
        $this->assertSame(100, $unserialized->getCode());
    }

    #[Test]
    public function factory_methods_return_static_type(): void
    {
        $withContext = MidnightException::withContext('Test');
        $fromPrevious = MidnightException::fromPrevious('Test', new RuntimeException());

        $this->assertInstanceOf(MidnightException::class, $withContext);
        $this->assertInstanceOf(MidnightException::class, $fromPrevious);
    }

    public static function contextDataProvider(): array
    {
        return [
            'simple string values' => [
                ['key' => 'value', 'name' => 'test'],
            ],
            'numeric values' => [
                ['count' => 42, 'rate' => 3.14, 'negative' => -10],
            ],
            'boolean values' => [
                ['enabled' => true, 'disabled' => false],
            ],
            'null values' => [
                ['optional' => null],
            ],
            'nested arrays' => [
                ['data' => ['nested' => ['value' => 123]]],
            ],
            'mixed types' => [
                [
                    'string' => 'text',
                    'int' => 42,
                    'float' => 3.14,
                    'bool' => true,
                    'null' => null,
                    'array' => [1, 2, 3],
                ],
            ],
        ];
    }

    public static function messageDataProvider(): array
    {
        return [
            'simple message' => ['Simple error message'],
            'message with special characters' => ['Error: Unable to connect! (retry: 3)'],
            'message with unicode' => ['Operation failed: 操作失败 🚫'],
            'empty message' => [''],
            'multiline message' => ["Error on line 1\nError on line 2"],
            'message with quotes' => ["Can't process \"quoted\" value"],
        ];
    }
}
