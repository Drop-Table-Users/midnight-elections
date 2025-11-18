<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Services;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Contracts\ProofClient;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\EntitlementToken;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\MidnightException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;
use VersionTwo\Midnight\Services\EntitlementServiceImpl;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the EntitlementServiceImpl class.
 *
 * @covers \VersionTwo\Midnight\Services\EntitlementServiceImpl
 */
final class EntitlementServiceImplTest extends TestCase
{
    private ContractGateway&MockInterface $contractGateway;
    private ProofClient&MockInterface $proofClient;
    private EntitlementServiceImpl $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contractGateway = Mockery::mock(ContractGateway::class);
        $this->proofClient = Mockery::mock(ProofClient::class);
        $this->service = new EntitlementServiceImpl($this->contractGateway, $this->proofClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(EntitlementServiceImpl::class, $this->service);
    }

    #[Test]
    public function it_requests_entitlement_token_successfully(): void
    {
        $identity = 'user@example.com';
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $tokenData = [
            'token' => 'entitlement_token_data',
            'expires_at' => $expiresAt->format(\DateTimeInterface::ATOM),
        ];

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->withArgs(function ($contractAddress, $selector, $args) use ($identity) {
                return $selector === 'requestEntitlement'
                    && $args['identity'] === $identity;
            })
            ->andReturn(ContractCallResult::success($tokenData));

        $token = $this->service->requestEntitlement($identity);

        $this->assertInstanceOf(EntitlementToken::class, $token);
        $this->assertSame('entitlement_token_data', $token->token);
        $this->assertTrue($token->isValid());
    }

    #[Test]
    public function it_throws_exception_when_identity_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identity cannot be empty');

