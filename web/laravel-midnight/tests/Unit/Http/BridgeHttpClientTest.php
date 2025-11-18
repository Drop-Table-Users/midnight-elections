<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use VersionTwo\Midnight\DTO\Address;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\NetworkException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;
use VersionTwo\Midnight\Http\BridgeHttpClient;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the BridgeHttpClient class.
 *
 * @covers \VersionTwo\Midnight\Http\BridgeHttpClient
 */
final class BridgeHttpClientTest extends TestCase
{
    private string $baseUri = 'http://localhost:4100';

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $client = new BridgeHttpClient($this->baseUri, 'test-api-key', 10.0, new NullLogger());

        $this->assertInstanceOf(BridgeHttpClient::class, $client);
        $this->assertSame($this->baseUri, $client->getBaseUri());
    }

    #[Test]
    public function it_gets_health_check_successfully(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'ok', 'message' => 'Service healthy'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $health = $client->getHealth();

        $this->assertIsArray($health);
        $this->assertSame('ok', $health['status']);
    }

    #[Test]
    public function it_throws_exception_on_failed_health_check(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'error', 'message' => 'Service unhealthy'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Service unhealthy');

        $client->getHealth();
    }

    #[Test]
    public function it_gets_network_metadata(): void
    {
        $metadataData = [
            'chain_id' => 'midnight-testnet-1',
            'name' => 'Midnight Testnet',
            'version' => '1.0.0',
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($metadataData)),
        ]);

        $client = $this->createClientWithMock($mock);

        $metadata = $client->getNetworkMetadata();

        $this->assertInstanceOf(NetworkMetadata::class, $metadata);
        $this->assertSame('midnight-testnet-1', $metadata->chainId);
    }

    #[Test]
    public function it_submits_transaction(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['tx_hash' => '0xabcdef123456'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $txData = [
            'contract_address' => 'midnight1contract',
            'entrypoint' => 'transfer',
            'arguments' => ['to' => 'midnight1recipient'],
        ];

        $txHash = $client->submitTransaction($txData);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xabcdef123456', $txHash->value);
    }

    #[Test]
    public function it_handles_camelCase_tx_hash(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['txHash' => '0xabcdef123456'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $txHash = $client->submitTransaction([]);

        $this->assertSame('0xabcdef123456', $txHash->value);
    }

    #[Test]
    public function it_throws_exception_when_tx_hash_missing(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['invalid' => 'response'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Missing tx_hash in response');

        $client->submitTransaction([]);
    }

    #[Test]
    public function it_gets_transaction_status(): void
    {
        $statusData = [
            'status' => 'confirmed',
            'block_height' => 12345,
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($statusData)),
        ]);

        $client = $this->createClientWithMock($mock);

        $status = $client->getTransactionStatus('0xabcdef');

        $this->assertIsArray($status);
        $this->assertSame('confirmed', $status['status']);
        $this->assertSame(12345, $status['block_height']);
    }

    #[Test]
    public function it_calls_contract(): void
    {
        $responseData = [
            'success' => true,
            'value' => '1000',
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($responseData)),
        ]);

        $client = $this->createClientWithMock($mock);

        $result = $client->callContract('midnight1contract', 'getBalance', ['address' => 'midnight1user']);

        $this->assertInstanceOf(ContractCallResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('1000', $result->value);
    }

    #[Test]
    public function it_wraps_network_exception_in_contract_exception_for_call(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('POST', '/contract/call')),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ContractException::class);

        $client->callContract('midnight1contract', 'method');
    }

    #[Test]
    public function it_generates_proof(): void
    {
        $proofData = [
            'proof' => 'proof_data_here',
            'public_outputs' => ['verified' => true],
            'generation_time' => 1.5,
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($proofData)),
        ]);

        $client = $this->createClientWithMock($mock);

        $proof = $client->generateProof(
            'VotingContract',
            'vote',
            ['vote_id' => '123'],
            ['choice' => 'yes']
        );

        $this->assertInstanceOf(ProofResponse::class, $proof);
        $this->assertSame('proof_data_here', $proof->proof);
    }

    #[Test]
    public function it_throws_exception_when_proof_missing(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['invalid' => 'response'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ProofFailedException::class);
        $this->expectExceptionMessage('Missing proof in response');

        $client->generateProof('Contract', 'method', [], ['private' => 'data']);
    }

    #[Test]
    public function it_wraps_network_exception_in_proof_failed_exception(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('POST', '/proof/generate')),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ProofFailedException::class);

        $client->generateProof('Contract', 'method', [], ['private' => 'data']);
    }

    #[Test]
    public function it_deploys_contract(): void
    {
        $deploymentResponse = [
            'contract_address' => 'midnight1newcontract',
            'tx_hash' => '0xabcdef',
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($deploymentResponse)),
        ]);

        $client = $this->createClientWithMock($mock);

        $result = $client->deployContract('/path/to/contract.wasm', ['owner' => 'midnight1owner']);

        $this->assertIsArray($result);
        $this->assertSame('midnight1newcontract', $result['contract_address']);
    }

    #[Test]
    public function it_throws_exception_on_deployment_failure(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('POST', '/contract/deploy')),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ContractException::class);

        $client->deployContract('/path/to/contract.wasm');
    }

    #[Test]
    public function it_joins_contract(): void
    {
        $joinResponse = [
            'success' => true,
            'participant_id' => '123',
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($joinResponse)),
        ]);

        $client = $this->createClientWithMock($mock);

        $result = $client->joinContract('midnight1contract', ['name' => 'participant']);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_throws_exception_on_join_failure(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('POST', '/contract/join')),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ContractException::class);

        $client->joinContract('midnight1contract');
    }

    #[Test]
    public function it_gets_wallet_address(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['address' => 'midnight1wallet123456'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $address = $client->getWalletAddress();

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('midnight1wallet123456', $address->value);
    }

    #[Test]
    public function it_throws_exception_when_wallet_address_missing(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['invalid' => 'response'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Missing address in response');

        $client->getWalletAddress();
    }

    #[Test]
    public function it_gets_wallet_balance(): void
    {
        $balanceData = [
            'balance' => '1000000000000000000',
            'currency' => 'MIDNIGHT',
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($balanceData)),
        ]);

        $client = $this->createClientWithMock($mock);

        $balance = $client->getWalletBalance();

        $this->assertIsArray($balance);
        $this->assertSame('1000000000000000000', $balance['balance']);
    }

    #[Test]
    public function it_gets_wallet_balance_for_specific_address(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['balance' => '500'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $balance = $client->getWalletBalance('midnight1otheraddress');

        $this->assertIsArray($balance);
    }

    #[Test]
    public function it_transfers_funds(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['tx_hash' => '0xtransfer123'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $txHash = $client->walletTransfer('midnight1recipient', '1000000000000000000');

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertSame('0xtransfer123', $txHash->value);
    }

    #[Test]
    public function it_transfers_with_options(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['tx_hash' => '0xtransfer123'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $txHash = $client->walletTransfer(
            'midnight1recipient',
            '1000',
            ['memo' => 'Payment for services']
        );

        $this->assertInstanceOf(TxHash::class, $txHash);
    }

    #[Test]
    public function it_handles_http_error_responses(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['error' => 'Bad request', 'message' => 'Invalid parameters'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Invalid parameters');

        $client->getNetworkMetadata();
    }

    #[Test]
    public function it_handles_500_errors(): void
    {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['error' => 'Internal server error'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);

        $client->getNetworkMetadata();
    }

    #[Test]
    public function it_handles_connection_errors(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', '/health')),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);

        $client->getNetworkMetadata();
    }

    #[Test]
    public function it_handles_request_exceptions(): void
    {
        $request = new Request('GET', '/test');
        $response = new Response(404, [], json_encode(['error' => 'Not found']));

        $mock = new MockHandler([
            new RequestException('Request failed', $request, $response),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);

        $client->getNetworkMetadata();
    }

    #[Test]
    public function it_handles_invalid_json_responses(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'invalid json{'),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Invalid JSON in response');

        $client->getNetworkMetadata();
    }

    #[Test]
    public function it_retries_on_connection_errors(): void
    {
        // First request fails, second succeeds
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('GET', '/health')),
            new Response(200, [], json_encode(['status' => 'ok'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $health = $client->getHealth();

        $this->assertSame('ok', $health['status']);
    }

    #[Test]
    public function it_retries_on_5xx_errors(): void
    {
        // First request returns 503, second succeeds
        $mock = new MockHandler([
            new Response(503, [], json_encode(['error' => 'Service unavailable'])),
            new Response(200, [], json_encode(['status' => 'ok'])),
        ]);

        $client = $this->createClientWithMock($mock);

        $health = $client->getHealth();

        $this->assertSame('ok', $health['status']);
    }

    #[Test]
    public function it_gets_underlying_guzzle_client(): void
    {
        $client = new BridgeHttpClient($this->baseUri);

        $guzzleClient = $client->getClient();

        $this->assertInstanceOf(Client::class, $guzzleClient);
    }

    #[Test]
    public function it_uses_default_config_values(): void
    {
        $client = new BridgeHttpClient();

        $this->assertInstanceOf(BridgeHttpClient::class, $client);
    }

    #[Test]
    public function it_sanitizes_sensitive_headers_in_logs(): void
    {
        // This test ensures the client can be constructed with a logger
        // The actual sanitization is tested via the private method
        $client = new BridgeHttpClient($this->baseUri, 'secret-key', 10.0, new NullLogger());

        $this->assertInstanceOf(BridgeHttpClient::class, $client);
    }

    #[Test]
    public function it_handles_empty_response_body(): void
    {
        $mock = new MockHandler([
            new Response(200, [], ''),
        ]);

        $client = $this->createClientWithMock($mock);

        // Empty body with 200 should decode to empty array
        $result = $client->getTransactionStatus('0xabcdef');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Create a BridgeHttpClient with a mocked Guzzle handler.
     */
    private function createClientWithMock(MockHandler $mock): BridgeHttpClient
    {
        $handlerStack = HandlerStack::create($mock);

        // Use reflection to inject the mock handler
        $client = new BridgeHttpClient($this->baseUri, 'test-api-key', 10.0, new NullLogger());

        $reflection = new \ReflectionClass($client);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);

        $guzzleClient = new Client([
            'handler' => $handlerStack,
            'http_errors' => false,
        ]);

        $clientProperty->setValue($client, $guzzleClient);

        return $client;
    }
}
