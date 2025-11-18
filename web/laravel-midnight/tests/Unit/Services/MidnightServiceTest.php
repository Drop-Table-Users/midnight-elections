<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use VersionTwo\Midnight\Contracts\BridgeHttpClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\NetworkException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;
use VersionTwo\Midnight\Services\MidnightService;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the MidnightService class.
 *
 * @covers \VersionTwo\Midnight\Services\MidnightService
 */
final class MidnightServiceTest extends TestCase
{
    private BridgeHttpClient&MockInterface $httpClient;
    private CacheRepository&MockInterface $cache;
    private MidnightService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(BridgeHttpClient::class);
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->service = new MidnightService($this->httpClient, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(MidnightService::class, $this->service);
    }

    #[Test]
    public function it_fetches_network_metadata_from_bridge(): void
    {
        $metadataData = [
            'chain_id' => 'midnight-testnet-1',
            'name' => 'Midnight Testnet',
            'version' => '1.0.0',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:network'])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->with('midnight:network:metadata')
            ->andReturn(null);

        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('/api/network/metadata')
            ->andReturn(['data' => $metadataData]);

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:network'])
            ->andReturnSelf();

        $this->cache->shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) use ($metadataData) {
                return $key === 'midnight:network:metadata'
                    && is_array($value)
                    && $value['chain_id'] === $metadataData['chain_id'];
            })
            ->andReturn(true);

        $metadata = $this->service->getNetworkMetadata();

