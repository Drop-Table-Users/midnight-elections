<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Http\Middleware;

use Psr\Http\Message\RequestInterface;

/**
 * HMAC request signing middleware for Guzzle HTTP client.
 *
 * This middleware implements HMAC-SHA256 signing for requests to the Midnight
 * bridge service. It signs the request with a timestamp, method, path, and body
 * to ensure request authenticity and integrity.
 *
 * The signature is added to the request as the X-Midnight-Signature header,
 * and a timestamp is added as the X-Midnight-Timestamp header for replay
 * protection.
 *
 * Signature format:
 * HMAC-SHA256(key, timestamp + method + path + body)
 *
 * Features:
 * - HMAC-SHA256 signing
 * - Timestamp-based replay protection
 * - Configurable signing algorithm
 * - Request body hashing
 *
 * @package VersionTwo\Midnight\Http\Middleware
 */
class RequestSigner
{
    /**
     * The signing key for HMAC.
     *
     * @var string
     */
    private string $signingKey;

    /**
     * The hashing algorithm to use.
     *
     * @var string
     */
    private string $algorithm;

    /**
     * The maximum allowed time skew in seconds for replay protection.
     *
     * @var int
     */
    private int $maxTimestampSkew;

    /**
     * Create a new RequestSigner middleware instance.
     *
     * @param string $signingKey The secret key to use for HMAC signing
     * @param string $algorithm The hashing algorithm (default: 'sha256')
     * @param int $maxTimestampSkew Maximum allowed timestamp skew in seconds (default: 300)
     */
    public function __construct(
        string $signingKey,
        string $algorithm = 'sha256',
        int $maxTimestampSkew = 300
    ) {
        $this->signingKey = $signingKey;
        $this->algorithm = $algorithm;
        $this->maxTimestampSkew = $maxTimestampSkew;
    }

    /**
     * Invoke the middleware to sign the request.
     *
     * This method is called by Guzzle's HandlerStack and returns a function
     * that will be used to process the request.
     *
     * @param callable $handler The next handler in the middleware stack
     * @return callable The wrapped handler
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $signedRequest = $this->signRequest($request);

            return $handler($signedRequest, $options);
        };
    }

    /**
     * Sign the HTTP request with HMAC.
     *
     * @param RequestInterface $request The request to sign
     * @return RequestInterface The signed request
     */
    private function signRequest(RequestInterface $request): RequestInterface
    {
        // Generate timestamp for replay protection
        $timestamp = (string) time();

        // Get request components for signing
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();

        if (!empty($query)) {
            $path .= '?' . $query;
        }

        // Get request body
        $body = (string) $request->getBody();

        // Construct the string to sign
        $stringToSign = $this->buildStringToSign($timestamp, $method, $path, $body);

        // Generate HMAC signature
        $signature = $this->generateSignature($stringToSign);

        // Add signature and timestamp headers to the request
        return $request
            ->withHeader('X-Midnight-Timestamp', $timestamp)
            ->withHeader('X-Midnight-Signature', $signature);
    }

    /**
     * Build the string to sign from request components.
     *
     * The string format is: timestamp + method + path + body_hash
     *
     * @param string $timestamp The request timestamp
     * @param string $method The HTTP method
     * @param string $path The request path (including query string)
     * @param string $body The request body
     * @return string The string to sign
     */
    private function buildStringToSign(
        string $timestamp,
        string $method,
        string $path,
        string $body
    ): string {
        // Hash the body to handle large payloads efficiently
        $bodyHash = hash($this->algorithm, $body);

        // Build the canonical string to sign
        return implode("\n", [
            $timestamp,
            $method,
            $path,
            $bodyHash,
        ]);
    }

    /**
     * Generate the HMAC signature for the string to sign.
     *
     * @param string $stringToSign The canonical string to sign
     * @return string The hexadecimal HMAC signature
     */
    private function generateSignature(string $stringToSign): string
    {
        return hash_hmac($this->algorithm, $stringToSign, $this->signingKey);
    }

    /**
     * Verify a signature against a request (for testing/validation).
     *
     * This method can be used to verify that a signature matches a request,
     * useful for testing or for implementing signature verification on the
     * receiving end.
     *
     * @param RequestInterface $request The request with signature headers
     * @param string $expectedSignature The expected signature
     * @return bool True if the signature is valid
     */
    public function verifySignature(RequestInterface $request, string $expectedSignature): bool
    {
        $timestampHeaders = $request->getHeader('X-Midnight-Timestamp');

        if (empty($timestampHeaders)) {
            return false;
        }

        $timestamp = $timestampHeaders[0];

        // Check timestamp freshness for replay protection
        if (!$this->isTimestampValid($timestamp)) {
            return false;
        }

        // Rebuild the signature
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();

        if (!empty($query)) {
            $path .= '?' . $query;
        }

        $body = (string) $request->getBody();
        $stringToSign = $this->buildStringToSign($timestamp, $method, $path, $body);
        $calculatedSignature = $this->generateSignature($stringToSign);

        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($expectedSignature, $calculatedSignature);
    }

    /**
     * Check if a timestamp is within the acceptable time skew window.
     *
     * This prevents replay attacks by rejecting requests with timestamps
     * that are too old or too far in the future.
     *
     * @param string $timestamp The timestamp to validate
     * @return bool True if the timestamp is valid
     */
    private function isTimestampValid(string $timestamp): bool
    {
        $now = time();
        $requestTime = (int) $timestamp;

        // Check if timestamp is within acceptable range
        $timeDifference = abs($now - $requestTime);

        return $timeDifference <= $this->maxTimestampSkew;
    }

    /**
     * Get the signing algorithm being used.
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Get the maximum timestamp skew allowed.
     *
     * @return int
     */
    public function getMaxTimestampSkew(): int
    {
        return $this->maxTimestampSkew;
    }

    /**
     * Set the maximum timestamp skew allowed.
     *
     * @param int $maxTimestampSkew The maximum skew in seconds
     * @return void
     */
    public function setMaxTimestampSkew(int $maxTimestampSkew): void
    {
        $this->maxTimestampSkew = $maxTimestampSkew;
    }
}
