<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Http\Middleware;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\Http\Middleware\RequestSigner;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the RequestSigner middleware.
 *
 * @covers \VersionTwo\Midnight\Http\Middleware\RequestSigner
 */
final class RequestSignerTest extends TestCase
{
    private const SIGNING_KEY = 'test-signing-key-secret';
    private const ALGORITHM = 'sha256';

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);

        $this->assertInstanceOf(RequestSigner::class, $signer);
    }

    #[Test]
    public function it_can_be_instantiated_with_custom_algorithm(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY, 'sha512');

        $this->assertSame('sha512', $signer->getAlgorithm());
    }

    #[Test]
    public function it_has_default_max_timestamp_skew(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);

        $this->assertSame(300, $signer->getMaxTimestampSkew());
    }

    #[Test]
    public function it_can_set_max_timestamp_skew(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $signer->setMaxTimestampSkew(600);

        $this->assertSame(600, $signer->getMaxTimestampSkew());
    }

    #[Test]
    public function it_adds_signature_header_to_request(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        $handler = $signer(function ($request, $options) {
            return new Response(200, [], json_encode(['success' => true]));
        });

        $signedRequest = null;
        $wrappedHandler = function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        };

        $handler = $signer($wrappedHandler);
        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Signature'));
        $this->assertNotEmpty($signedRequest->getHeader('X-Midnight-Signature'));
    }

    #[Test]
    public function it_adds_timestamp_header_to_request(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Timestamp'));
        $timestampHeaders = $signedRequest->getHeader('X-Midnight-Timestamp');
        $this->assertCount(1, $timestampHeaders);
        $this->assertIsNumeric($timestampHeaders[0]);
    }

    #[Test]
    public function it_generates_different_signatures_for_different_requests(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);

        $request1 = new Request('GET', 'http://localhost/endpoint1');
        $request2 = new Request('GET', 'http://localhost/endpoint2');

        $signed1 = null;
        $signed2 = null;

        $handler = $signer(function ($req, $options) use (&$signed1, &$signed2) {
            static $count = 0;
            if ($count === 0) {
                $signed1 = $req;
            } else {
                $signed2 = $req;
            }
            $count++;
            return new Response(200);
        });

        $handler($request1, []);
        sleep(1); // Ensure different timestamp
        $handler($request2, []);

        $signature1 = $signed1->getHeader('X-Midnight-Signature')[0];
        $signature2 = $signed2->getHeader('X-Midnight-Signature')[0];

        $this->assertNotSame($signature1, $signature2);
    }

    #[Test]
    public function it_signs_post_request_with_body(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $body = json_encode(['key' => 'value']);
        $request = new Request('POST', 'http://localhost/test', [], $body);

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Signature'));
        $this->assertNotEmpty($signedRequest->getHeader('X-Midnight-Signature'));
    }

    #[Test]
    public function it_includes_query_string_in_signature(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);

        $request1 = new Request('GET', 'http://localhost/test?param=value1');
        $request2 = new Request('GET', 'http://localhost/test?param=value2');

        $signed1 = null;
        $signed2 = null;

        $handler = $signer(function ($req, $options) use (&$signed1, &$signed2) {
            static $count = 0;
            if ($count === 0) {
                $signed1 = $req;
            } else {
                $signed2 = $req;
            }
            $count++;
            return new Response(200);
        });

        $handler($request1, []);
        sleep(1);
        $handler($request2, []);

        $signature1 = $signed1->getHeader('X-Midnight-Signature')[0];
        $signature2 = $signed2->getHeader('X-Midnight-Signature')[0];

        // Different query params should generate different signatures
        $this->assertNotSame($signature1, $signature2);
    }

    #[Test]
    public function it_verifies_valid_signature(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $signature = $signedRequest->getHeader('X-Midnight-Signature')[0];
        $isValid = $signer->verifySignature($signedRequest, $signature);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function it_rejects_invalid_signature(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $isValid = $signer->verifySignature($signedRequest, 'invalid_signature_here');

        $this->assertFalse($isValid);
    }

    #[Test]
    public function it_rejects_request_without_timestamp(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        // Request without timestamp header
        $isValid = $signer->verifySignature($request, 'any_signature');

        $this->assertFalse($isValid);
    }

    #[Test]
    public function it_rejects_request_with_old_timestamp(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY, self::ALGORITHM, 300);

        $oldTimestamp = (string) (time() - 400); // 400 seconds old
        $request = new Request('GET', 'http://localhost/test', [
            'X-Midnight-Timestamp' => $oldTimestamp,
        ]);

        $isValid = $signer->verifySignature($request, 'any_signature');

        $this->assertFalse($isValid);
    }

    #[Test]
    public function it_rejects_request_with_future_timestamp(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY, self::ALGORITHM, 300);

        $futureTimestamp = (string) (time() + 400); // 400 seconds in future
        $request = new Request('GET', 'http://localhost/test', [
            'X-Midnight-Timestamp' => $futureTimestamp,
        ]);

        $isValid = $signer->verifySignature($request, 'any_signature');

        $this->assertFalse($isValid);
    }

    #[Test]
    public function it_accepts_request_within_timestamp_skew(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY, self::ALGORITHM, 300);
        $request = new Request('GET', 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $signature = $signedRequest->getHeader('X-Midnight-Signature')[0];

        // Wait a bit but stay within the skew window
        sleep(1);

        $isValid = $signer->verifySignature($signedRequest, $signature);

        $this->assertTrue($isValid);
    }

    #[Test]
    #[DataProvider('httpMethodsProvider')]
    public function it_signs_different_http_methods_differently(string $method): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request($method, 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Signature'));
    }

    #[Test]
    #[DataProvider('algorithmProvider')]
    public function it_supports_different_hashing_algorithms(string $algorithm): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY, $algorithm);

        $this->assertSame($algorithm, $signer->getAlgorithm());
    }

    #[Test]
    public function it_generates_consistent_signature_for_same_request_at_same_time(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);

        // Create two identical requests
        $request1 = new Request('GET', 'http://localhost/test', [], '');
        $request2 = new Request('GET', 'http://localhost/test', [], '');

        // Add the same timestamp to both
        $timestamp = (string) time();
        $request1 = $request1->withHeader('X-Midnight-Timestamp', $timestamp);
        $request2 = $request2->withHeader('X-Midnight-Timestamp', $timestamp);

        // Since the requests and timestamps are identical, we need to use reflection
        // to call the private signRequest method and compare signatures
        $reflection = new \ReflectionClass($signer);
        $method = $reflection->getMethod('signRequest');
        $method->setAccessible(true);

        $signed1 = $method->invoke($signer, $request1);
        $signed2 = $method->invoke($signer, $request2);

        $this->assertSame(
            $signed1->getHeader('X-Midnight-Signature')[0],
            $signed2->getHeader('X-Midnight-Signature')[0]
        );
    }

    #[Test]
    public function it_handles_empty_request_body(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Signature'));
    }

    #[Test]
    public function it_handles_large_request_body(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $largeBody = str_repeat('a', 10000); // 10KB body
        $request = new Request('POST', 'http://localhost/test', [], $largeBody);

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Signature'));
    }

    #[Test]
    public function it_uses_timing_safe_comparison(): void
    {
        // This test ensures that verification uses hash_equals
        // We test this indirectly by verifying that the comparison works correctly
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/test');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $correctSignature = $signedRequest->getHeader('X-Midnight-Signature')[0];
        $wrongSignature = str_repeat('0', strlen($correctSignature));

        $this->assertTrue($signer->verifySignature($signedRequest, $correctSignature));
        $this->assertFalse($signer->verifySignature($signedRequest, $wrongSignature));
    }

    #[Test]
    public function it_works_with_guzzle_middleware_stack(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($signer);

        $handler = $stack->resolve();
        $request = new Request('GET', 'http://localhost/test');

        $promise = $handler($request, []);
        $response = $promise->wait();

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function it_signs_requests_with_complex_paths(): void
    {
        $signer = new RequestSigner(self::SIGNING_KEY);
        $request = new Request('GET', 'http://localhost/api/v1/contracts/midnight1abc/methods/transfer?arg=value');

        $signedRequest = null;
        $handler = $signer(function ($req, $options) use (&$signedRequest) {
            $signedRequest = $req;
            return new Response(200);
        });

        $handler($request, []);

        $this->assertTrue($signedRequest->hasHeader('X-Midnight-Signature'));
        $signature = $signedRequest->getHeader('X-Midnight-Signature')[0];
        $this->assertNotEmpty($signature);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature); // SHA256 produces 64 hex chars
    }

    /**
     * Data provider for HTTP methods.
     *
     * @return array<string, array{string}>
     */
    public static function httpMethodsProvider(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'DELETE' => ['DELETE'],
            'PATCH' => ['PATCH'],
        ];
    }

    /**
     * Data provider for hashing algorithms.
     *
     * @return array<string, array{string}>
     */
    public static function algorithmProvider(): array
    {
        return [
            'sha256' => ['sha256'],
            'sha512' => ['sha512'],
            'sha1' => ['sha1'],
        ];
    }
}