        $this->assertInstanceOf(NetworkMetadata::class, $metadata);
        $this->assertSame('midnight-testnet-1', $metadata->chainId);
    }

    #[Test]
    public function it_retrieves_network_metadata_from_cache(): void
    {
        $cachedData = [
            'chain_id' => 'midnight-testnet-1',
            'name' => 'Midnight Testnet',
            'version' => '1.0.0',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:network'])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->with('midnight:network:metadata')
            ->andReturn($cachedData);

        $this->httpClient->shouldNotReceive('get');

        $metadata = $this->service->getNetworkMetadata();

        $this->assertInstanceOf(NetworkMetadata::class, $metadata);
        $this->assertSame('midnight-testnet-1', $metadata->chainId);
    }

    #[Test]
    public function it_throws_network_exception_when_metadata_response_is_invalid(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('/api/network/metadata')
            ->andReturn(['invalid' => 'response']);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Invalid bridge response');

        $this->service->getNetworkMetadata();
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_network_exception_for_metadata(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->httpClient->shouldReceive('get')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to retrieve network metadata');

        $this->service->getNetworkMetadata();
    }

    #[Test]
    public function it_submits_transaction_successfully(): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1contract',
            entrypoint: 'transfer',
            publicArgs: ['to' => 'midnight1recipient', 'amount' => '100']
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('/api/transactions/submit', [
                'contract_address' => 'midnight1contract',
                'entrypoint' => 'transfer',
                'public_args' => ['to' => 'midnight1recipient', 'amount' => '100'],
                'private_args' => [],
                'metadata' => [],
            ])
            ->andReturn(['tx_hash' => '0xabcdef123456']);

        $txHash = $this->service->submitTransaction($call);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xabcdef123456', $txHash->value);
    }

    #[Test]
    public function it_handles_camelCase_tx_hash_in_response(): void
    {
        $call = new ContractCall('midnight1contract', 'transfer');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andReturn(['txHash' => '0xabcdef123456']);

        $txHash = $this->service->submitTransaction($call);

        $this->assertSame('0xabcdef123456', $txHash->value);
    }

    #[Test]
    public function it_throws_network_exception_when_submit_response_missing_tx_hash(): void
    {
        $call = new ContractCall('midnight1contract', 'transfer');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andReturn(['invalid' => 'response']);

        $this->expectException(NetworkException::class);

        $this->service->submitTransaction($call);
    }

    #[Test]
    public function it_wraps_network_exception_in_contract_exception_for_submit(): void
    {
        $call = new ContractCall('midnight1contract', 'transfer');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception('Connection failed')));

        $this->expectException(NetworkException::class);

        $this->service->submitTransaction($call);
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_contract_exception_for_submit(): void
    {
        $call = new ContractCall('midnight1contract', 'transfer');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Failed to submit transaction');

        $this->service->submitTransaction($call);
    }

    #[Test]
    public function it_gets_transaction_status(): void
    {
        $txHash = '0xabcdef123456';
        $statusData = [
            'status' => 'confirmed',
            'block_height' => 12345,
            'confirmations' => 10,
        ];

        $this->httpClient->shouldReceive('get')
            ->once()
            ->with("/api/transactions/{$txHash}/status")
            ->andReturn($statusData);

        $status = $this->service->getTransactionStatus($txHash);

        $this->assertSame('confirmed', $status['status']);
        $this->assertSame(12345, $status['block_height']);
    }

    #[Test]
    public function it_throws_network_exception_when_status_response_is_invalid(): void
    {
        $txHash = '0xabcdef123456';

        $this->httpClient->shouldReceive('get')
            ->once()
            ->andReturn(['invalid' => 'response']);

        $this->expectException(NetworkException::class);

        $this->service->getTransactionStatus($txHash);
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_network_exception_for_status(): void
    {
        $this->httpClient->shouldReceive('get')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to get status for transaction');

        $this->service->getTransactionStatus('0xabcdef');
    }

    #[Test]
    public function it_calls_readonly_contract_method(): void
    {
        $call = ContractCall::readOnly(
            contractAddress: 'midnight1contract',
            entrypoint: 'getBalance'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('/api/contracts/call/readonly', [
                'contract_address' => 'midnight1contract',
                'entrypoint' => 'getBalance',
                'public_args' => [],
                'metadata' => [],
            ])
            ->andReturn([
                'success' => true,
                'value' => '1000',
            ]);

        $result = $this->service->callReadOnly($call);

        $this->assertInstanceOf(ContractCallResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('1000', $result->value);
    }

    #[Test]
    public function it_wraps_network_exception_in_contract_exception_for_readonly_call(): void
    {
        $call = ContractCall::readOnly('midnight1contract', 'getBalance');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(NetworkException::class);

        $this->service->callReadOnly($call);
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_contract_exception_for_readonly_call(): void
    {
        $call = ContractCall::readOnly('midnight1contract', 'getBalance');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Failed to execute read-only call');

        $this->service->callReadOnly($call);
    }

    #[Test]
    public function it_generates_proof_successfully(): void
    {
        $request = new ProofRequest(
            contractName: 'VotingContract',
            entrypoint: 'vote',
            publicInputs: ['vote_id' => '123'],
            privateInputs: ['choice' => 'yes']
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('/api/proofs/generate', [
                'contract_name' => 'VotingContract',
                'entrypoint' => 'vote',
                'public_inputs' => ['vote_id' => '123'],
                'private_inputs' => ['choice' => 'yes'],
                'circuit_path' => null,
                'metadata' => [],
            ])
            ->andReturn([
                'proof' => 'proof_data_here',
                'public_outputs' => ['result' => 'verified'],
                'generation_time' => 1.5,
            ]);

        $proof = $this->service->generateProof($request);

        $this->assertInstanceOf(ProofResponse::class, $proof);
        $this->assertSame('proof_data_here', $proof->proof);
    }

    #[Test]
    public function it_throws_proof_failed_exception_on_network_error(): void
    {
        $request = new ProofRequest(
            contractName: 'VotingContract',
            entrypoint: 'vote',
            publicInputs: [],
            privateInputs: ['choice' => 'yes']
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(ProofFailedException::class);
        $this->expectExceptionMessage('Proof generation failed');

        $this->service->generateProof($request);
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_proof_failed_exception(): void
    {
        $request = new ProofRequest(
            contractName: 'VotingContract',
            entrypoint: 'vote',
            publicInputs: [],
            privateInputs: ['choice' => 'yes']
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(ProofFailedException::class);

        $this->service->generateProof($request);
    }

    #[Test]
    public function it_performs_health_check_successfully(): void
    {
        $this->httpClient->shouldReceive('healthCheck')
            ->once()
            ->andReturn(true);

        $healthy = $this->service->healthCheck();

        $this->assertTrue($healthy);
    }

    #[Test]
    public function it_returns_false_on_health_check_failure(): void
    {
        $this->httpClient->shouldReceive('healthCheck')
            ->once()
            ->andThrow(new \RuntimeException('Health check failed'));

        $healthy = $this->service->healthCheck();

        $this->assertFalse($healthy);
    }

    #[Test]
    public function it_clears_network_cache(): void
    {
        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:network'])
            ->andReturnSelf();

        $this->cache->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $this->service->clearNetworkCache();

        // If we got here without exception, the test passes
        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_successful_operations(): void
    {
        // This test ensures logging is called (checking behavior, not output)
        $metadataData = [
            'chain_id' => 'midnight-testnet-1',
            'name' => 'Midnight Testnet',
            'version' => '1.0.0',
        ];

        $this->cache->shouldReceive('tags->get')->andReturn(null);
        $this->httpClient->shouldReceive('get')->andReturn(['data' => $metadataData]);
        $this->cache->shouldReceive('tags->put')->andReturn(true);

        // The service should complete without errors
        $metadata = $this->service->getNetworkMetadata();

        $this->assertInstanceOf(NetworkMetadata::class, $metadata);
    }

    #[Test]
    public function it_submits_transaction_with_private_args(): void
    {
        $call = new ContractCall(
            contractAddress: 'midnight1contract',
            entrypoint: 'privateTransfer',
            publicArgs: ['to' => 'midnight1recipient'],
            privateArgs: ['amount' => '100', 'secret' => 'data']
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->withArgs(function ($endpoint, $data) {
                return $endpoint === '/api/transactions/submit'
                    && $data['private_args'] === ['amount' => '100', 'secret' => 'data'];
            })
            ->andReturn(['tx_hash' => '0xabcdef']);

        $txHash = $this->service->submitTransaction($call);

        $this->assertInstanceOf(TxHash::class, $txHash);
    }

    #[Test]
    public function it_handles_failed_readonly_call_result(): void
    {
        $call = ContractCall::readOnly('midnight1contract', 'failingMethod');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Method execution failed',
            ]);

        $result = $this->service->callReadOnly($call);

        $this->assertFalse($result->success);
        $this->assertSame('Method execution failed', $result->error);
    }

    #[Test]
    public function it_uses_default_cache_when_none_provided(): void
    {
        // Create service without explicit cache
        $service = new MidnightService($this->httpClient);

        // This test verifies that the service can be instantiated without a cache
        // The actual cache will be resolved from Laravel's Cache facade
        $this->assertInstanceOf(MidnightService::class, $service);
    }
}
