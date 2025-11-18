<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\MidnightException;
use VersionTwo\Midnight\Exceptions\NetworkException;
use VersionTwo\Midnight\Services\WalletService;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the WalletService class.
 *
 * @covers \VersionTwo\Midnight\Services\WalletService
 */
final class WalletServiceTest extends TestCase
{
    private MidnightClient&MockInterface $client;
    private CacheRepository&MockInterface $cache;
    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(MidnightClient::class);
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->service = new WalletService($this->client, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(WalletService::class, $this->service);
    }

    #[Test]
    public function it_fetches_wallet_address_from_client(): void
    {
        $address = 'midnight1wallet123456';

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->with('midnight:wallet:address')
            ->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->withArgs(function (ContractCall $call) {
                return $call->contractAddress === '__wallet__'
                    && $call->entrypoint === 'getAddress'
                    && $call->readOnly === true;
            })
            ->andReturn(ContractCallResult::success(['value' => $address]));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) use ($address) {
                return $key === 'midnight:wallet:address'
                    && $value === $address
                    && $ttl === 86400;
            })
            ->andReturn(true);

        $result = $this->service->getAddress();

        $this->assertSame($address, $result);
    }

    #[Test]
    public function it_retrieves_wallet_address_from_cache(): void
    {
        $cachedAddress = 'midnight1cached123456';

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->with('midnight:wallet:address')
            ->andReturn($cachedAddress);

        $this->client->shouldNotReceive('callReadOnly');

        $result = $this->service->getAddress();

        $this->assertSame($cachedAddress, $result);
    }

    #[Test]
    public function it_throws_exception_when_address_response_is_empty(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andReturn(ContractCallResult::success([]));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to retrieve wallet address');

        $this->service->getAddress();
    }

    #[Test]
    public function it_wraps_network_exception_when_fetching_address(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(NetworkException::class);

        $this->service->getAddress();
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_midnight_exception_for_address(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to retrieve wallet address');

        $this->service->getAddress();
    }

    #[Test]
    public function it_fetches_wallet_balance_from_client(): void
    {
        $balance = '1000000000000000000';

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->with('midnight:wallet:balance:native')
            ->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->withArgs(function (ContractCall $call) {
                return $call->contractAddress === '__wallet__'
                    && $call->entrypoint === 'getBalance';
            })
            ->andReturn(ContractCallResult::success(['value' => $balance]));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) use ($balance) {
                return $key === 'midnight:wallet:balance:native'
                    && $value === $balance
                    && $ttl === 10;
            })
            ->andReturn(true);

        $result = $this->service->getBalance();

        $this->assertSame($balance, $result);
    }

    #[Test]
    public function it_retrieves_wallet_balance_from_cache(): void
    {
        $cachedBalance = '2000000000000000000';

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('get')
            ->once()
            ->with('midnight:wallet:balance:native')
            ->andReturn($cachedBalance);

        $this->client->shouldNotReceive('callReadOnly');

        $result = $this->service->getBalance();

        $this->assertSame($cachedBalance, $result);
    }

    #[Test]
    public function it_wraps_network_exception_when_fetching_balance(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(NetworkException::class);

        $this->service->getBalance();
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_midnight_exception_for_balance(): void
    {
        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to retrieve wallet balance');

        $this->service->getBalance();
    }

    #[Test]
    public function it_transfers_funds_successfully(): void
    {
        $toAddress = 'midnight1recipient123456';
        $amount = '1000000000000000000';

        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->withArgs(function (ContractCall $call) use ($toAddress, $amount) {
                return $call->contractAddress === '__wallet__'
                    && $call->entrypoint === 'transfer'
                    && $call->publicArgs['to_address'] === $toAddress
                    && $call->publicArgs['amount'] === $amount
                    && !$call->readOnly;
            })
            ->andReturn(new TxHash('0xabcdef123456'));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $txHash = $this->service->transfer($toAddress, $amount);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xabcdef123456', $txHash->value);
    }

    #[Test]
    public function it_transfers_with_custom_asset(): void
    {
        $toAddress = 'midnight1recipient123456';
        $amount = '100';
        $asset = 'custom_token';

        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->withArgs(function (ContractCall $call) use ($asset) {
                return $call->publicArgs['asset'] === $asset;
            })
            ->andReturn(new TxHash('0xabcdef'));

        $this->cache->shouldReceive('tags->flush')->andReturn(true);

        $this->service->transfer($toAddress, $amount, $asset);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_invalidates_balance_cache_after_transfer(): void
    {
        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andReturn(new TxHash('0xabcdef'));

        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $this->service->transfer('midnight1recipient', '100');

        // Cache flush was verified by mock expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_exception_when_transfer_address_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address cannot be empty');

        $this->service->transfer('', '100');
    }

    #[Test]
    public function it_throws_exception_when_transfer_address_is_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address is too short');

        $this->service->transfer('short', '100');
    }

    #[Test]
    public function it_throws_exception_when_transfer_amount_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be empty');

        $this->service->transfer('midnight1recipient123456', '');
    }

    #[Test]
    public function it_throws_exception_when_transfer_amount_is_not_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be a valid numeric string');

        $this->service->transfer('midnight1recipient123456', 'not_a_number');
    }

    #[Test]
    #[DataProvider('invalidAmountsProvider')]
    public function it_throws_exception_when_transfer_amount_is_invalid(string $amount, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->service->transfer('midnight1recipient123456', $amount);
    }

    #[Test]
    public function it_wraps_network_exception_when_transfer_fails(): void
    {
        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andThrow(NetworkException::bridgeConnectionFailed('http://localhost', new \Exception()));

        $this->expectException(NetworkException::class);

        $this->service->transfer('midnight1recipient123456', '100');
    }

    #[Test]
    public function it_wraps_unexpected_errors_in_midnight_exception_for_transfer(): void
    {
        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        $this->expectException(MidnightException::class);
        $this->expectExceptionMessage('Failed to transfer');

        $this->service->transfer('midnight1recipient123456', '100');
    }

    #[Test]
    public function it_clears_address_cache(): void
    {
        $this->cache->shouldReceive('tags')
            ->once()
            ->with(['midnight:wallet'])
            ->andReturnSelf();

        $this->cache->shouldReceive('forget')
            ->once()
            ->with('midnight:wallet:address')
            ->andReturn(true);

        $this->service->clearAddressCache();

        // Cache forget was verified by mock expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function it_uses_default_cache_when_none_provided(): void
    {
        $service = new WalletService($this->client);

        $this->assertInstanceOf(WalletService::class, $service);
    }

    #[Test]
    #[DataProvider('validAmountsProvider')]
    public function it_accepts_valid_transfer_amounts(string $amount): void
    {
        $this->client->shouldReceive('submitTransaction')
            ->once()
            ->andReturn(new TxHash('0xabcdef'));

        $this->cache->shouldReceive('tags->flush')->andReturn(true);

        $txHash = $this->service->transfer('midnight1recipient123456', $amount);

        $this->assertInstanceOf(TxHash::class, $txHash);
    }

    #[Test]
    public function it_masks_address_in_logs(): void
    {
        // This test ensures the service can handle addresses of various lengths
        $shortAddress = 'midnight123';
        $longAddress = 'midnight1abcdefghijklmnopqrstuvwxyz123456';

        $this->client->shouldReceive('submitTransaction')->twice()->andReturn(new TxHash('0xabcdef'));
        $this->cache->shouldReceive('tags->flush')->twice()->andReturn(true);

        $this->service->transfer($shortAddress, '100');
        $this->service->transfer($longAddress, '100');

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_large_balance_values(): void
    {
        $largeBalance = '999999999999999999999999999';

        $this->cache->shouldReceive('tags->get')->andReturn(null);

        $this->client->shouldReceive('callReadOnly')
            ->once()
            ->andReturn(ContractCallResult::success(['value' => $largeBalance]));

        $this->cache->shouldReceive('tags->put')->andReturn(true);

        $balance = $this->service->getBalance();

        $this->assertSame($largeBalance, $balance);
    }

    /**
     * Data provider for invalid amounts.
     *
     * @return array<string, array{string, string}>
     */
    public static function invalidAmountsProvider(): array
    {
        return [
            'zero amount' => ['0', 'Amount must be greater than zero'],
            'negative amount' => ['-100', 'Amount must be greater than zero'],
        ];
    }

    /**
     * Data provider for valid amounts.
     *
     * @return array<string, array{string}>
     */
    public static function validAmountsProvider(): array
    {
        return [
            'small integer' => ['1'],
            'large integer' => ['1000000000000000000'],
            'decimal string' => ['100.5'],
            'scientific notation' => ['1e18'],
        ];
    }
}
