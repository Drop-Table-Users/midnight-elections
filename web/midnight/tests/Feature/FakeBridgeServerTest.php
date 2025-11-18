<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Feature;

use PHPUnit\Framework\TestCase;
use VersionTwo\Midnight\Http\BridgeHttpClient;
use VersionTwo\Midnight\Tests\Fixtures\FakeBridgeServer;

/**
 * Example test demonstrating the FakeBridgeServer usage.
 *
 * This test shows how to start the fake bridge server, make requests to it,
 * and verify responses. The server can be used with or without HMAC signature
 * verification.
 */
class FakeBridgeServerTest extends TestCase
{
    private FakeBridgeServer $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Start the fake bridge server on port 14100
        $this->server = new FakeBridgeServer(port: 14100);
        $this->server->start();
    }

    protected function tearDown(): void
    {
        // Stop the server after each test
        $this->server->stop();

        parent::tearDown();
    }

    public function test_server_can_start_and_stop(): void
    {
        $this->assertTrue($this->server->isRunning());
        $this->assertEquals(14100, $this->server->getPort());
        $this->assertEquals('http://127.0.0.1:14100', $this->server->getUrl());

        $this->server->stop();
        $this->assertFalse($this->server->isRunning());
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->makeRequest('GET', '/health');

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('ok', $response['body']['status']);
        $this->assertArrayHasKey('timestamp', $response['body']);
        $this->assertArrayHasKey('version', $response['body']);
    }

    public function test_network_metadata_endpoint(): void
    {
        $response = $this->makeRequest('GET', '/network/metadata');

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('network_id', $response['body']);
        $this->assertArrayHasKey('block_height', $response['body']);
        $this->assertEquals('testnet-fake', $response['body']['network_id']);
    }

    public function test_tx_submit_endpoint(): void
    {
        $txData = [
            'from' => '0x1234',
            'to' => '0x5678',
            'amount' => '1000',
        ];

        $response = $this->makeRequest('POST', '/tx/submit', $txData);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('tx_hash', $response['body']);
        $this->assertArrayHasKey('status', $response['body']);
        $this->assertEquals('pending', $response['body']['status']);
    }

    public function test_tx_status_endpoint(): void
    {
        $txHash = 'abc123def456';

        $response = $this->makeRequest('GET', "/tx/{$txHash}/status");

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals($txHash, $response['body']['tx_hash']);
        $this->assertArrayHasKey('status', $response['body']);
        $this->assertArrayHasKey('confirmations', $response['body']);
    }

    public function test_contract_call_endpoint(): void
    {
        $callData = [
            'contract_address' => '0xabcdef',
            'entrypoint' => 'get_balance',
            'arguments' => [],
        ];

        $response = $this->makeRequest('POST', '/contract/call', $callData);

        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('result', $response['body']);
        $this->assertArrayHasKey('balance', $response['body']['result']);
    }

    public function test_proof_generate_endpoint(): void
    {
        $proofRequest = [
            'contract_name' => 'MyContract',
            'entrypoint' => 'transfer',
            'public_inputs' => ['amount' => '100'],
            'private_inputs' => ['secret' => 'xyz'],
        ];

        $response = $this->makeRequest('POST', '/proof/generate', $proofRequest);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('proof', $response['body']);
        $this->assertArrayHasKey('verification_key', $response['body']);
        $this->assertNotEmpty($response['body']['proof']);
    }

    public function test_contract_deploy_endpoint(): void
    {
        $deployData = [
            'contract_path' => '/path/to/contract.wasm',
            'constructor_args' => ['owner' => '0x1234'],
            'options' => [],
        ];

        $response = $this->makeRequest('POST', '/contract/deploy', $deployData);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('contract_address', $response['body']);
        $this->assertArrayHasKey('tx_hash', $response['body']);
        $this->assertStringStartsWith('0x', $response['body']['contract_address']);
    }

    public function test_contract_join_endpoint(): void
    {
        $joinData = [
            'contract_address' => '0xabcdef',
            'params' => [],
        ];

        $response = $this->makeRequest('POST', '/contract/join', $joinData);

        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('tx_hash', $response['body']);
        $this->assertArrayHasKey('participant_id', $response['body']);
    }

    public function test_wallet_address_endpoint(): void
    {
        $response = $this->makeRequest('GET', '/wallet/address');

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('address', $response['body']);
        $this->assertStringStartsWith('0x', $response['body']['address']);
        $this->assertArrayHasKey('public_key', $response['body']);
    }

    public function test_wallet_balance_endpoint(): void
    {
        $response = $this->makeRequest('GET', '/wallet/balance');

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('balance', $response['body']);
        $this->assertArrayHasKey('unit', $response['body']);
        $this->assertEquals('DUST', $response['body']['unit']);
    }

    public function test_wallet_transfer_endpoint(): void
    {
        $transferData = [
            'to_address' => '0x5678',
            'amount' => '1000',
        ];

        $response = $this->makeRequest('POST', '/wallet/transfer', $transferData);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('tx_hash', $response['body']);
        $this->assertEquals('pending', $response['body']['status']);
        $this->assertEquals('0x5678', $response['body']['to_address']);
    }

    public function test_unknown_endpoint_returns_404(): void
    {
        $response = $this->makeRequest('GET', '/unknown/endpoint');

        $this->assertEquals(404, $response['status_code']);
        $this->assertArrayHasKey('error', $response['body']);
    }

    /**
     * Helper method to make HTTP requests to the fake server.
     *
     * @param string $method The HTTP method
     * @param string $path The request path
     * @param array $data The request data (for POST requests)
     * @return array{status_code: int, body: array}
     */
    private function makeRequest(string $method, string $path, array $data = []): array
    {
        $url = $this->server->getUrl() . $path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
            ]);
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true) ?? [],
        ];
    }
}
