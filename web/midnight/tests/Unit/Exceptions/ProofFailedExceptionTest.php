<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use VersionTwo\Midnight\Exceptions\MidnightException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;

#[CoversClass(ProofFailedException::class)]
final class ProofFailedExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_midnight_exception(): void
    {
        $exception = new ProofFailedException('Test');

        $this->assertInstanceOf(MidnightException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_creates_generation_failed_exception(): void
    {
        $contractName = 'TokenContract';
        $entrypoint = 'transfer';
        $reason = 'Invalid witness data';

        $exception = ProofFailedException::generationFailed($contractName, $entrypoint, $reason);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame(
            "Proof generation failed for {$contractName}::{$entrypoint}: {$reason}",
            $exception->getMessage()
        );
        $this->assertSame([
            'contract_name' => $contractName,
            'entrypoint' => $entrypoint,
            'reason' => $reason,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_generation_failed_with_additional_context(): void
    {
        $contractName = 'TokenContract';
        $entrypoint = 'mint';
        $reason = 'Circuit constraint violation';
        $context = [
            'witness_size' => 1024,
            'circuit_id' => 'abc123',
        ];

        $exception = ProofFailedException::generationFailed($contractName, $entrypoint, $reason, $context);

        $this->assertSame([
            'contract_name' => $contractName,
            'entrypoint' => $entrypoint,
            'reason' => $reason,
            'witness_size' => 1024,
            'circuit_id' => 'abc123',
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('contractDataProvider')]
    public function it_handles_various_contract_and_entrypoint_combinations(
        string $contractName,
        string $entrypoint
    ): void {
        $exception = ProofFailedException::generationFailed($contractName, $entrypoint, 'test reason');

        $this->assertStringContainsString($contractName, $exception->getMessage());
        $this->assertStringContainsString($entrypoint, $exception->getMessage());
        $this->assertSame($contractName, $exception->getContext()['contract_name']);
        $this->assertSame($entrypoint, $exception->getContext()['entrypoint']);
    }

    #[Test]
    public function it_creates_verification_failed_exception(): void
    {
        $reason = 'Proof signature mismatch';

        $exception = ProofFailedException::verificationFailed($reason);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame("Proof verification failed: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    public function it_creates_verification_failed_with_additional_context(): void
    {
        $reason = 'Invalid public inputs';
        $context = [
            'proof_id' => 'proof_123',
            'verifier_version' => '2.0',
        ];

        $exception = ProofFailedException::verificationFailed($reason, $context);

        $this->assertSame([
            'reason' => $reason,
            'proof_id' => 'proof_123',
            'verifier_version' => '2.0',
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('verificationReasonProvider')]
    public function it_handles_various_verification_failure_reasons(string $reason): void
    {
        $exception = ProofFailedException::verificationFailed($reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function it_creates_invalid_inputs_exception(): void
    {
        $inputType = 'public';
        $details = 'Expected 32 bytes, got 16 bytes';

        $exception = ProofFailedException::invalidInputs($inputType, $details);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame(
            "Invalid {$inputType} inputs for proof generation: {$details}",
            $exception->getMessage()
        );
        $this->assertSame([
            'input_type' => $inputType,
            'details' => $details,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('invalidInputsProvider')]
    public function it_handles_various_input_types_and_details(string $inputType, string $details): void
    {
        $exception = ProofFailedException::invalidInputs($inputType, $details);

        $this->assertStringContainsString($inputType, $exception->getMessage());
        $this->assertStringContainsString($details, $exception->getMessage());
        $this->assertSame($inputType, $exception->getContext()['input_type']);
        $this->assertSame($details, $exception->getContext()['details']);
    }

    #[Test]
    public function it_creates_server_unavailable_exception(): void
    {
        $serverUri = 'https://proof-server.example.com';

        $exception = ProofFailedException::serverUnavailable($serverUri);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame("Proof server at {$serverUri} is unavailable", $exception->getMessage());
        $this->assertSame(['server_uri' => $serverUri], $exception->getContext());
        $this->assertNull($exception->getPrevious());
    }

    #[Test]
    public function it_creates_server_unavailable_with_previous_exception(): void
    {
        $serverUri = 'https://proof-server.example.com';
        $previous = new RuntimeException('Connection timeout');

        $exception = ProofFailedException::serverUnavailable($serverUri, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(['server_uri' => $serverUri], $exception->getContext());
    }

    #[Test]
    #[DataProvider('serverUriProvider')]
    public function it_handles_various_server_uris(string $serverUri): void
    {
        $exception = ProofFailedException::serverUnavailable($serverUri);

        $this->assertStringContainsString($serverUri, $exception->getMessage());
        $this->assertSame($serverUri, $exception->getContext()['server_uri']);
    }

    #[Test]
    public function it_creates_constraint_violation_exception(): void
    {
        $constraint = 'input_sum_equals_output_sum';
        $details = 'Input sum: 100, Output sum: 95';

        $exception = ProofFailedException::constraintViolation($constraint, $details);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame(
            "Circuit constraint violation: {$constraint}. {$details}",
            $exception->getMessage()
        );
        $this->assertSame([
            'constraint' => $constraint,
            'details' => $details,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('constraintViolationProvider')]
    public function it_handles_various_constraint_violations(string $constraint, string $details): void
    {
        $exception = ProofFailedException::constraintViolation($constraint, $details);

        $this->assertStringContainsString($constraint, $exception->getMessage());
        $this->assertStringContainsString($details, $exception->getMessage());
        $this->assertSame($constraint, $exception->getContext()['constraint']);
        $this->assertSame($details, $exception->getContext()['details']);
    }

    #[Test]
    public function it_creates_timeout_exception(): void
    {
        $timeout = 60.0;
        $contractName = 'ComplexContract';
        $entrypoint = 'heavyComputation';

        $exception = ProofFailedException::timeout($timeout, $contractName, $entrypoint);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame(
            "Proof generation timed out after {$timeout} seconds for {$contractName}::{$entrypoint}",
            $exception->getMessage()
        );
        $this->assertSame([
            'timeout' => $timeout,
            'contract_name' => $contractName,
            'entrypoint' => $entrypoint,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('timeoutProvider')]
    public function it_handles_various_timeout_values(
        float $timeout,
        string $contractName,
        string $entrypoint
    ): void {
        $exception = ProofFailedException::timeout($timeout, $contractName, $entrypoint);

        $this->assertStringContainsString((string) $timeout, $exception->getMessage());
        $this->assertSame($timeout, $exception->getContext()['timeout']);
        $this->assertSame($contractName, $exception->getContext()['contract_name']);
        $this->assertSame($entrypoint, $exception->getContext()['entrypoint']);
    }

    #[Test]
    public function it_creates_missing_witness_exception(): void
    {
        $missingField = 'commitment_randomness';

        $exception = ProofFailedException::missingWitness($missingField);

        $this->assertInstanceOf(ProofFailedException::class, $exception);
        $this->assertSame("Missing required witness data: {$missingField}", $exception->getMessage());
        $this->assertSame(['missing_field' => $missingField], $exception->getContext());
    }

    #[Test]
    #[DataProvider('missingWitnessProvider')]
    public function it_handles_various_missing_witness_fields(string $missingField): void
    {
        $exception = ProofFailedException::missingWitness($missingField);

        $this->assertStringContainsString($missingField, $exception->getMessage());
        $this->assertSame($missingField, $exception->getContext()['missing_field']);
    }

    #[Test]
    public function all_factory_methods_return_proof_failed_exception_instance(): void
    {
        $methods = [
            ProofFailedException::generationFailed('Contract', 'method', 'reason'),
            ProofFailedException::verificationFailed('reason'),
            ProofFailedException::invalidInputs('public', 'details'),
            ProofFailedException::serverUnavailable('https://server.test'),
            ProofFailedException::constraintViolation('constraint', 'details'),
            ProofFailedException::timeout(30.0, 'Contract', 'method'),
            ProofFailedException::missingWitness('field'),
        ];

        foreach ($methods as $exception) {
            $this->assertInstanceOf(ProofFailedException::class, $exception);
            $this->assertInstanceOf(MidnightException::class, $exception);
        }
    }

    #[Test]
    public function exception_chaining_works_correctly(): void
    {
        $root = new RuntimeException('Network error');
        $proof = ProofFailedException::serverUnavailable('https://proof.test', $root);
        $wrapper = MidnightException::fromPrevious('Operation failed', $proof);

        $this->assertSame($proof, $wrapper->getPrevious());
        $this->assertSame($root, $wrapper->getPrevious()?->getPrevious());
    }

    public static function contractDataProvider(): array
    {
        return [
            'simple names' => ['TokenContract', 'transfer'],
            'complex names' => ['MultiSigWallet', 'executeTransaction'],
            'with underscores' => ['nft_contract', 'safe_mint'],
            'camelCase' => ['governanceContract', 'castVote'],
        ];
    }

    public static function verificationReasonProvider(): array
    {
        return [
            'signature mismatch' => ['Proof signature mismatch'],
            'invalid public inputs' => ['Invalid public inputs provided'],
            'proof too old' => ['Proof timestamp is too old'],
            'tampered proof' => ['Proof data has been tampered with'],
            'wrong circuit' => ['Proof was generated for different circuit'],
        ];
    }

    public static function invalidInputsProvider(): array
    {
        return [
            'public inputs' => ['public', 'Expected 32 bytes, got 16 bytes'],
            'private inputs' => ['private', 'Missing required field: amount'],
            'witness data' => ['witness', 'Array size mismatch: expected 10, got 8'],
            'commitment' => ['commitment', 'Invalid commitment format'],
        ];
    }

    public static function serverUriProvider(): array
    {
        return [
            'https' => ['https://proof-server.midnight.network'],
            'http localhost' => ['http://localhost:9000'],
            'with port' => ['https://proof.example.com:8443'],
            'ip address' => ['http://192.168.1.100:8080'],
        ];
    }

    public static function constraintViolationProvider(): array
    {
        return [
            'sum constraint' => [
                'input_sum_equals_output_sum',
                'Input sum: 100, Output sum: 95',
            ],
            'range constraint' => [
                'value_in_valid_range',
                'Value 256 exceeds maximum 255',
            ],
            'merkle proof' => [
                'valid_merkle_proof',
                'Merkle path verification failed at depth 5',
            ],
            'signature verification' => [
                'valid_signature',
                'EdDSA signature verification failed',
            ],
        ];
    }

    public static function timeoutProvider(): array
    {
        return [
            'short timeout' => [30.0, 'SimpleContract', 'basicOp'],
            'long timeout' => [300.0, 'ComplexContract', 'heavyComputation'],
            'fractional timeout' => [45.5, 'TokenContract', 'transfer'],
        ];
    }

    public static function missingWitnessProvider(): array
    {
        return [
            'randomness' => ['commitment_randomness'],
            'merkle path' => ['merkle_proof_path'],
            'private key' => ['spending_key'],
            'nullifier' => ['nullifier_secret'],
            'balance' => ['account_balance'],
        ];
    }
}
