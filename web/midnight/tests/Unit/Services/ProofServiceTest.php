<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Services;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\Exceptions\ProofFailedException;
use VersionTwo\Midnight\Services\ProofService;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the ProofService class.
 *
 * @covers \VersionTwo\Midnight\Services\ProofService
 */
final class ProofServiceTest extends TestCase
{
    private MidnightClient&MockInterface $client;
    private ProofService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(MidnightClient::class);
        $this->service = new ProofService($this->client);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ProofService::class, $this->service);
    }

    #[Test]
    public function it_generates_proof_for_contract_successfully(): void
    {
        $contractName = 'VotingContract';
        $entrypoint = 'vote';
        $publicInputs = ['vote_id' => '123'];
        $privateInputs = ['choice' => 'yes'];

        $mockProof = new ProofResponse(
            proof: 'proof_data_here',
            publicOutputs: ['verified' => true],
            generationTime: 1.5
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->withArgs(function (ProofRequest $request) use ($contractName, $entrypoint, $publicInputs, $privateInputs) {
                return $request->contractName === $contractName
                    && $request->entrypoint === $entrypoint
                    && $request->publicInputs === $publicInputs
                    && $request->privateInputs === $privateInputs;
            })
            ->andReturn($mockProof);

        $proof = $this->service->generateForContract(
            $contractName,
            $entrypoint,
            $publicInputs,
            $privateInputs
        );

        $this->assertInstanceOf(ProofResponse::class, $proof);
        $this->assertSame('proof_data_here', $proof->proof);
        $this->assertSame(1.5, $proof->generationTime);
    }

    #[Test]
    public function it_throws_exception_when_contract_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract name cannot be empty');

        $this->service->generateForContract('', 'entrypoint', [], ['private' => 'data']);
    }

    #[Test]
    public function it_throws_exception_when_entrypoint_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entrypoint cannot be empty');

        $this->service->generateForContract('Contract', '', [], ['private' => 'data']);
    }

    #[Test]
    public function it_throws_exception_when_contract_name_is_too_long(): void
    {
        $longName = str_repeat('a', 256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract name is too long');

        $this->service->generateForContract($longName, 'entrypoint', [], ['private' => 'data']);
    }

    #[Test]
    public function it_throws_exception_when_entrypoint_is_too_long(): void
    {
        $longEntrypoint = str_repeat('a', 256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entrypoint name is too long');

        $this->service->generateForContract('Contract', $longEntrypoint, [], ['private' => 'data']);
    }

    #[Test]
    public function it_throws_exception_when_private_inputs_are_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Private inputs cannot be empty');

        $this->service->generateForContract('Contract', 'entrypoint', ['public' => 'data'], []);
    }

    #[Test]
    public function it_throws_exception_when_public_inputs_are_not_associative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Public inputs must be an associative array');

        $this->service->generateForContract(
            'Contract',
            'entrypoint',
            ['sequential', 'array'],
            ['private' => 'data']
        );
    }

    #[Test]
    public function it_throws_exception_when_private_inputs_are_not_associative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Private inputs must be an associative array');

        $this->service->generateForContract(
            'Contract',
            'entrypoint',
            ['public' => 'data'],
            ['sequential', 'array']
        );
    }

    #[Test]
    public function it_accepts_empty_public_inputs(): void
    {
        $mockProof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->andReturn($mockProof);

        $proof = $this->service->generateForContract(
            'Contract',
            'entrypoint',
            [],
            ['private' => 'data']
        );

        $this->assertInstanceOf(ProofResponse::class, $proof);
    }

    #[Test]
    public function it_throws_exception_when_proof_is_empty(): void
    {
        $emptyProof = new ProofResponse(
            proof: '',
            publicOutputs: [],
            generationTime: 0.0
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->andReturn($emptyProof);

        $this->expectException(ProofFailedException::class);
        $this->expectExceptionMessage('Proof generation returned empty proof');

        $this->service->generateForContract(
            'Contract',
            'entrypoint',
            [],
            ['private' => 'data']
        );
    }

    #[Test]
    public function it_rethrows_proof_failed_exception(): void
    {
        $this->client->shouldReceive('generateProof')
            ->once()
            ->andThrow(ProofFailedException::generationFailed('Contract', 'entrypoint', 'Failed'));

        $this->expectException(ProofFailedException::class);

        $this->service->generateForContract(
            'Contract',
            'entrypoint',
            [],
            ['private' => 'data']
        );
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_proof_failed_exception(): void
    {
        $this->client->shouldReceive('generateProof')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(ProofFailedException::class);

        $this->service->generateForContract(
            'Contract',
            'entrypoint',
            [],
            ['private' => 'data']
        );
    }

    #[Test]
    public function it_validates_proof_request_and_returns_errors(): void
    {
        $errors = $this->service->validateProofRequest(
            '',
            '',
            [],
            []
        );

        $this->assertArrayHasKey('contract_name', $errors);
        $this->assertArrayHasKey('entrypoint', $errors);
        $this->assertArrayHasKey('inputs', $errors);
    }

    #[Test]
    public function it_validates_proof_request_and_returns_empty_for_valid_input(): void
    {
        $errors = $this->service->validateProofRequest(
            'ValidContract',
            'validEntrypoint',
            ['public' => 'data'],
            ['private' => 'data']
        );

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_validates_contract_name_length(): void
    {
        $longName = str_repeat('a', 256);

        $errors = $this->service->validateProofRequest(
            $longName,
            'entrypoint',
            [],
            ['private' => 'data']
        );

        $this->assertArrayHasKey('contract_name', $errors);
        $this->assertStringContainsString('too long', $errors['contract_name']);
    }

    #[Test]
    public function it_validates_entrypoint_length(): void
    {
        $longEntrypoint = str_repeat('a', 256);

        $errors = $this->service->validateProofRequest(
            'Contract',
            $longEntrypoint,
            [],
            ['private' => 'data']
        );

        $this->assertArrayHasKey('entrypoint', $errors);
        $this->assertStringContainsString('too long', $errors['entrypoint']);
    }

    #[Test]
    public function it_validates_inputs_are_associative(): void
    {
        $errors = $this->service->validateProofRequest(
            'Contract',
            'entrypoint',
            ['sequential', 'array'],
            ['private' => 'data']
        );

        $this->assertArrayHasKey('inputs', $errors);
    }

    #[Test]
    public function it_generates_proof_with_public_outputs(): void
    {
        $mockProof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: ['output1' => 'value1', 'output2' => 'value2'],
            generationTime: 2.0
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->andReturn($mockProof);

        $proof = $this->service->generateForContract(
            'Contract',
            'entrypoint',
            ['public' => 'input'],
            ['private' => 'input']
        );

        $this->assertTrue($proof->hasPublicOutputs());
        $this->assertCount(2, $proof->publicOutputs);
    }

    #[Test]
    #[DataProvider('validContractNamesProvider')]
    public function it_accepts_valid_contract_names(string $contractName): void
    {
        $mockProof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->andReturn($mockProof);

        $proof = $this->service->generateForContract(
            $contractName,
            'entrypoint',
            [],
            ['private' => 'data']
        );

        $this->assertInstanceOf(ProofResponse::class, $proof);
    }

    #[Test]
    #[DataProvider('validEntrypointsProvider')]
    public function it_accepts_valid_entrypoints(string $entrypoint): void
    {
        $mockProof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->andReturn($mockProof);

        $proof = $this->service->generateForContract(
            'Contract',
            $entrypoint,
            [],
            ['private' => 'data']
        );

        $this->assertInstanceOf(ProofResponse::class, $proof);
    }

    #[Test]
    public function it_handles_complex_private_inputs(): void
    {
        $complexPrivateInputs = [
            'nested' => ['data' => ['value' => 123]],
            'array' => ['item1', 'item2', 'item3'],
            'string' => 'secret_value',
            'number' => 42,
        ];

        $mockProof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->client->shouldReceive('generateProof')
            ->once()
            ->withArgs(function (ProofRequest $request) use ($complexPrivateInputs) {
                return $request->privateInputs === $complexPrivateInputs;
            })
            ->andReturn($mockProof);

        $proof = $this->service->generateForContract(
            'Contract',
            'entrypoint',
            [],
            $complexPrivateInputs
        );

        $this->assertInstanceOf(ProofResponse::class, $proof);
    }

    /**
     * Data provider for valid contract names.
     *
     * @return array<string, array{string}>
     */
    public static function validContractNamesProvider(): array
    {
        return [
            'simple name' => ['VotingContract'],
            'name with underscore' => ['Voting_Contract'],
            'name with number' => ['VotingContract123'],
            'single char' => ['V'],
            'max length' => [str_repeat('a', 255)],
        ];
    }

    /**
     * Data provider for valid entrypoints.
     *
     * @return array<string, array{string}>
     */
    public static function validEntrypointsProvider(): array
    {
        return [
            'simple name' => ['vote'],
            'name with underscore' => ['cast_vote'],
            'name with number' => ['vote123'],
            'single char' => ['v'],
            'camelCase' => ['castVote'],
        ];
    }
}
