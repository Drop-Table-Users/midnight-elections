<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Fixtures;

use RuntimeException;

/**
 * Fake Bridge Server for Integration Testing
 *
 * A simple HTTP server that simulates the Midnight bridge API for testing purposes.
 * Uses PHP's built-in web server with a custom router script to handle bridge endpoints.
 *
 * Features:
 * - Implements all bridge endpoints that BridgeHttpClient calls
 * - Returns deterministic JSON responses
 * - Supports request validation
 * - Optional HMAC signature verification
 * - Configurable port (default: 14100)
 * - Request logging for debugging
 * - Easy start/stop for tests
 *
 * Usage:
 * ```php
 * $server = new FakeBridgeServer(port: 14100, signingKey: 'test-secret');
 * $server->start();
 * // ... run tests ...
 * $server->stop();
 * ```
 *
 * @package VersionTwo\Midnight\Tests\Fixtures
 */
class FakeBridgeServer
{
    /**
     * The port the server will listen on.
     */
    private int $port;

    /**
     * The hostname the server will bind to.
     */
    private string $host;

    /**
     * The process resource for the running server.
     */
    private mixed $process = null;

    /**
     * Pipes for communicating with the server process.
     *
     * @var array<int, resource>
     */
    private array $pipes = [];

    /**
     * Whether the server is currently running.
     */
    private bool $running = false;

    /**
     * The optional HMAC signing key for request verification.
     */
    private ?string $signingKey;

    /**
     * The hashing algorithm to use for HMAC verification.
     */
    private string $algorithm;

    /**
     * The path to the router script.
     */
    private string $routerScript;

    /**
     * The document root for the server.
     */
    private string $documentRoot;

    /**
     * Maximum time to wait for server startup in seconds.
     */
    private int $startupTimeout = 5;

    /**
     * Create a new FakeBridgeServer instance.
     *
     * @param int $port The port to listen on (default: 14100)
     * @param string $host The host to bind to (default: 127.0.0.1)
     * @param string|null $signingKey Optional HMAC signing key for request verification
     * @param string $algorithm The hashing algorithm for HMAC (default: sha256)
     */
    public function __construct(
        int $port = 14100,
        string $host = '127.0.0.1',
        ?string $signingKey = null,
        string $algorithm = 'sha256'
    ) {
        $this->port = $port;
        $this->host = $host;
        $this->signingKey = $signingKey;
        $this->algorithm = $algorithm;
        $this->routerScript = __DIR__ . '/bridge-server-router.php';
        $this->documentRoot = __DIR__;

        if (!file_exists($this->routerScript)) {
            throw new RuntimeException(
                "Router script not found at: {$this->routerScript}"
            );
        }
    }

    /**
     * Start the fake bridge server.
     *
     * @return void
     * @throws RuntimeException If the server fails to start
     */
    public function start(): void
    {
        if ($this->running) {
            return;
        }

        // Set environment variables for the router script
        $env = array_merge($_ENV, [
            'FAKE_BRIDGE_PORT' => (string) $this->port,
            'FAKE_BRIDGE_HOST' => $this->host,
            'FAKE_BRIDGE_SIGNING_KEY' => $this->signingKey ?? '',
            'FAKE_BRIDGE_ALGORITHM' => $this->algorithm,
        ]);

        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            PHP_BINARY,
            escapeshellarg($this->host),
            $this->port,
            escapeshellarg($this->documentRoot),
            escapeshellarg($this->routerScript)
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $this->process = proc_open(
            $command,
            $descriptorSpec,
            $this->pipes,
            $this->documentRoot,
            $env
        );

        if (!is_resource($this->process)) {
            throw new RuntimeException('Failed to start fake bridge server process');
        }

        // Set streams to non-blocking
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        // Wait for the server to be ready
        $this->waitForServerReady();

        $this->running = true;
    }

    /**
     * Stop the fake bridge server.
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running || !is_resource($this->process)) {
            return;
        }

        // Close pipes
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        // Terminate the process
        $status = proc_get_status($this->process);
        if ($status['running']) {
            proc_terminate($this->process);

            // Wait up to 2 seconds for graceful termination
            $timeout = 2;
            $start = time();
            while (time() - $start < $timeout) {
                $status = proc_get_status($this->process);
                if (!$status['running']) {
                    break;
                }
                usleep(100000); // 100ms
            }

            // Force kill if still running
            if ($status['running']) {
                proc_terminate($this->process, 9);
            }
        }

        proc_close($this->process);
        $this->process = null;
        $this->pipes = [];
        $this->running = false;
    }

    /**
     * Check if the server is running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        if (!$this->running || !is_resource($this->process)) {
            return false;
        }

        $status = proc_get_status($this->process);
        return $status['running'];
    }

    /**
     * Get the server URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    /**
     * Get the server port.
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get the server host.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get server output (stdout and stderr).
     *
     * Useful for debugging test failures.
     *
     * @return array{stdout: string, stderr: string}
     */
    public function getOutput(): array
    {
        $stdout = '';
        $stderr = '';

        if (isset($this->pipes[1]) && is_resource($this->pipes[1])) {
            $stdout = stream_get_contents($this->pipes[1]);
        }

        if (isset($this->pipes[2]) && is_resource($this->pipes[2])) {
            $stderr = stream_get_contents($this->pipes[2]);
        }

        return [
            'stdout' => $stdout ?: '',
            'stderr' => $stderr ?: '',
        ];
    }

    /**
     * Wait for the server to be ready by attempting to connect.
     *
     * @return void
     * @throws RuntimeException If the server doesn't become ready in time
     */
    private function waitForServerReady(): void
    {
        $start = time();
        $ready = false;

        while (time() - $start < $this->startupTimeout) {
            $connection = @fsockopen($this->host, $this->port, $errno, $errstr, 0.1);

            if ($connection !== false) {
                fclose($connection);
                $ready = true;
                break;
            }

            // Check if process is still running
            $status = proc_get_status($this->process);
            if (!$status['running']) {
                $output = $this->getOutput();
                throw new RuntimeException(
                    "Server process died during startup. STDERR: {$output['stderr']}"
                );
            }

            usleep(100000); // 100ms
        }

        if (!$ready) {
            $this->stop();
            throw new RuntimeException(
                "Server did not become ready within {$this->startupTimeout} seconds"
            );
        }

        // Give it a bit more time to fully initialize
        usleep(200000); // 200ms
    }

    /**
     * Destructor ensures the server is stopped when the object is destroyed.
     */
    public function __destruct()
    {
        $this->stop();
    }

    /**
     * Set the startup timeout.
     *
     * @param int $seconds
     * @return void
     */
    public function setStartupTimeout(int $seconds): void
    {
        $this->startupTimeout = $seconds;
    }
}
