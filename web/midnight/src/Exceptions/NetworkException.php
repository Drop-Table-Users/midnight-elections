<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Exceptions;

/**
 * Exception thrown when network or bridge communication errors occur.
 *
 * This exception is used for:
 * - Bridge service connectivity issues
 * - Midnight node RPC failures
 * - Network timeouts
 * - HTTP transport errors
 * - Connection refused errors
 *
 * @package VersionTwo\Midnight\Exceptions
 */
class NetworkException extends MidnightException
{
    /**
     * Create exception for bridge connection failure.
     *
     * @param string $baseUri The bridge base URI that failed
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function bridgeConnectionFailed(string $baseUri, ?\Throwable $previous = null): static
    {
        return new static(
            message: "Failed to connect to Midnight bridge at {$baseUri}",
            previous: $previous,
            context: ['bridge_uri' => $baseUri]
        );
    }

    /**
     * Create exception for bridge timeout.
     *
     * @param float $timeout The timeout value in seconds
     * @param string $endpoint The endpoint that timed out
     * @return static
     */
    public static function bridgeTimeout(float $timeout, string $endpoint): static
    {
        return new static(
            message: "Bridge request to {$endpoint} timed out after {$timeout} seconds",
            context: [
                'timeout' => $timeout,
                'endpoint' => $endpoint,
            ]
        );
    }

    /**
     * Create exception for invalid bridge response.
     *
     * @param int $statusCode The HTTP status code received
     * @param string $endpoint The endpoint that returned the error
     * @param string|null $responseBody The response body (if available)
     * @return static
     */
    public static function invalidBridgeResponse(int $statusCode, string $endpoint, ?string $responseBody = null): static
    {
        return new static(
            message: "Bridge returned invalid response (HTTP {$statusCode}) from {$endpoint}",
            context: [
                'status_code' => $statusCode,
                'endpoint' => $endpoint,
                'response_body' => $responseBody,
            ]
        );
    }

    /**
     * Create exception for Midnight node RPC failure.
     *
     * @param string $reason The failure reason
     * @param array<string, mixed> $context Additional context
     * @return static
     */
    public static function nodeRpcFailed(string $reason, array $context = []): static
    {
        return new static(
            message: "Midnight node RPC failed: {$reason}",
            context: array_merge(['reason' => $reason], $context)
        );
    }

    /**
     * Create exception for network unreachable.
     *
     * @param string $network The network name (e.g., 'devnet', 'mainnet')
     * @return static
     */
    public static function networkUnreachable(string $network): static
    {
        return new static(
            message: "Midnight network '{$network}' is unreachable",
            context: ['network' => $network]
        );
    }

    /**
     * Create exception for health check failure.
     *
     * @param string $reason The failure reason
     * @return static
     */
    public static function healthCheckFailed(string $reason): static
    {
        return new static(
            message: "Bridge health check failed: {$reason}",
            context: ['reason' => $reason]
        );
    }
}
