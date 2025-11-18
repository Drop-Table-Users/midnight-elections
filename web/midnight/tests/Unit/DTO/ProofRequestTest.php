<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the ProofRequest DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\ProofRequest
 */
final class ProofRequestTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_required_fields(): void
    {
        $request = new ProofRequest(
            contractName: 'VotingContract',
            entrypoint: 'vote'
        );

        $this->assertInstanceOf(ProofRequest::class, $request);
        $this->assertSame('VotingContract', $request->contractName);
        $this->assertSame('vote', $request->entrypoint);
        $this->assertSame([], $request->publicInputs);
        $this->assertSame([], $request->privateInputs);
        $this->assertNull($request->circuitPath);
        $this->assertSame([], $request->metadata);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_fields(): void
    {
        $publicInputs = ['choice' => 'yes'];
        $privateInputs = ['voter_id' => '123'];
        $metadata = ['version' => '1.0'];

        $request = new ProofRequest(
            contractName: 'VotingContract',
            entrypoint: 'vote',
            publicInputs: $publicInputs,
            privateInputs: $privateInputs,
            circuitPath: '/path/to/circuit.wasm',
            metadata: $metadata
        );

        $this->assertSame('VotingContract', $request->contractName);
        $this->assertSame('vote', $request->entrypoint);
        $this->assertSame($publicInputs, $request->publicInputs);
        $this->assertSame($privateInputs, $request->privateInputs);
        $this->assertSame('/path/to/circuit.wasm', $request->circuitPath);
        $this->assertSame($metadata, $request->metadata);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $request = new ProofRequest('Contract', 'method');

        $this->assertClassIsFinal($request);
        $this->assertClassIsReadonly($request);
    }

    #[Test]
    public function it_throws_exception_when_contract_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract name cannot be empty');

        new ProofRequest(
            contractName: '',
            entrypoint: 'vote'
        );
    }

    #[Test]
    public function it_throws_exception_when_entrypoint_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entrypoint cannot be empty');

        new ProofRequest(
            contractName: 'Contract',
            entrypoint: ''
        );
    }

    #[Test]
    public function it_can_be_created_from_array_with_snake_case_keys(): void
    {
        $data = [
            'contract_name' => 'TestContract',
            'entrypoint' => 'execute',
            'public_inputs' => ['input1' => 'value1'],
            'private_inputs' => ['input2' => 'value2'],
            'circuit_path' => '/circuits/test.wasm',
            'metadata' => ['key' => 'value'],
        ];

        $request = ProofRequest::fromArray($data);

        $this->assertSame('TestContract', $request->contractName);
        $this->assertSame('execute', $request->entrypoint);
        $this->assertSame(['input1' => 'value1'], $request->publicInputs);
        $this->assertSame(['input2' => 'value2'], $request->privateInputs);
        $this->assertSame('/circuits/test.wasm', $request->circuitPath);
        $this->assertSame(['key' => 'value'], $request->metadata);
    }

    #[Test]
    public function it_can_be_created_from_array_with_camel_case_keys(): void
    {
        $data = [
            'contractName' => 'TestContract',
            'entrypoint' => 'execute',
            'publicInputs' => ['input1' => 'value1'],
            'privateInputs' => ['input2' => 'value2'],
            'circuitPath' => '/circuits/test.wasm',
            'metadata' => ['key' => 'value'],
        ];

        $request = ProofRequest::fromArray($data);

        $this->assertSame('TestContract', $request->contractName);
        $this->assertSame('execute', $request->entrypoint);
        $this->assertSame(['input1' => 'value1'], $request->publicInputs);
        $this->assertSame(['input2' => 'value2'], $request->privateInputs);
        $this->assertSame('/circuits/test.wasm', $request->circuitPath);
    }

    #[Test]
    public function it_handles_missing_optional_fields_in_fromArray(): void
    {
        $data = [
            'contract_name' => 'TestContract',
            'entrypoint' => 'execute',
        ];

        $request = ProofRequest::fromArray($data);

        $this->assertSame([], $request->publicInputs);
        $this->assertSame([], $request->privateInputs);
        $this->assertNull($request->circuitPath);
        $this->assertSame([], $request->metadata);
    }

    #[Test]
    public function it_prefers_snake_case_over_camel_case_in_fromArray(): void
    {
        $data = [
            'contract_name' => 'SnakeCase',
            'contractName' => 'CamelCase',
            'entrypoint' => 'test',
        ];

        $request = ProofRequest::fromArray($data);

        $this->assertSame('SnakeCase', $request->contractName);
    }

    #[Test]
    public function it_can_be_created_from_contract_call(): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1abc',
            entrypoint: 'transfer',
            publicArgs: ['to' => 'midnight1xyz'],
            privateArgs: ['amount' => 100]
        );

        $request = ProofRequest::fromContractCall($call, 'TokenContract');

        $this->assertSame('TokenContract', $request->contractName);
        $this->assertSame('transfer', $request->entrypoint);
        $this->assertSame(['to' => 'midnight1xyz'], $request->publicInputs);
        $this->assertSame(['amount' => 100], $request->privateInputs);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $request = new ProofRequest(
            contractName: 'TestContract',
            entrypoint: 'method',
            publicInputs: ['public' => 'input'],
            privateInputs: ['private' => 'input'],
            circuitPath: '/path/circuit.wasm',
            metadata: ['meta' => 'data']
        );

        $array = $request->toArray();

        $this->assertSame([
            'contract_name' => 'TestContract',
            'entrypoint' => 'method',
            'public_inputs' => ['public' => 'input'],
            'private_inputs' => ['private' => 'input'],
            'circuit_path' => '/path/circuit.wasm',
            'metadata' => ['meta' => 'data'],
        ], $array);
    }

    #[Test]
    #[DataProvider('hasPrivateInputsProvider')]
    public function it_detects_private_inputs(array $privateInputs, bool $expected): void
    {
        $request = new ProofRequest(
            contractName: 'Contract',
            entrypoint: 'method',
            privateInputs: $privateInputs
        );

        $this->assertSame($expected, $request->hasPrivateInputs());
    }

    #[Test]
    #[DataProvider('hasCircuitPathProvider')]
    public function it_detects_circuit_path(?string $circuitPath, bool $expected): void
    {
        $request = new ProofRequest(
            contractName: 'Contract',
            entrypoint: 'method',
            circuitPath: $circuitPath
        );

        $this->assertSame($expected, $request->hasCircuitPath());
    }

    #[Test]
    public function it_can_create_new_instance_with_additional_metadata(): void
    {
        $original = new ProofRequest(
            contractName: 'Contract',
            entrypoint: 'method',
            metadata: ['key1' => 'value1']
        );

        $modified = $original->withMetadata(['key2' => 'value2']);

        // Original should be unchanged
        $this->assertSame(['key1' => 'value1'], $original->metadata);

        // Modified should have merged metadata
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $modified->metadata);
    }

    #[Test]
    public function withMetadata_merges_metadata_correctly(): void
    {
        $request = new ProofRequest(
            contractName: 'Contract',
            entrypoint: 'method',
            metadata: ['existing' => 'value', 'override' => 'old']
        );

        $modified = $request->withMetadata([
            'override' => 'new',
            'additional' => 'data',
        ]);

        $this->assertSame([
            'existing' => 'value',
            'override' => 'new',
            'additional' => 'data',
        ], $modified->metadata);
    }

    #[Test]
    public function withMetadata_preserves_other_properties(): void
    {
        $original = new ProofRequest(
            contractName: 'Contract',
            entrypoint: 'method',
            publicInputs: ['public' => 'data'],
            privateInputs: ['private' => 'data'],
            circuitPath: '/path/circuit.wasm',
            metadata: ['key' => 'value']
        );

        $modified = $original->withMetadata(['new' => 'metadata']);

        $this->assertSame($original->contractName, $modified->contractName);
        $this->assertSame($original->entrypoint, $modified->entrypoint);
        $this->assertSame($original->publicInputs, $modified->publicInputs);
        $this->assertSame($original->privateInputs, $modified->privateInputs);
        $this->assertSame($original->circuitPath, $modified->circuitPath);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $request = new ProofRequest('Contract', 'method');

        $this->assertPropertyIsReadonly($request, 'contractName');
        $this->assertPropertyIsReadonly($request, 'entrypoint');
        $this->assertPropertyIsReadonly($request, 'publicInputs');
        $this->assertPropertyIsReadonly($request, 'privateInputs');
        $this->assertPropertyIsReadonly($request, 'circuitPath');
        $this->assertPropertyIsReadonly($request, 'metadata');
    }

    #[Test]
    public function it_roundtrips_through_array_conversion(): void
    {
        $original = new ProofRequest(
            contractName: 'TestContract',
            entrypoint: 'execute',
            publicInputs: ['public' => 'data'],
            privateInputs: ['private' => 'data'],
            circuitPath: '/circuits/test.wasm',
            metadata: ['version' => '1.0']
        );

        $array = $original->toArray();
        $restored = ProofRequest::fromArray($array);

        $this->assertSame($original->contractName, $restored->contractName);
        $this->assertSame($original->entrypoint, $restored->entrypoint);
        $this->assertSame($original->publicInputs, $restored->publicInputs);
        $this->assertSame($original->privateInputs, $restored->privateInputs);
        $this->assertSame($original->circuitPath, $restored->circuitPath);
        $this->assertSame($original->metadata, $restored->metadata);
    }

    #[Test]
    public function fromContractCall_creates_request_without_circuit_path(): void
    {
        $call = new ContractCall('midnight1abc', 'method');
        $request = ProofRequest::fromContractCall($call, 'Contract');

        $this->assertNull($request->circuitPath);
        $this->assertFalse($request->hasCircuitPath());
    }

    #[Test]
    public function fromContractCall_maps_args_to_inputs_correctly(): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1abc',
            entrypoint: 'complexMethod',
            publicArgs: ['arg1' => 'value1', 'arg2' => 'value2'],
            privateArgs: ['secret1' => 'private1', 'secret2' => 'private2']
        );

        $request = ProofRequest::fromContractCall($call, 'MyContract');

        $this->assertSame($call->publicArgs, $request->publicInputs);
        $this->assertSame($call->privateArgs, $request->privateInputs);
    }

    #[Test]
    public function it_handles_empty_inputs(): void
    {
        $request = new ProofRequest(
            contractName: 'Contract',
            entrypoint: 'method',
            publicInputs: [],
            privateInputs: []
        );

        $this->assertFalse($request->hasPrivateInputs());
        $this->assertSame([], $request->publicInputs);
        $this->assertSame([], $request->privateInputs);
    }

    /**
     * Data provider for hasPrivateInputs tests.
     *
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function hasPrivateInputsProvider(): array
    {
        return [
            'with private inputs' => [['secret' => 'data'], true],
            'without private inputs' => [[], false],
            'with multiple inputs' => [['secret1' => 'data1', 'secret2' => 'data2'], true],
        ];
    }

    /**
     * Data provider for hasCircuitPath tests.
     *
     * @return array<string, array{string|null, bool}>
     */
    public static function hasCircuitPathProvider(): array
    {
        return [
            'with circuit path' => ['/path/to/circuit.wasm', true],
            'without circuit path' => [null, false],
        ];
    }
}
