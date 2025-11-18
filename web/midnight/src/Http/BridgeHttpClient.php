<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use VersionTwo\Midnight\Contracts\BridgeHttpClient as BridgeHttpClientContract;
use VersionTwo\Midnight\DTO\Address;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\NetworkException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;
use VersionTwo\Midnight\Http\Middleware\RequestSigner;

/**
 * HTTP client for communicating with the Midnight Bridge service.
 *
 * This client handles all communication with the Node/TS bridge microservice,
 * including contract operations, transaction submission, proof generation,
 * and wallet operations. It provides automatic retry logic, request signing,
 * connection pooling, and response mapping to DTOs.
 *
 * Features:
 * - Automatic API key injection
 * - Optional HMAC request signing
 * - Exponential backoff retry logic
 * - Connection pooling and keep-alive
 * - JSON encoding/decoding
 * - Response mapping to DTOs
 * - Typed exception handling
 * - PSR-3 logging support
 *
 * @package VersionTwo\Midnight\Http
 */
class BridgeHttpClient implements BridgeHttpClientContract
{
    /**
     * The Guzzle HTTP client instance.
     *
     * @var Client
     */
    private Client $client;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * The base URI for the bridge service.
     *
     * @var string
     */
    private string $baseUri;

    /**
     * Create a new BridgeHttpClient instance.
     *
     * @param string|null $baseUri Optional base URI override
     * @param string|null $apiKey Optional API key override
     * @param float|null $timeout Optional timeout override (in seconds)
     * @param LoggerInterface|null $logger Optional logger instance
     */
    public function __construct(
        ?string $baseUri = null,
        ?string $apiKey = null,
        ?float $timeout = null,
        ?LoggerInterface $logger = null
    ) {
        $this->baseUri = $baseUri ?? config('midnight.bridge.base_uri', 'http://127.0.0.1:4100');
        $this->logger = $logger ?? new NullLogger();

        // Build handler stack with middleware
        $stack = HandlerStack::create();

        // Add request signing middleware if enabled
        if (config('midnight.bridge.signing.enabled', false)) {
            $signingKey = config('midnight.bridge.signing.key');
            $signingAlgo = config('midnight.bridge.signing.algo', 'sha256');

            if ($signingKey) {
                $stack->push(new RequestSigner($signingKey, $signingAlgo));
            }
        }

        // Add retry middleware with exponential backoff
        $stack->push($this->createRetryMiddleware());

        // Add logging middleware
        $stack->push($this->createLoggingMiddleware());

        // Configure Guzzle client
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => $timeout ?? config('midnight.bridge.timeout', 10.0),
            'handler' => $stack,
            'headers' => array_filter([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Midnight-Laravel/1.0',
                'X-API-Key' => $apiKey ?? config('midnight.bridge.api_key'),
            ]),
            'http_errors' => false, // We'll handle errors manually
            'connect_timeout' => 5.0,
            'verify' => config('app.env') === 'production',
            // Connection pooling and keep-alive
            'curl' => [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 120,
                CURLOPT_TCP_KEEPINTVL => 60,
            ],
        ]);
    }

    /**
     * Check the health status of the bridge service.
     *
     * @return array<string, mixed> Health check response data
     * @throws NetworkException If the health check fails
     */
    public function getHealth(): array
    {
        try {
            $response = $this->get('/health');

            if (!isset($response['status']) || $response['status'] !== 'ok') {
                throw NetworkException::healthCheckFailed(
                    $response['message'] ?? 'Unknown health check failure'
                );
            }

            return $response;
        } catch (NetworkException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw NetworkException::healthCheckFailed($e->getMessage());
        }
    }

    /**
     * Get network metadata from the bridge.
     *
     * @return NetworkMetadata The network metadata
     * @throws NetworkException If the request fails
     */
    public function getNetworkMetadata(): NetworkMetadata
    {
        $response = $this->get('/network/metadata');

        return NetworkMetadata::fromArray($response);
    }

    /**
     * Submit a transaction to the network.
     *
     * @param array<string, mixed> $txData The transaction data
     * @return TxHash The transaction hash
     * @throws NetworkException If the submission fails
     */
    public function submitTransaction(array $txData): TxHash
    {
        $response = $this->post('/tx/submit', $txData);

        if (!isset($response['tx_hash']) && !isset($response['txHash'])) {
            throw NetworkException::invalidBridgeResponse(
                500,
                '/tx/submit',
                'Missing tx_hash in response'
            );
        }

        return TxHash::fromString($response['tx_hash'] ?? $response['txHash']);
    }

    /**
     * Get the status of a transaction.
     *
     * @param string $txHash The transaction hash
     * @return array<string, mixed> Transaction status data
     * @throws NetworkException If the request fails
     */
    public function getTransactionStatus(string $txHash): array
    {
        return $this->get("/tx/{$txHash}/status");
    }

    /**
     * Call a contract method (read-only).
     *
     * @param string $contractAddress The contract address
     * @param string $entrypoint The contract entrypoint/method name
     * @param array<string, mixed> $arguments The method arguments
     * @return ContractCallResult The call result
     * @throws ContractException If the call fails
     */
    public function callContract(
        string $contractAddress,
        string $entrypoint,
        array $arguments = []
    ): ContractCallResult {
        try {
            $response = $this->post('/contract/call', [
                'contract_address' => $contractAddress,
                'entrypoint' => $entrypoint,
                'arguments' => $arguments,
            ]);

            return ContractCallResult::fromArray($response);
        } catch (NetworkException $e) {
            throw ContractException::callFailed(
                $contractAddress,
                $entrypoint,
                $e->getMessage(),
                ['original_error' => $e]
            );
        }
    }

    /**
     * Generate a zero-knowledge proof.
     *
     * @param string $contractName The contract name
     * @param string $entrypoint The contract entrypoint
     * @param array<string, mixed> $publicInputs Public inputs for the proof
     * @param array<string, mixed> $privateInputs Private inputs for the proof
     * @return ProofResponse The proof response
     * @throws ProofFailedException If proof generation fails
     */
    public function generateProof(
        string $contractName,
        string $entrypoint,
        array $publicInputs = [],
        array $privateInputs = []
    ): ProofResponse {
        try {
            $response = $this->post('/proof/generate', [
                'contract_name' => $contractName,
                'entrypoint' => $entrypoint,
                'public_inputs' => $publicInputs,
                'private_inputs' => $privateInputs,
            ]);

            if (!isset($response['proof'])) {
                throw ProofFailedException::generationFailed(
                    $contractName,
                    $entrypoint,
                    'Missing proof in response',
                    $response
                );
            }

            return ProofResponse::fromArray($response);
        } catch (NetworkException $e) {
            throw ProofFailedException::generationFailed(
                $contractName,
                $entrypoint,
                $e->getMessage(),
                ['original_error' => $e]
            );
        }
    }

    /**
     * Deploy a new contract to the network.
     *
     * @param string $contractPath The path to the compiled contract
     * @param array<string, mixed> $constructorArgs Constructor arguments
     * @param array<string, mixed> $deploymentOptions Deployment options
     * @return array<string, mixed> Deployment response with contract address and tx hash
     * @throws ContractException If deployment fails
     */
    public function deployContract(
        string $contractPath,
        array $constructorArgs = [],
        array $deploymentOptions = []
    ): array {
        try {
            return $this->post('/contract/deploy', [
                'contract_path' => $contractPath,
                'constructor_args' => $constructorArgs,
                'options' => $deploymentOptions,
            ]);
        } catch (NetworkException $e) {
            throw ContractException::deploymentFailed(
                $e->getMessage(),
                $contractPath,
                ['original_error' => $e]
            );
        }
    }

    /**
     * Join an existing contract as a participant.
     *
     * @param string $contractAddress The contract address to join
     * @param array<string, mixed> $joinParams Join parameters
     * @return array<string, mixed> Join response
     * @throws ContractException If joining fails
     */
    public function joinContract(string $contractAddress, array $joinParams = []): array
    {
        try {
            return $this->post('/contract/join', [
                'contract_address' => $contractAddress,
                'params' => $joinParams,
            ]);
        } catch (NetworkException $e) {
            throw ContractException::joinFailed(
                $contractAddress,
                $e->getMessage()
            );
        }
    }

    /**
     * Get the wallet address.
     *
     * @return Address The wallet address
     * @throws NetworkException If the request fails
     */
    public function getWalletAddress(): Address
    {
        $response = $this->get('/wallet/address');

        if (!isset($response['address'])) {
            throw NetworkException::invalidBridgeResponse(
                500,
                '/wallet/address',
                'Missing address in response'
            );
        }

        return Address::fromString($response['address']);
    }

    /**
     * Get the wallet balance.
     *
     * @param string|null $address Optional address to check (defaults to wallet address)
     * @return array<string, mixed> Balance data
     * @throws NetworkException If the request fails
     */
    public function getWalletBalance(?string $address = null): array
    {
        $endpoint = '/wallet/balance';

        if ($address !== null) {
            $endpoint .= '?' . http_build_query(['address' => $address]);
        }

        return $this->get($endpoint);
    }

    /**
     * Transfer funds from the wallet.
     *
     * @param string $toAddress The recipient address
     * @param string $amount The amount to transfer
     * @param array<string, mixed> $options Transfer options
     * @return TxHash The transaction hash
     * @throws NetworkException If the transfer fails
     */
    public function walletTransfer(
        string $toAddress,
        string $amount,
        array $options = []
    ): TxHash {
        $response = $this->post('/wallet/transfer', array_merge([
            'to_address' => $toAddress,
            'amount' => $amount,
        ], $options));

        if (!isset($response['tx_hash']) && !isset($response['txHash'])) {
            throw NetworkException::invalidBridgeResponse(
                500,
                '/wallet/transfer',
                'Missing tx_hash in response'
            );
        }

        return TxHash::fromString($response['tx_hash'] ?? $response['txHash']);
    }

    /**
     * Perform a GET request to the bridge.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $query Optional query parameters
     * @return array<string, mixed> The decoded response
     * @throws NetworkException If the request fails
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, [
            'query' => $query,
        ]);
    }

    /**
     * Perform a POST request to the bridge.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data The request body data
     * @return array<string, mixed> The decoded response
     * @throws NetworkException If the request fails
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Perform a PUT request to the bridge.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data The request body data
     * @return array<string, mixed> The decoded response
     * @throws NetworkException If the request fails
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Perform a DELETE request to the bridge.
     *
     * @param string $endpoint The API endpoint
     * @return array<string, mixed> The decoded response
     * @throws NetworkException If the request fails
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Perform a health check on the bridge service.
     *
     * @return bool True if the bridge is healthy, false otherwise
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->client->get('/health');
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->error('Health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Perform an HTTP request to the bridge.
     *
     * @param string $method The HTTP method
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $options Guzzle request options
     * @return array<string, mixed> The decoded response
     * @throws NetworkException If the request fails
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);

            return $this->handleResponse($response, $endpoint);
        } catch (ConnectException $e) {
            $this->logger->error('Bridge connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw NetworkException::bridgeConnectionFailed($this->baseUri, $e);
        } catch (RequestException $e) {
            $this->logger->error('Bridge request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                return $this->handleResponse($response, $endpoint);
            }

            throw NetworkException::bridgeConnectionFailed($this->baseUri, $e);
        } catch (GuzzleException $e) {
            $this->logger->error('Bridge HTTP error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw NetworkException::bridgeConnectionFailed($this->baseUri, $e);
        }
    }

    /**
     * Handle the HTTP response from the bridge.
     *
     * @param ResponseInterface $response The HTTP response
     * @param string $endpoint The endpoint that was called
     * @return array<string, mixed> The decoded response
     * @throws NetworkException If the response indicates an error
     */
    private function handleResponse(ResponseInterface $response, string $endpoint): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // Attempt to decode JSON response
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE && $statusCode < 400) {
            throw NetworkException::invalidBridgeResponse(
                $statusCode,
                $endpoint,
                'Invalid JSON in response: ' . json_last_error_msg()
            );
        }

        // Handle error responses
        if ($statusCode >= 400) {
            $errorMessage = $data['error'] ?? $data['message'] ?? 'Unknown error';

            $this->logger->warning('Bridge returned error', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);

            throw NetworkException::invalidBridgeResponse($statusCode, $endpoint, $errorMessage);
        }

        return $data ?? [];
    }

    /**
     * Create a retry middleware with exponential backoff.
     *
     * @return callable The retry middleware
     */
    private function createRetryMiddleware(): callable
    {
        $retryTimes = config('midnight.retry.times', 3);
        $retrySleep = config('midnight.retry.sleep', 100);
        $backoffMultiplier = config('midnight.retry.backoff_multiplier', 2);

        return GuzzleMiddleware::retry(
            function (
                int $retries,
                RequestInterface $request,
                ?ResponseInterface $response = null,
                ?\Exception $exception = null
            ) use ($retryTimes) {
                // Don't retry if we've exhausted our retry limit
                if ($retries >= $retryTimes) {
                    return false;
                }

                // Retry on connection errors
                if ($exception instanceof ConnectException) {
                    return true;
                }

                // Retry on 5xx server errors
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                // Retry on timeout (408)
                if ($response && $response->getStatusCode() === 408) {
                    return true;
                }

                return false;
            },
            function (int $retries) use ($retrySleep, $backoffMultiplier) {
                // Exponential backoff: sleep * (multiplier ^ retries)
                return (int) ($retrySleep * pow($backoffMultiplier, $retries));
            }
        );
    }

    /**
     * Create a logging middleware for requests and responses.
     *
     * @return callable The logging middleware
     */
    private function createLoggingMiddleware(): callable
    {
        return GuzzleMiddleware::tap(
            function (RequestInterface $request) {
                $this->logger->debug('Bridge request', [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'headers' => $this->sanitizeHeaders($request->getHeaders()),
                ]);
            },
            function (RequestInterface $request, RequestInterface $options, ResponseInterface $response) {
                $this->logger->debug('Bridge response', [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'status_code' => $response->getStatusCode(),
                ]);
            }
        );
    }

    /**
     * Sanitize headers to remove sensitive information from logs.
     *
     * @param array<string, mixed> $headers The request headers
     * @return array<string, mixed> Sanitized headers
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['X-API-Key', 'Authorization', 'X-Midnight-Signature'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Get the underlying Guzzle client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the base URI.
     *
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
}