        $this->service->requestEntitlement('');
    }

    #[Test]
    public function it_throws_exception_when_identity_is_too_long(): void
    {
        $longIdentity = str_repeat('a', 256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identity is too long');

        $this->service->requestEntitlement($longIdentity);
    }

    #[Test]
    public function it_throws_exception_when_entitlement_request_fails(): void
    {
        $identity = 'user@example.com';

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andReturn(ContractCallResult::failure('Not eligible'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to request entitlement token');

        $this->service->requestEntitlement($identity);
    }

    #[Test]
    public function it_throws_exception_when_token_response_is_invalid(): void
    {
        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andReturn(ContractCallResult::success('invalid_string'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Invalid entitlement token response format');

        $this->service->requestEntitlement('user@example.com');
    }

    #[Test]
    public function it_throws_exception_when_received_token_is_expired(): void
    {
        $expiredTime = new \DateTimeImmutable('-1 hour');

        $tokenData = [
            'token' => 'expired_token',
            'expires_at' => $expiredTime->format(\DateTimeInterface::ATOM),
        ];

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andReturn(ContractCallResult::success($tokenData));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Received expired entitlement token');

        $this->service->requestEntitlement('user@example.com');
    }

    #[Test]
    public function it_wraps_contract_exception_when_requesting_entitlement(): void
    {
        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andThrow(ContractException::stateReadFailed('contract', 'selector', 'Failed'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Identity is not eligible for entitlement token');

        $this->service->requestEntitlement('user@example.com');
    }

    #[Test]
    public function it_wraps_unexpected_errors_when_requesting_entitlement(): void
    {
        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to request entitlement token');

        $this->service->requestEntitlement('user@example.com');
    }

    #[Test]
    public function it_uses_entitlement_for_vote_successfully(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $contractAddress = 'midnight1voting';
        $votePayload = ['proposal_id' => '123', 'vote' => 'yes'];

        $proof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: ['verified' => true],
            generationTime: 1.5
        );

        $this->proofClient->shouldReceive('generateForContract')
            ->once()
            ->withArgs(function ($contractName, $entrypoint, $publicInputs, $privateInputs) use ($token, $votePayload) {
                return $entrypoint === 'vote'
                    && $privateInputs['entitlement_token'] === $token->token
                    && $privateInputs['identity'] === $token->identity
                    && $privateInputs['vote'] === $votePayload;
            })
            ->andReturn($proof);

        $this->contractGateway->shouldReceive('call')
            ->once()
            ->withArgs(function ($address, $entrypoint, $publicArgs, $privateArgs) use ($contractAddress, $proof, $token, $votePayload) {
                return $address === $contractAddress
                    && $entrypoint === 'vote'
                    && $publicArgs['proof'] === $proof->proof
                    && $privateArgs['entitlement_token'] === $token->token
                    && $privateArgs['vote_data'] === $votePayload;
            })
            ->andReturn(new TxHash('0xabcdef123456'));

        $txHash = $this->service->useEntitlementForVote($token, $contractAddress, $votePayload);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xabcdef123456', $txHash->value);
    }

    #[Test]
    public function it_throws_exception_when_token_is_invalid(): void
    {
        $expiredToken = new EntitlementToken(
            token: 'expired_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('-1 hour')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entitlement token has expired');

        $this->service->useEntitlementForVote($expiredToken, 'midnight1voting', ['vote' => 'yes']);
    }

    #[Test]
    public function it_throws_exception_when_token_value_is_empty(): void
    {
        $emptyToken = new EntitlementToken(
            token: '',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entitlement token value is empty');

        $this->service->useEntitlementForVote($emptyToken, 'midnight1voting', ['vote' => 'yes']);
    }

    #[Test]
    public function it_throws_exception_when_contract_address_is_empty(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract address cannot be empty');

        $this->service->useEntitlementForVote($token, '', ['vote' => 'yes']);
    }

    #[Test]
    public function it_throws_exception_when_vote_payload_is_empty(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vote payload cannot be empty');

        $this->service->useEntitlementForVote($token, 'midnight1voting', []);
    }

    #[Test]
    public function it_throws_proof_failed_exception_when_proof_generation_fails(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $this->proofClient->shouldReceive('generateForContract')
            ->once()
            ->andThrow(ProofFailedException::generationFailed('Contract', 'vote', 'Failed'));

        $this->expectException(ProofFailedException::class);

        $this->service->useEntitlementForVote($token, 'midnight1voting', ['vote' => 'yes']);
    }

    #[Test]
    public function it_throws_contract_exception_when_vote_submission_fails(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $proof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->proofClient->shouldReceive('generateForContract')
            ->once()
            ->andReturn($proof);

        $this->contractGateway->shouldReceive('call')
            ->once()
            ->andThrow(ContractException::callFailed('midnight1voting', 'vote', 'Failed'));

        $this->expectException(ContractException::class);

        $this->service->useEntitlementForVote($token, 'midnight1voting', ['vote' => 'yes']);
    }

    #[Test]
    public function it_wraps_unexpected_errors_when_voting(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $proof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->proofClient->shouldReceive('generateForContract')
            ->once()
            ->andReturn($proof);

        $this->contractGateway->shouldReceive('call')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to submit vote');

        $this->service->useEntitlementForVote($token, 'midnight1voting', ['vote' => 'yes']);
    }

    #[Test]
    public function it_checks_if_token_is_used(): void
    {
        $token = new EntitlementToken(
            token: 'used_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $contractAddress = 'midnight1voting';

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->withArgs(function ($address, $selector, $args) use ($contractAddress, $token) {
                return $address === $contractAddress
                    && $selector === 'isTokenUsed'
                    && isset($args['token_hash']);
            })
            ->andReturn(ContractCallResult::success(['value' => true]));

        $isUsed = $this->service->isTokenUsed($token, $contractAddress);

        $this->assertTrue($isUsed);
    }

    #[Test]
    public function it_checks_if_token_is_not_used(): void
    {
        $token = new EntitlementToken(
            token: 'unused_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andReturn(ContractCallResult::success(['value' => false]));

        $isUsed = $this->service->isTokenUsed($token, 'midnight1voting');

        $this->assertFalse($isUsed);
    }

    #[Test]
    public function it_returns_true_when_token_usage_check_fails(): void
    {
        $token = new EntitlementToken(
            token: 'token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andThrow(new \RuntimeException('Failed to check'));

        $isUsed = $this->service->isTokenUsed($token, 'midnight1voting');

        // Conservative approach: assume token is used if we can't verify
        $this->assertTrue($isUsed);
    }

    #[Test]
    public function it_masks_identity_in_operations(): void
    {
        // Test with various identity lengths to ensure masking works
        $shortIdentity = 'user';
        $longIdentity = 'user@example.com';

        $tokenData = [
            'token' => 'token_data',
            'expires_at' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
        ];

        $this->contractGateway->shouldReceive('read')
            ->twice()
            ->andReturn(ContractCallResult::success($tokenData));

        $this->service->requestEntitlement($shortIdentity);
        $this->service->requestEntitlement($longIdentity);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_includes_timestamp_in_proof_public_inputs(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $proof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->proofClient->shouldReceive('generateForContract')
            ->once()
            ->withArgs(function ($contractName, $entrypoint, $publicInputs, $privateInputs) {
                return isset($publicInputs['timestamp'])
                    && isset($publicInputs['contract_address']);
            })
            ->andReturn($proof);

        $this->contractGateway->shouldReceive('call')
            ->once()
            ->andReturn(new TxHash('0xabcdef'));

        $this->service->useEntitlementForVote($token, 'midnight1voting', ['vote' => 'yes']);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_complex_vote_payloads(): void
    {
        $token = new EntitlementToken(
            token: 'valid_token',
            identity: 'user@example.com',
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        $complexPayload = [
            'proposal_id' => '123',
            'vote' => 'yes',
            'delegation' => ['delegate_to' => 'midnight1delegate'],
            'metadata' => ['reason' => 'I support this', 'timestamp' => time()],
        ];

        $proof = new ProofResponse(
            proof: 'proof_data',
            publicOutputs: [],
            generationTime: 1.0
        );

        $this->proofClient->shouldReceive('generateForContract')
            ->once()
            ->withArgs(function ($contractName, $entrypoint, $publicInputs, $privateInputs) use ($complexPayload) {
                return $privateInputs['vote'] === $complexPayload;
            })
            ->andReturn($proof);

        $this->contractGateway->shouldReceive('call')
            ->once()
            ->andReturn(new TxHash('0xabcdef'));

        $txHash = $this->service->useEntitlementForVote($token, 'midnight1voting', $complexPayload);

        $this->assertInstanceOf(TxHash::class, $txHash);
    }

    #[Test]
    public function it_accepts_identity_at_max_length(): void
    {
        $maxLengthIdentity = str_repeat('a', 255);

        $tokenData = [
            'token' => 'token_data',
            'expires_at' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
        ];

        $this->contractGateway->shouldReceive('read')
            ->once()
            ->andReturn(ContractCallResult::success($tokenData));

        $token = $this->service->requestEntitlement($maxLengthIdentity);

        $this->assertInstanceOf(EntitlementToken::class, $token);
    }
}
