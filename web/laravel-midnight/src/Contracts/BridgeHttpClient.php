<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Contracts;

/**
 * HTTP client interface for communicating with the Midnight bridge service.
 *
 * This interface defines the low-level HTTP communication layer for interacting
 * with the Midnight bridge service. Implementations should handle HTTP requests,
 * response parsing, error handling, and retry logic.
 */
interface BridgeHttpClient
{
    /**
     * Send a GET request to the bridge service.
     *
     * @param string $endpoint The API endpoint (relative path)
     * @param array<string, mixed> $query Optional query parameters
     * @return array<string, mixed> The decoded JSON response
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the request fails
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Send a POST request to the bridge service.
     *
     * @param string $endpoint The API endpoint (relative path)
     * @param array<string, mixed> $data The request payload
     * @return array<string, mixed> The decoded JSON response
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the request fails
     */
    public function post(string $endpoint, array $data = []): array;

    /**
     * Send a PUT request to the bridge service.
     *
     * @param string $endpoint The API endpoint (relative path)
     * @param array<string, mixed> $data The request payload
     * @return array<string, mixed> The decoded JSON response
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the request fails
     */
    public function put(string $endpoint, array $data = []): array;

    /**
     * Send a DELETE request to the bridge service.
     *
     * @param string $endpoint The API endpoint (relative path)
     * @return array<string, mixed> The decoded JSON response
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the request fails
     */
    public function delete(string $endpoint): array;

    /**
     * Perform a health check on the bridge service.
     *
     * @return bool True if the bridge is healthy, false otherwise
     */
    public function healthCheck(): bool;
}
