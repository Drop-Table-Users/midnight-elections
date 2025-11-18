<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Feature;

use PHPUnit\Framework\TestCase;
use VersionTwo\Midnight\Tests\Fixtures\FakeBridgeServer;

/**
 * Integration test for BridgeHttpClient with HMAC signature verification.
 *
 * This test demonstrates how to use the fake bridge server with HMAC signature
 * verification enabled. The server validates request signatures using the same
 * algorithm as the RequestSigner middleware.
 */
class BridgeHttpClientWithSigningTest extends TestCase
{
    private FakeBridgeServer $server;
    private string $signingKey = 'test-secret-key-for-hmac';

    protected function setUp(): void
    {
        parent::setUp();

        // Start the fake bridge server WITH signature verification
        $this->server = new FakeBridgeServer(
            port: 14102,
            signingKey: $this->signingKey,
            algorithm: 'sha256'
        );
        $this->server->start();
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        parent::tearDown();
    }

    public function test_request_without_signature_is_rejected(): void
    {
        $response = $this->makeUnsignedRequest('GET', '/health');

        $this->assertEquals(401, $response['status_code']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertStringContainsString('signature', strtolower($response['body']['error']));
    }

    public function test_request_with_invalid_signature_is_rejected(): void
    {
        $timestamp = (string) time();
        $invalidSignature = hash_hmac('sha256', 'invalid', 'wrong-key');

        $response = $this->makeRequest('GET', '/health', [], $timestamp, $invalidSignature);

        $this->assertEquals(401, $response['status_code']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('Invalid signature', $response['body']['error']);
    }

    public function test_request_with_expired_timestamp_is_rejected(): void
    {
        // Timestamp from 10 minutes ago (beyond the 5-minute window)
        $expiredTimestamp = (string) (time() - 600);
        $signature = $this->generateSignature('GET', '/health', '', $expiredTimestamp);

        $response = $this->makeRequest('GET', '/health', [], $expiredTimestamp, $signature);

        $this->assertEquals(401, $response['status_code']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('Request timestamp expired', $response['body']['error']);
    }

    public function test_request_with_valid_signature_is_accepted(): void
    {
        $response = $this->makeSignedRequest('GET', '/health');

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('ok', $response['body']['status']);
    }

    public function test_post_request_with_valid_signature_is_accepted(): void
    {
        $data = [
            'contract_address' => '0xabcdef',
            'entrypoint' => 'get_balance',
            'arguments' => [],
        ];

        $response = $this->makeSignedRequest('POST', '/contract/call', $data);

        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
    }

    public function test_signature_validation_includes_request_body(): void
    {
        // Make a signed request
        $data = ['test' => 'data'];
        $timestamp = (string) time();
        $body = json_encode($data);

        // Generate signature with correct body
        $signature = $this->generateSignature('POST', '/tx/submit', $body, $timestamp);

        // Send the request with the signature
        $response = $this->makeRequest('POST', '/tx/submit', $data, $timestamp, $signature);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('tx_hash', $response['body']);
    }

    public function test_signature_validation_includes_query_parameters(): void
    {
        $queryAddress = '0x1234567890abcdef';
        $path = "/wallet/balance?address={$queryAddress}";
        $timestamp = (string) time();

        // Generate signature including the query string
        $signature = $this->generateSignature('GET', $path, '', $timestamp);

        $url = $this->server->getUrl() . $path;
        $response = $this->makeRequestWithUrl('GET', $url, [], $timestamp, $signature);

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals($queryAddress, $response['body']['address']);
    }

    public function test_multiple_signed_requests_work_correctly(): void
    {
        // Make several different signed requests
        $health = $this->makeSignedRequest('GET', '/health');
        $metadata = $this->makeSignedRequest('GET', '/network/metadata');
        $address = $this->makeSignedRequest('GET', '/wallet/address');

        $this->assertEquals(200, $health['status_code']);
        $this->assertEquals(200, $metadata['status_code']);
        $this->assertEquals(200, $address['status_code']);

        $this->assertEquals('ok', $health['body']['status']);
        $this->assertEquals('testnet-fake', $metadata['body']['network_id']);
        $this->assertArrayHasKey('address', $address['body']);
    }

    /**
     * Generate an HMAC signature for a request.
     *
     * This mimics the RequestSigner middleware's signature generation.
     *
     * @param string $method The HTTP method
     * @param string $path The request path (including query string)
     * @param string $body The request body
     * @param string $timestamp The request timestamp
     * @return string The HMAC signature
     */
    private function generateSignature(
        string $method,
        string $path,
        string $body,
        string $timestamp
    ): string {
        $bodyHash = hash('sha256', $body);

        $stringToSign = implode("\n", [
            $timestamp,
            strtoupper($method),
            $path,
            $bodyHash,
        ]);

        return hash_hmac('sha256', $stringToSign, $this->signingKey);
    }

    /**
     * Make a signed HTTP request to the fake server.
     *
     * @param string $method The HTTP method
     * @param string $path The request path
     * @param array $data The request data (for POST requests)
     * @return array{status_code: int, body: array}
     */
    private function makeSignedRequest(string $method, string $path, array $data = []): array
    {
        $timestamp = (string) time();
        $body = $method === 'POST' ? json_encode($data) : '';
        $signature = $this->generateSignature($method, $path, $body, $timestamp);

        return $this->makeRequest($method, $path, $data, $timestamp, $signature);
    }

    /**
     * Make an unsigned HTTP request to the fake server.
     *
     * @param string $method The HTTP method
     * @param string $path The request path
     * @param array $data The request data (for POST requests)
     * @return array{status_code: int, body: array}
     */
    private function makeUnsignedRequest(string $method, string $path, array $data = []): array
    {
        $url = $this->server->getUrl() . $path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $headers = [
            'Accept: application/json',
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true) ?? [],
        ];
    }

    /**
     * Make an HTTP request with signature headers.
     *
     * @param string $method The HTTP method
     * @param string $path The request path
     * @param array $data The request data
     * @param string $timestamp The request timestamp
     * @param string $signature The HMAC signature
     * @return array{status_code: int, body: array}
     */
    private function makeRequest(
        string $method,
        string $path,
        array $data,
        string $timestamp,
        string $signature
    ): array {
        $url = $this->server->getUrl() . $path;
        return $this->makeRequestWithUrl($method, $url, $data, $timestamp, $signature);
    }

    /**
     * Make an HTTP request with signature headers to a full URL.
     *
     * @param string $method The HTTP method
     * @param string $url The full URL
     * @param array $data The request data
     * @param string $timestamp The request timestamp
     * @param string $signature The HMAC signature
     * @return array{status_code: int, body: array}
     */
    private function makeRequestWithUrl(
        string $method,
        string $url,
        array $data,
        string $timestamp,
        string $signature
    ): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $headers = [
            'Accept: application/json',
            'X-Midnight-Timestamp: ' . $timestamp,
            'X-Midnight-Signature: ' . $signature,
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true) ?? [],
        ];
    }
}
