<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the ProofResponse DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\ProofResponse
 */
final class ProofResponseTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_required_fields(): void
    {
        $response = new ProofResponse(proof: '0xabc123def456');

        $this->assertInstanceOf(ProofResponse::class, $response);
        $this->assertSame('0xabc123def456', $response->proof);
        $this->assertSame([], $response->publicOutputs);
        $this->assertFalse($response->verified);
        $this->assertNull($response->generationTime);
        $this->assertSame([], $response->metadata);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_fields(): void
    {
        $publicOutputs = ['output1' => 'value1'];
        $metadata = ['server' => 'proof-server-1'];

        $response = new ProofResponse(
            proof: '0xproof123',
            publicOutputs: $publicOutputs,
            verified: true,
            generationTime: 1.234,
            metadata: $metadata
        );

        $this->assertSame('0xproof123', $response->proof);
        $this->assertSame($publicOutputs, $response->publicOutputs);
        $this->assertTrue($response->verified);
        $this->assertSame(1.234, $response->generationTime);
        $this->assertSame($metadata, $response->metadata);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $response = new ProofResponse('0xproof');

        $this->assertClassIsFinal($response);
        $this->assertClassIsReadonly($response);
    }

    #[Test]
    public function it_can_be_created_from_array_with_snake_case_keys(): void
    {
        $data = [
            'proof' => '0xabc',
            'public_outputs' => ['key' => 'value'],
            'verified' => true,
            'generation_time' => 2.5,
            'metadata' => ['info' => 'data'],
        ];

        $response = ProofResponse::fromArray($data);

        $this->assertSame('0xabc', $response->proof);
        $this->assertSame(['key' => 'value'], $response->publicOutputs);
        $this->assertTrue($response->verified);
        $this->assertSame(2.5, $response->generationTime);
        $this->assertSame(['info' => 'data'], $response->metadata);
    }

    #[Test]
    public function it_can_be_created_from_array_with_camel_case_keys(): void
    {
        $data = [
            'proof' => '0xdef',
            'publicOutputs' => ['output' => 'result'],
            'verified' => false,
            'generationTime' => 3.14,
            'metadata' => ['server' => 'test'],
        ];

        $response = ProofResponse::fromArray($data);

        $this->assertSame('0xdef', $response->proof);
        $this->assertSame(['output' => 'result'], $response->publicOutputs);
        $this->assertFalse($response->verified);
        $this->assertSame(3.14, $response->generationTime);
    }

    #[Test]
    public function it_handles_missing_optional_fields_in_fromArray(): void
    {
        $data = ['proof' => '0xminimal'];

        $response = ProofResponse::fromArray($data);

        $this->assertSame([], $response->publicOutputs);
        $this->assertFalse($response->verified);
        $this->assertNull($response->generationTime);
        $this->assertSame([], $response->metadata);
    }

    #[Test]
    public function it_prefers_snake_case_over_camel_case_in_fromArray(): void
    {
        $data = [
            'proof' => '0xtest',
            'public_outputs' => ['snake' => 'case'],
            'publicOutputs' => ['camel' => 'case'],
        ];

        $response = ProofResponse::fromArray($data);

        $this->assertSame(['snake' => 'case'], $response->publicOutputs);
    }

    #[Test]
    public function it_handles_string_generation_time_in_fromArray(): void
    {
        $data = [
            'proof' => '0xtest',
            'generation_time' => '1.5',
        ];

        $response = ProofResponse::fromArray($data);

        $this->assertSame(1.5, $response->generationTime);
    }

    #[Test]
    public function it_handles_integer_generation_time_in_fromArray(): void
    {
        $data = [
            'proof' => '0xtest',
            'generation_time' => 5,
        ];

        $response = ProofResponse::fromArray($data);

        $this->assertSame(5.0, $response->generationTime);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $response = new ProofResponse(
            proof: '0xabc123',
            publicOutputs: ['output' => 'value'],
            verified: true,
            generationTime: 2.5,
            metadata: ['meta' => 'data']
        );

        $array = $response->toArray();

        $this->assertSame([
            'proof' => '0xabc123',
            'public_outputs' => ['output' => 'value'],
            'verified' => true,
            'generation_time' => 2.5,
            'metadata' => ['meta' => 'data'],
        ], $array);
    }

    #[Test]
    #[DataProvider('emptyProofProvider')]
    public function it_detects_empty_proof(string $proof, bool $expectedEmpty): void
    {
        $response = new ProofResponse(proof: $proof);

        $this->assertSame($expectedEmpty, $response->isEmpty());
    }

    #[Test]
    #[DataProvider('hasPublicOutputsProvider')]
    public function it_detects_public_outputs(array $outputs, bool $expected): void
    {
        $response = new ProofResponse(
            proof: '0xtest',
            publicOutputs: $outputs
        );

        $this->assertSame($expected, $response->hasPublicOutputs());
    }

    #[Test]
    public function it_gets_specific_public_output(): void
    {
        $response = new ProofResponse(
            proof: '0xtest',
            publicOutputs: ['key1' => 'value1', 'key2' => 'value2']
        );

        $this->assertSame('value1', $response->getPublicOutput('key1'));
        $this->assertSame('value2', $response->getPublicOutput('key2'));
    }

    #[Test]
    public function it_returns_default_for_missing_public_output(): void
    {
        $response = new ProofResponse(
            proof: '0xtest',
            publicOutputs: ['key1' => 'value1']
        );

        $this->assertNull($response->getPublicOutput('missing'));
        $this->assertSame('default', $response->getPublicOutput('missing', 'default'));
    }

    #[Test]
    #[DataProvider('hexConversionProvider')]
    public function it_converts_proof_to_hex(string $proof, string $expectedHex): void
    {
        $response = new ProofResponse(proof: $proof);

        $this->assertSame($expectedHex, $response->asHex());
    }

    #[Test]
    #[DataProvider('base64ConversionProvider')]
    public function it_converts_proof_to_base64(string $proof, string $expectedBase64): void
    {
        $response = new ProofResponse(proof: $proof);

        $this->assertSame($expectedBase64, $response->asBase64());
    }

    #[Test]
    public function it_formats_generation_time(): void
    {
        $response = new ProofResponse(
            proof: '0xtest',
            generationTime: 1.23456
        );

        $this->assertSame('1.235s', $response->getFormattedGenerationTime());
    }

    #[Test]
    public function it_formats_generation_time_with_custom_decimals(): void
    {
        $response = new ProofResponse(
            proof: '0xtest',
            generationTime: 1.23456
        );

        $this->assertSame('1.2s', $response->getFormattedGenerationTime(1));
        $this->assertSame('1.23s', $response->getFormattedGenerationTime(2));
        $this->assertSame('1.23456s', $response->getFormattedGenerationTime(5));
    }

    #[Test]
    public function it_returns_null_formatted_time_when_generation_time_is_null(): void
    {
        $response = new ProofResponse(proof: '0xtest');

        $this->assertNull($response->getFormattedGenerationTime());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $response = new ProofResponse('0xtest');

        $this->assertPropertyIsReadonly($response, 'proof');
        $this->assertPropertyIsReadonly($response, 'publicOutputs');
        $this->assertPropertyIsReadonly($response, 'verified');
        $this->assertPropertyIsReadonly($response, 'generationTime');
        $this->assertPropertyIsReadonly($response, 'metadata');
    }

    #[Test]
    public function it_roundtrips_through_array_conversion(): void
    {
        $original = new ProofResponse(
            proof: '0xdeadbeef',
            publicOutputs: ['result' => 'success'],
            verified: true,
            generationTime: 5.5,
            metadata: ['server' => 'proof-1']
        );

        $array = $original->toArray();
        $restored = ProofResponse::fromArray($array);

        $this->assertSame($original->proof, $restored->proof);
        $this->assertSame($original->publicOutputs, $restored->publicOutputs);
        $this->assertSame($original->verified, $restored->verified);
        $this->assertSame($original->generationTime, $restored->generationTime);
        $this->assertSame($original->metadata, $restored->metadata);
    }

    #[Test]
    public function it_handles_empty_proof_string(): void
    {
        $response = new ProofResponse(proof: '');

        $this->assertTrue($response->isEmpty());
        $this->assertSame('', $response->proof);
    }

    #[Test]
    public function it_preserves_proof_format(): void
    {
        $proofs = [
            '0xabcdef',
            'abcdef',
            'base64EncodedProof==',
            'UPPERCASE0X',
        ];

        foreach ($proofs as $proof) {
            $response = new ProofResponse(proof: $proof);
            $this->assertSame($proof, $response->proof);
        }
    }

    #[Test]
    public function fromArray_handles_empty_array(): void
    {
        $response = ProofResponse::fromArray([]);

        $this->assertSame('', $response->proof);
        $this->assertSame([], $response->publicOutputs);
        $this->assertFalse($response->verified);
        $this->assertNull($response->generationTime);
    }

    #[Test]
    public function it_handles_complex_public_outputs(): void
    {
        $complexOutputs = [
            'simple' => 'value',
            'nested' => ['key' => 'value'],
            'array' => [1, 2, 3],
            'mixed' => ['str' => 'text', 'num' => 42],
        ];

        $response = new ProofResponse(
            proof: '0xtest',
            publicOutputs: $complexOutputs
        );

        $this->assertSame($complexOutputs, $response->publicOutputs);
        foreach ($complexOutputs as $key => $value) {
            $this->assertSame($value, $response->getPublicOutput($key));
        }
    }

    #[Test]
    public function it_handles_zero_generation_time(): void
    {
        $response = new ProofResponse(
            proof: '0xtest',
            generationTime: 0.0
        );

        $this->assertSame(0.0, $response->generationTime);
        $this->assertSame('0.000s', $response->getFormattedGenerationTime());
    }

    /**
     * Data provider for empty proof tests.
     *
     * @return array<string, array{string, bool}>
     */
    public static function emptyProofProvider(): array
    {
        return [
            'empty string' => ['', true],
            'non-empty hex' => ['0xabc', false],
            'non-empty base64' => ['YWJj', false],
            'single char' => ['a', false],
        ];
    }

    /**
     * Data provider for hasPublicOutputs tests.
     *
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function hasPublicOutputsProvider(): array
    {
        return [
            'with outputs' => [['key' => 'value'], true],
            'without outputs' => [[], false],
            'multiple outputs' => [['k1' => 'v1', 'k2' => 'v2'], true],
        ];
    }

    /**
     * Data provider for hex conversion tests.
     *
     * @return array<string, array{string, string}>
     */
    public static function hexConversionProvider(): array
    {
        return [
            'already hex lowercase' => ['abcdef123456', 'abcdef123456'],
            'already hex uppercase' => ['ABCDEF123456', 'ABCDEF123456'],
            'already hex mixed' => ['AbCdEf123456', 'AbCdEf123456'],
            'base64 to hex' => [base64_encode('test'), bin2hex('test')],
        ];
    }

    /**
     * Data provider for base64 conversion tests.
     *
     * @return array<string, array{string, string}>
     */
    public static function base64ConversionProvider(): array
    {
        return [
            'hex to base64 even chars' => ['abcd', base64_encode(hex2bin('abcd'))],
            'hex to base64 full' => ['deadbeef', base64_encode(hex2bin('deadbeef'))],
            'already base64' => [base64_encode('test'), base64_encode('test')],
        ];
    }
}
