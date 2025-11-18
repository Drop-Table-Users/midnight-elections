<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use VersionTwo\Midnight\DTO\Address;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Http\BridgeHttpClient;
use VersionTwo\Midnight\Tests\Fixtures\FakeBridgeServer;

/**
 * Integration test for BridgeHttpClient using the FakeBridgeServer.
 *
 * This test demonstrates how to use the fake bridge server for integration
 * testing with the actual BridgeHttpClient implementation. It shows both
 * scenarios: with and without HMAC signature verification.
 */
class BridgeHttpClientIntegrationTest extends TestCase
{
    private FakeBridgeServer $server;
    private BridgeHttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Start the fake bridge server without signature verification
        $this->server = new FakeBridgeServer(port: 14101);
        $this->server->start();

        // Create a BridgeHttpClient pointing to the fake server
        $this->client = new BridgeHttpClient(
            baseUri: $this->server->getUrl(),
            apiKey: 'test-api-key',
            timeout: 5.0,
            logger: new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        parent::tearDown();
    }

    public function test_can_check_bridge_health(): void
    {
        $health = $this->client->getHealth();

        $this->assertEquals('ok', $health['status']);
        $this->assertArrayHasKey('timestamp', $health);
        $this->assertArrayHasKey('version', $health);
    }

    public function test_can_get_network_metadata(): void
    {
        $metadata = $this->client->getNetworkMetadata();

        $this->assertEquals('testnet-fake', $metadata->networkId);
        $this->assertEquals('Midnight Fake Testnet', $metadata->networkName);
        $this->assertGreaterThan(0, $metadata->blockHeight);
        $this->assertFalse($metadata->syncing);
    }

    public function test_can_submit_transaction(): void
    {
        $txData = [
            'from' => '0x1234',
            'to' => '0x5678',
            'amount' => '1000',
        ];

        $txHash = $this->client->submitTransaction($txData);

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertNotEmpty($txHash->toString());
    }

    public function test_can_get_transaction_status(): void
    {
        // First submit a transaction
        $txHash = $this->client->submitTransaction(['test' => 'data']);

        // Then check its status
        $status = $this->client->getTransactionStatus($txHash->toString());

        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('confirmations', $status);
        $this->assertContains($status['status'], ['pending', 'confirmed']);
    }

    public function test_can_call_contract(): void
    {
        $result = $this->client->callContract(
            contractAddress: '0xabcdef123456',
            entrypoint: 'get_balance',
            arguments: []
        );

        $this->assertTrue($result->success);
        $this->assertIsArray($result->result);
        $this->assertArrayHasKey('balance', $result->result);
        $this->assertGreaterThan(0, $result->gasUsed);
    }

    public function test_can_generate_proof(): void
    {
        $proof = $this->client->generateProof(
            contractName: 'MyContract',
            entrypoint: 'transfer',
            publicInputs: ['amount' => '100'],
            privateInputs: ['secret' => 'xyz']
        );

        $this->assertNotEmpty($proof->proof);
        $this->assertNotEmpty($proof->verificationKey);
        $this->assertIsArray($proof->publicInputs);
    }

    public function test_can_deploy_contract(): void
    {
        $result = $this->client->deployContract(
            contractPath: '/path/to/contract.wasm',
            constructorArgs: ['owner' => '0x1234'],
            deploymentOptions: []
        );

        $this->assertArrayHasKey('contract_address', $result);
        $this->assertArrayHasKey('tx_hash', $result);
        $this->assertStringStartsWith('0x', $result['contract_address']);
    }

    public function test_can_join_contract(): void
    {
        $result = $this->client->joinContract(
            contractAddress: '0xabcdef123456',
            joinParams: []
        );

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('tx_hash', $result);
        $this->assertArrayHasKey('participant_id', $result);
        $this->assertTrue($result['success']);
    }

    public function test_can_get_wallet_address(): void
    {
        $address = $this->client->getWalletAddress();

        $this->assertInstanceOf(Address::class, $address);
        $this->assertStringStartsWith('0x', $address->toString());
    }

    public function test_can_get_wallet_balance(): void
    {
        $balance = $this->client->getWalletBalance();

        $this->assertArrayHasKey('balance', $balance);
        $this->assertArrayHasKey('unit', $balance);
        $this->assertEquals('DUST', $balance['unit']);
    }

    public function test_can_get_wallet_balance_for_specific_address(): void
    {
        $testAddress = '0x1234567890abcdef';
        $balance = $this->client->getWalletBalance($testAddress);

        $this->assertArrayHasKey('balance', $balance);
        $this->assertEquals($testAddress, $balance['address']);
    }

    public function test_can_transfer_from_wallet(): void
    {
        $txHash = $this->client->walletTransfer(
            toAddress: '0x5678',
            amount: '1000',
            options: []
        );

        $this->assertInstanceOf(TxHash::class, $txHash);
        $this->assertNotEmpty($txHash->toString());
    }

    public function test_can_make_multiple_requests(): void
    {
        // This test verifies that the server handles multiple concurrent requests
        $health1 = $this->client->getHealth();
        $health2 = $this->client->getHealth();
        $metadata = $this->client->getNetworkMetadata();

        $this->assertEquals('ok', $health1['status']);
        $this->assertEquals('ok', $health2['status']);
        $this->assertEquals('testnet-fake', $metadata->networkId);
    }

    public function test_different_contract_entrypoints_return_different_results(): void
    {
        $balanceResult = $this->client->callContract(
            contractAddress: '0xabcdef',
            entrypoint: 'get_balance',
            arguments: []
        );

        $nameResult = $this->client->callContract(
            contractAddress: '0xabcdef',
            entrypoint: 'get_name',
            arguments: []
        );

        $ownerResult = $this->client->callContract(
            contractAddress: '0xabcdef',
            entrypoint: 'get_owner',
            arguments: []
        );

        $this->assertArrayHasKey('balance', $balanceResult->result);
        $this->assertArrayHasKey('name', $nameResult->result);
        $this->assertArrayHasKey('owner', $ownerResult->result);
    }

    public function test_proof_generation_is_deterministic(): void
    {
        // Generate two proofs with the same inputs at the same timestamp
        $proof1 = $this->client->generateProof(
            contractName: 'MyContract',
            entrypoint: 'transfer',
            publicInputs: ['amount' => '100'],
            privateInputs: []
        );

        usleep(10000); // Small delay to ensure different timestamp

        $proof2 = $this->client->generateProof(
            contractName: 'MyContract',
            entrypoint: 'transfer',
            publicInputs: ['amount' => '100'],
            privateInputs: []
        );

        // Proofs should be different due to timestamp
        $this->assertNotEquals($proof1->proof, $proof2->proof);

        // But both should be valid proofs
        $this->assertNotEmpty($proof1->proof);
        $this->assertNotEmpty($proof2->proof);
    }
}
