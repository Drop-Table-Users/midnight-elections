<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the ContractCall DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\ContractCall
 */
final class ContractCallTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_required_fields(): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1abc123',
            entrypoint: 'transfer'
        );

        $this->assertInstanceOf(ContractCall::class, $call);
        $this->assertSame('midnight1abc123', $call->contractAddress);
        $this->assertSame('transfer', $call->entrypoint);
        $this->assertSame([], $call->publicArgs);
        $this->assertSame([], $call->privateArgs);
        $this->assertFalse($call->readOnly);
        $this->assertSame([], $call->metadata);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_fields(): void
    {
        $publicArgs = ['to' => 'midnight1xyz', 'amount' => 100];
        $privateArgs = ['secret' => 'private_data'];
        $metadata = ['version' => '1.0'];

        $call = new ContractCall(
            contractAddress: 'midnight1abc123',
            entrypoint: 'transfer',
            publicArgs: $publicArgs,
            privateArgs: $privateArgs,
            readOnly: true,
            metadata: $metadata
        );

        $this->assertSame('midnight1abc123', $call->contractAddress);
        $this->assertSame('transfer', $call->entrypoint);
        $this->assertSame($publicArgs, $call->publicArgs);
        $this->assertSame($privateArgs, $call->privateArgs);
        $this->assertTrue($call->readOnly);
        $this->assertSame($metadata, $call->metadata);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $call = new ContractCall('midnight1abc', 'method');

        $this->assertClassIsFinal($call);
        $this->assertClassIsReadonly($call);
    }

    #[Test]
    public function it_throws_exception_when_contract_address_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract address cannot be empty');

        new ContractCall(
            contractAddress: '',
            entrypoint: 'transfer'
        );
    }

    #[Test]
    public function it_throws_exception_when_entrypoint_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entrypoint cannot be empty');

        new ContractCall(
            contractAddress: 'midnight1abc',
            entrypoint: ''
        );
    }

    #[Test]
    public function it_can_be_created_from_array_with_snake_case_keys(): void
    {
        $data = [
            'contract_address' => 'midnight1contract',
            'entrypoint' => 'execute',
            'public_args' => ['arg1' => 'value1'],
            'private_args' => ['arg2' => 'value2'],
            'read_only' => true,
            'metadata' => ['key' => 'value'],
        ];

        $call = ContractCall::fromArray($data);

        $this->assertSame('midnight1contract', $call->contractAddress);
        $this->assertSame('execute', $call->entrypoint);
        $this->assertSame(['arg1' => 'value1'], $call->publicArgs);
        $this->assertSame(['arg2' => 'value2'], $call->privateArgs);
        $this->assertTrue($call->readOnly);
        $this->assertSame(['key' => 'value'], $call->metadata);
    }

    #[Test]
    public function it_can_be_created_from_array_with_camel_case_keys(): void
    {
        $data = [
            'contractAddress' => 'midnight1contract',
            'entrypoint' => 'execute',
            'publicArgs' => ['arg1' => 'value1'],
            'privateArgs' => ['arg2' => 'value2'],
            'readOnly' => false,
            'metadata' => ['key' => 'value'],
        ];

        $call = ContractCall::fromArray($data);

        $this->assertSame('midnight1contract', $call->contractAddress);
        $this->assertSame('execute', $call->entrypoint);
        $this->assertSame(['arg1' => 'value1'], $call->publicArgs);
        $this->assertSame(['arg2' => 'value2'], $call->privateArgs);
        $this->assertFalse($call->readOnly);
    }

    #[Test]
    public function it_handles_missing_optional_fields_in_fromArray(): void
    {
        $data = [
            'contract_address' => 'midnight1abc',
            'entrypoint' => 'method',
        ];

        $call = ContractCall::fromArray($data);

        $this->assertSame([], $call->publicArgs);
        $this->assertSame([], $call->privateArgs);
        $this->assertFalse($call->readOnly);
        $this->assertSame([], $call->metadata);
    }

    #[Test]
    public function it_can_create_read_only_call(): void
    {
        $args = ['param' => 'value'];
        $call = ContractCall::readOnly('midnight1contract', 'getBalance', $args);

        $this->assertSame('midnight1contract', $call->contractAddress);
        $this->assertSame('getBalance', $call->entrypoint);
        $this->assertSame($args, $call->publicArgs);
        $this->assertSame([], $call->privateArgs);
        $this->assertTrue($call->readOnly);
    }

    #[Test]
    public function it_can_create_write_call(): void
    {
        $publicArgs = ['to' => 'midnight1xyz'];
        $privateArgs = ['amount' => 100];

        $call = ContractCall::write(
            'midnight1contract',
            'transfer',
            $publicArgs,
            $privateArgs
        );

        $this->assertSame('midnight1contract', $call->contractAddress);
        $this->assertSame('transfer', $call->entrypoint);
        $this->assertSame($publicArgs, $call->publicArgs);
        $this->assertSame($privateArgs, $call->privateArgs);
        $this->assertFalse($call->readOnly);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1abc',
            entrypoint: 'transfer',
            publicArgs: ['arg' => 'value'],
            privateArgs: ['secret' => 'data'],
            readOnly: true,
            metadata: ['meta' => 'data']
        );

        $array = $call->toArray();

        $this->assertSame([
            'contract_address' => 'midnight1abc',
            'entrypoint' => 'transfer',
            'public_args' => ['arg' => 'value'],
            'private_args' => ['secret' => 'data'],
            'read_only' => true,
            'metadata' => ['meta' => 'data'],
        ], $array);
    }

    #[Test]
    #[DataProvider('hasPrivateArgsProvider')]
    public function it_detects_private_args(array $privateArgs, bool $expected): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1abc',
            entrypoint: 'method',
            privateArgs: $privateArgs
        );

        $this->assertSame($expected, $call->hasPrivateArgs());
    }

    #[Test]
    #[DataProvider('requiresProofProvider')]
    public function it_determines_if_proof_is_required(
        array $privateArgs,
        bool $readOnly,
        bool $expectedRequiresProof
    ): void {
        $call = new ContractCall(
            contractAddress: 'midnight1abc',
            entrypoint: 'method',
            privateArgs: $privateArgs,
            readOnly: $readOnly
        );

        $this->assertSame($expectedRequiresProof, $call->requiresProof());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $call = new ContractCall('midnight1abc', 'transfer');

        $this->assertPropertyIsReadonly($call, 'contractAddress');
        $this->assertPropertyIsReadonly($call, 'entrypoint');
        $this->assertPropertyIsReadonly($call, 'publicArgs');
        $this->assertPropertyIsReadonly($call, 'privateArgs');
        $this->assertPropertyIsReadonly($call, 'readOnly');
        $this->assertPropertyIsReadonly($call, 'metadata');
    }

    #[Test]
    public function it_roundtrips_through_array_conversion(): void
    {
        $original = new ContractCall(
            contractAddress: 'midnight1test',
            entrypoint: 'execute',
            publicArgs: ['public' => 'data'],
            privateArgs: ['private' => 'secret'],
            readOnly: false,
            metadata: ['version' => '1.0']
        );

        $array = $original->toArray();
        $restored = ContractCall::fromArray($array);

        $this->assertSame($original->contractAddress, $restored->contractAddress);
        $this->assertSame($original->entrypoint, $restored->entrypoint);
        $this->assertSame($original->publicArgs, $restored->publicArgs);
        $this->assertSame($original->privateArgs, $restored->privateArgs);
        $this->assertSame($original->readOnly, $restored->readOnly);
        $this->assertSame($original->metadata, $restored->metadata);
    }

    #[Test]
    public function readOnly_factory_creates_call_without_private_args(): void
    {
        $call = ContractCall::readOnly('midnight1abc', 'getState');

        $this->assertFalse($call->hasPrivateArgs());
        $this->assertFalse($call->requiresProof());
    }

    #[Test]
    public function write_factory_without_private_args_does_not_require_proof(): void
    {
        $call = ContractCall::write('midnight1abc', 'publicMethod', ['arg' => 'value']);

        $this->assertFalse($call->hasPrivateArgs());
        $this->assertFalse($call->requiresProof());
    }

    #[Test]
    public function write_factory_with_private_args_requires_proof(): void
    {
        $call = ContractCall::write(
            'midnight1abc',
            'privateMethod',
            ['public' => 'arg'],
            ['private' => 'arg']
        );

        $this->assertTrue($call->hasPrivateArgs());
        $this->assertTrue($call->requiresProof());
    }

    #[Test]
    public function fromArray_prefers_snake_case_over_camel_case(): void
    {
        $data = [
            'contract_address' => 'midnight1snake',
            'contractAddress' => 'midnight1camel',
            'entrypoint' => 'test',
        ];

        $call = ContractCall::fromArray($data);

        $this->assertSame('midnight1snake', $call->contractAddress);
    }

    /**
     * Data provider for hasPrivateArgs tests.
     *
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function hasPrivateArgsProvider(): array
    {
        return [
            'with private args' => [['secret' => 'data'], true],
            'without private args' => [[], false],
            'with multiple private args' => [['secret1' => 'data1', 'secret2' => 'data2'], true],
        ];
    }

    /**
     * Data provider for requiresProof tests.
     *
     * @return array<string, array{array<string, mixed>, bool, bool}>
     */
    public static function requiresProofProvider(): array
    {
        return [
            'write with private args' => [['secret' => 'data'], false, true],
            'write without private args' => [[], false, false],
            'read with private args' => [['secret' => 'data'], true, false],
            'read without private args' => [[], true, false],
        ];
    }
}
