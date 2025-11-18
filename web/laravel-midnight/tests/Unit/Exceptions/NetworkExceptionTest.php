<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use VersionTwo\Midnight\Exceptions\MidnightException;
use VersionTwo\Midnight\Exceptions\NetworkException;

#[CoversClass(NetworkException::class)]
final class NetworkExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_midnight_exception(): void
    {
        $exception = new NetworkException('Test');

        $this->assertInstanceOf(MidnightException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_creates_bridge_connection_failed_exception(): void
    {
        $baseUri = 'https://bridge.example.com';

        $exception = NetworkException::bridgeConnectionFailed($baseUri);

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertSame("Failed to connect to Midnight bridge at {$baseUri}", $exception->getMessage());
        $this->assertSame(['bridge_uri' => $baseUri], $exception->getContext());
        $this->assertNull($exception->getPrevious());
    }

    #[Test]
    public function it_creates_bridge_connection_failed_with_previous_exception(): void
    {
        $baseUri = 'https://bridge.example.com';
        $previous = new RuntimeException('Connection refused');

        $exception = NetworkException::bridgeConnectionFailed($baseUri, $previous);

        $this->assertSame("Failed to connect to Midnight bridge at {$baseUri}", $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(['bridge_uri' => $baseUri], $exception->getContext());
    }

    #[Test]
    #[DataProvider('bridgeUriProvider')]
    public function it_handles_various_bridge_uris(string $baseUri): void
    {
        $exception = NetworkException::bridgeConnectionFailed($baseUri);

        $this->assertStringContainsString($baseUri, $exception->getMessage());
        $this->assertSame($baseUri, $exception->getContext()['bridge_uri']);
    }

    #[Test]
    public function it_creates_bridge_timeout_exception(): void
    {
        $timeout = 30.0;
        $endpoint = '/api/v1/contract/deploy';

        $exception = NetworkException::bridgeTimeout($timeout, $endpoint);

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertSame(
            "Bridge request to {$endpoint} timed out after {$timeout} seconds",
            $exception->getMessage()
        );
        $this->assertSame([
            'timeout' => $timeout,
            'endpoint' => $endpoint,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('timeoutProvider')]
    public function it_handles_various_timeout_values(float $timeout, string $endpoint): void
    {
        $exception = NetworkException::bridgeTimeout($timeout, $endpoint);

        $this->assertStringContainsString((string) $timeout, $exception->getMessage());
        $this->assertStringContainsString($endpoint, $exception->getMessage());
        $this->assertSame($timeout, $exception->getContext()['timeout']);
        $this->assertSame($endpoint, $exception->getContext()['endpoint']);
    }

    #[Test]
    public function it_creates_invalid_bridge_response_exception(): void
    {
        $statusCode = 500;
        $endpoint = '/api/v1/proof/generate';

        $exception = NetworkException::invalidBridgeResponse($statusCode, $endpoint);

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertSame(
            "Bridge returned invalid response (HTTP {$statusCode}) from {$endpoint}",
            $exception->getMessage()
        );
        $this->assertSame([
            'status_code' => $statusCode,
            'endpoint' => $endpoint,
            'response_body' => null,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_invalid_bridge_response_with_body(): void
    {
        $statusCode = 404;
        $endpoint = '/api/v1/contract/state';
        $responseBody = '{"error": "Not found"}';

        $exception = NetworkException::invalidBridgeResponse($statusCode, $endpoint, $responseBody);

        $this->assertStringContainsString("HTTP {$statusCode}", $exception->getMessage());
        $this->assertStringContainsString($endpoint, $exception->getMessage());
        $this->assertSame($responseBody, $exception->getContext()['response_body']);
    }

    #[Test]
    #[DataProvider('httpStatusCodeProvider')]
    public function it_handles_various_http_status_codes(int $statusCode): void
    {
        $endpoint = '/api/test';

        $exception = NetworkException::invalidBridgeResponse($statusCode, $endpoint);

        $this->assertStringContainsString((string) $statusCode, $exception->getMessage());
        $this->assertSame($statusCode, $exception->getContext()['status_code']);
    }

    #[Test]
    public function it_creates_node_rpc_failed_exception(): void
    {
        $reason = 'RPC method not found';

        $exception = NetworkException::nodeRpcFailed($reason);

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertSame("Midnight node RPC failed: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    public function it_creates_node_rpc_failed_with_additional_context(): void
    {
        $reason = 'Invalid parameters';
        $context = [
            'method' => 'eth_getBalance',
            'params' => ['0x123'],
        ];

        $exception = NetworkException::nodeRpcFailed($reason, $context);

        $this->assertSame("Midnight node RPC failed: {$reason}", $exception->getMessage());
        $this->assertSame([
            'reason' => $reason,
            'method' => 'eth_getBalance',
            'params' => ['0x123'],
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_network_unreachable_exception(): void
    {
        $network = 'devnet';

        $exception = NetworkException::networkUnreachable($network);

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertSame("Midnight network '{$network}' is unreachable", $exception->getMessage());
        $this->assertSame(['network' => $network], $exception->getContext());
    }

    #[Test]
    #[DataProvider('networkNameProvider')]
    public function it_handles_various_network_names(string $network): void
    {
        $exception = NetworkException::networkUnreachable($network);

        $this->assertStringContainsString($network, $exception->getMessage());
        $this->assertSame($network, $exception->getContext()['network']);
    }

    #[Test]
    public function it_creates_health_check_failed_exception(): void
    {
        $reason = 'Service unavailable';

        $exception = NetworkException::healthCheckFailed($reason);

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertSame("Bridge health check failed: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    #[DataProvider('healthCheckReasonProvider')]
    public function it_handles_various_health_check_reasons(string $reason): void
    {
        $exception = NetworkException::healthCheckFailed($reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function all_factory_methods_return_network_exception_instance(): void
    {
        $methods = [
            NetworkException::bridgeConnectionFailed('https://bridge.test'),
            NetworkException::bridgeTimeout(30.0, '/endpoint'),
            NetworkException::invalidBridgeResponse(500, '/endpoint'),
            NetworkException::nodeRpcFailed('test'),
            NetworkException::networkUnreachable('devnet'),
            NetworkException::healthCheckFailed('test'),
        ];

        foreach ($methods as $exception) {
            $this->assertInstanceOf(NetworkException::class, $exception);
            $this->assertInstanceOf(MidnightException::class, $exception);
        }
    }

    #[Test]
    public function exception_chaining_works_correctly(): void
    {
        $root = new RuntimeException('Socket error');
        $network = NetworkException::bridgeConnectionFailed('https://bridge.test', $root);
        $wrapper = MidnightException::fromPrevious('Operation failed', $network);

        $this->assertSame($network, $wrapper->getPrevious());
        $this->assertSame($root, $wrapper->getPrevious()?->getPrevious());
    }

    public static function bridgeUriProvider(): array
    {
        return [
            'http' => ['http://localhost:8080'],
            'https' => ['https://bridge.midnight.network'],
            'with port' => ['https://bridge.example.com:3000'],
            'with path' => ['https://api.example.com/bridge/v1'],
            'localhost' => ['http://127.0.0.1:8080'],
        ];
    }

    public static function timeoutProvider(): array
    {
        return [
            'small timeout' => [1.0, '/api/quick'],
            'standard timeout' => [30.0, '/api/standard'],
            'large timeout' => [120.0, '/api/slow'],
            'fractional timeout' => [5.5, '/api/test'],
            'with complex endpoint' => [15.0, '/api/v1/contracts/0x123/state'],
        ];
    }

    public static function httpStatusCodeProvider(): array
    {
        return [
            '400 Bad Request' => [400],
            '401 Unauthorized' => [401],
            '403 Forbidden' => [403],
            '404 Not Found' => [404],
            '429 Too Many Requests' => [429],
            '500 Internal Server Error' => [500],
            '502 Bad Gateway' => [502],
            '503 Service Unavailable' => [503],
            '504 Gateway Timeout' => [504],
        ];
    }

    public static function networkNameProvider(): array
    {
        return [
            'devnet' => ['devnet'],
            'testnet' => ['testnet'],
            'mainnet' => ['mainnet'],
            'custom network' => ['custom-network-01'],
        ];
    }

    public static function healthCheckReasonProvider(): array
    {
        return [
            'service unavailable' => ['Service unavailable'],
            'database error' => ['Database connection failed'],
            'timeout' => ['Health check timeout after 5 seconds'],
            'invalid response' => ['Invalid health check response format'],
            'dependency failure' => ['Dependent service is down'],
        ];
    }
}
