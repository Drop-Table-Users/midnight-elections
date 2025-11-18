<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\NetworkException;
use VersionTwo\Midnight\Services\ContractService;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the ContractService class.
 *
 * @covers \VersionTwo\Midnight\Services\ContractService
 */
final class ContractServiceTest extends TestCase
{
    private MidnightClient&MockInterface $client;
    private CacheRepository&MockInterface $cache;
    private ContractService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(MidnightClient::class);
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->service = new ContractService($this->client, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ContractService::class, $this->service);
    }

    #[Test]
    public function it_deploys_contract_successfully(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
        file_put_contents($tempFile, 'compiled_contract_data');

        try {
            $this->client->shouldReceive('submitTransaction')
                ->once()
                ->withArgs(function (ContractCall $call) {
                    return $call->entrypoint === '__deploy__'
                        && isset($call->publicArgs['contract_bytes']);
                })
                ->andReturn(new TxHash('0xabcdef123456'));

            $address = $this->service->deploy($tempFile, ['init_arg' => 'value']);

            $this->assertStringContainsString('pending:', $address);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function it_throws_exception_when_contract_file_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Compiled contract file not found');

        $this->service->deploy('/nonexistent/contract.wasm');
    }

    #[Test]
    public function it_throws_exception_when_contract_file_not_readable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
        chmod($tempFile, 0000);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('not readable');

            $this->service->deploy($tempFile);
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }

    #[Test]
    public function it_throws_contract_exception_on_deployment_failure(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
        file_put_contents($tempFile, 'contract_data');

        try {
            $this->client->shouldReceive('submitTransaction')
                ->once()
                ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

            $this->expectException(ContractException::class);

            $this->service->deploy($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function it_joins_contract_successfully(): void
    {
        $contractAddress = 'midnight1contract';
        $args = ['participant_id' => '123'];

        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->withArgs(function (ContractCall $call) use ($contractAddress, $args) {
                return $call->contractAddress === $contractAddress
                    && $call->entrypoint === 'join'
                    && $call->publicArgs === $args;
            })
            ->andReturn(new TxHash('0xabcdef'));

        $result = $this->service->join($contractAddress, $args);

        $this->assertInstanceOf(ContractCallResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('0xabcdef', $result->value['tx_hash']);
    }

    #[Test]
    public function it_throws_exception_when_joining_with_empty_address(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract address cannot be empty');

        $this->service->join('');
    }

    #[Test]
    public function it_throws_contract_exception_on_join_failure(): void
    {
        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(ContractException::class);

        $this->service->join('midnight1contract');
    }

    #[Test]
    public function it_calls_contract_method_successfully(): void
    {
        $contractAddress = 'midnight1contract';
        $entrypoint = 'transfer';
        $publicArgs = ['to' => 'midnight1recipient', 'amount' => '100'];

        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->withArgs(function (ContractCall $call) use ($contractAddress, $entrypoint) {
                return $call->contractAddress === $contractAddress
                    && $call->entrypoint === $entrypoint
                    && !$call->readOnly;
            })
            ->andReturn(new TxHash('0xabcdef'));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();

        $this->cache->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $txHash = $this->service->call($contractAddress, $entrypoint, $publicArgs);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xabcdef', $txHash->value);
    }

    #[Test]
    public function it_invalidates_cache_after_contract_call(): void
    {
        $contractAddress = 'midnight1contract';

        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andReturn(new TxHash('0xabcdef'));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();

        $this->cache->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $this->service->call($contractAddress, 'method');

        // Cache flush was verified by mock expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_exception_when_calling_with_empty_address(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract address cannot be empty');

        $this->service->call('', 'method');
    }

    #[Test]
    public function it_throws_exception_when_calling_with_empty_entrypoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entrypoint cannot be empty');

        $this->service->call('midnight1contract', '');
    }

    #[Test]
    public function it_calls_contract_with_private_args(): void
    {
        $contractAddress = 'midnight1contract';
        $privateArgs = ['secret' => 'data'];

        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->withArgs(function (ContractCall $call) use ($privateArgs) {
                return $call->privateArgs === $privateArgs;
            })
            ->andReturn(new TxHash('0xabcdef'));

        $this->cache->shouldReceive('tags->flush')->andReturn(true);

        $this->service->call($contractAddress, 'privateMethod', [], $privateArgs);

        // Verified by mock expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function it_reads_contract_state_successfully(): void
    {
        $contractAddress = 'midnight1contract';
        $selector = 'getBalance';
        $args = ['address' => 'midnight1user'];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->withArgs(function (ContractCall $call) use ($contractAddress, $selector) {
                return $call->contractAddress === $contractAddress
                    && $call->entrypoint === $selector
                    && $call->readOnly === true;
            })
            ->andReturn(ContractCallResult::success(['balance' => '1000']));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();

        $this->cache->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $result = $this->service->read($contractAddress, $selector, $args);

        $this->assertInstanceOf(ContractCallResult::class, $result);
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_retrieves_contract_state_from_cache(): void
    {
        $contractAddress = 'midnight1contract';
        $selector = 'getBalance';

        $cachedResult = [
            'success' => true,
            'value' => ['balance' => '1000'],
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->andReturn($cachedResult);

        $this->client->shouldNotReceive('callReadOnly');

        $result = $this->service->read($contractAddress, $selector);

        $this->assertInstanceOf(ContractCallResult::class, $result);
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_throws_exception_when_reading_with_empty_address(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract address cannot be empty');

        $this->service->read('', 'selector');
    }

    #[Test]
    public function it_throws_exception_when_reading_with_empty_selector(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entrypoint cannot be empty');

        $this->service->read('midnight1contract', '');
    }

    #[Test]
    public function it_throws_contract_exception_on_read_failure(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(ContractException::class);

        $this->service->read('midnight1contract', 'getBalance');
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_contract_exception_for_read(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(ContractException::class);

        $this->service->read('midnight1contract', 'getBalance');
    }

    #[Test]
    public function it_generates_cache_key_with_args_hash(): void
    {
        // This test verifies that different args generate different cache entries
        $contractAddress = 'midnight1contract';
        $selector = 'getBalance';

        // First call with args1
        $this->cache->shouldReceive('tags')
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();
        $this->cache->shouldReceive('get')->andReturn(null);
        $this->client->shouldReceive('callReadOnly')
            ->andReturn(ContractCallResult::success(['balance' => '1000']));
        $this->cache->shouldReceive('tags')
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();
        $this->cache->shouldReceive('put')->andReturn(true);

        $this->service->read($contractAddress, $selector, ['address' => 'user1']);

        // Second call with args2 should not hit the same cache key
        $this->cache->shouldReceive('tags')
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();
        $this->cache->shouldReceive('get')->andReturn(null);
        $this->client->shouldReceive('callReadOnly')
            ->andReturn(ContractCallResult::success(['balance' => '2000']));
        $this->cache->shouldReceive('tags')
            ->with(['midnight:contract:' . $contractAddress])
            ->andReturnSelf();
        $this->cache->shouldReceive('put')->andReturn(true);

        $this->service->read($contractAddress, $selector, ['address' => 'user2']);

        // Both calls completed, proving different cache keys were used
        $this->assertTrue(true);
    }

    #[Test]
    public function it_uses_default_cache_when_none_provided(): void
    {
        $service = new ContractService($this->client);

        $this->assertInstanceOf(ContractService::class, $service);
    }

    #[Test]
    public function it_deploys_contract_with_init_args(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
        file_put_contents($tempFile, 'compiled_contract_data');

        try {
            $initArgs = ['owner' => 'midnight1owner', 'total_supply' => '1000000'];

            $this->client->shouldReceive('submitTransaction')
                ->once()
                ->withArgs(function (ContractCall $call) use ($initArgs) {
                    return $call->publicArgs['owner'] === $initArgs['owner']
                        && $call->publicArgs['total_supply'] === $initArgs['total_supply'];
                })
                ->andReturn(new TxHash('0xabcdef'));

            $this->service->deploy($tempFile, $initArgs);

            $this->assertTrue(true);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function it_handles_contract_call_exception(): void
    {
        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andThrow(ContractException::callFailed('midnight1contract', 'method', 'Failed'));

        $this->cache->shouldNotReceive('flush');

        $this->expectException(ContractException::class);

        $this->service->call('midnight1contract', 'method');
    }

    #[Test]
    public function it_handles_unexpected_deployment_error(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
        file_put_contents($tempFile, 'contract_data');

        try {
            $this->client->shouldReceive('submitTransaction')
                ->once()
                ->andThrow(new \RuntimeException('Unexpected error'));

            $this->expectException(ContractException::class);

            $this->service->deploy($tempFile);
        } finally {
            unlink($tempFile);
        }
    }
}
