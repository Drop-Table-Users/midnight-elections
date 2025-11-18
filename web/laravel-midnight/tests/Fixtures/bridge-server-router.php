<?php

declare(strict_types=1);

/**
 * Fake Bridge Server Router Script
 *
 * This script handles HTTP requests for the fake bridge server.
 * It implements all the endpoints that BridgeHttpClient calls and returns
 * deterministic JSON responses for testing.
 *
 * This script is used by PHP's built-in web server via the FakeBridgeServer class.
 */

// Get configuration from environment variables
$signingKey = $_ENV['FAKE_BRIDGE_SIGNING_KEY'] ?? '';
$algorithm = $_ENV['FAKE_BRIDGE_ALGORITHM'] ?? 'sha256';
$verifySignatures = !empty($signingKey);

// Get request details
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Read request body
$body = file_get_contents('php://input');
$requestData = json_decode($body, true) ?? [];

// Log request for debugging
error_log(sprintf('[FakeBridge] %s %s', $method, $uri));

// Verify HMAC signature if enabled
if ($verifySignatures) {
    $timestamp = $_SERVER['HTTP_X_MIDNIGHT_TIMESTAMP'] ?? null;
    $signature = $_SERVER['HTTP_X_MIDNIGHT_SIGNATURE'] ?? null;

    if (!$timestamp || !$signature) {
        sendErrorResponse(401, 'Missing signature headers');
        return;
    }

    // Validate timestamp (5 minute window)
    $now = time();
    $requestTime = (int) $timestamp;
    if (abs($now - $requestTime) > 300) {
        sendErrorResponse(401, 'Request timestamp expired');
        return;
    }

    // Verify signature
    $bodyHash = hash($algorithm, $body);
    $path = $uri;
    if ($query) {
        $path .= '?' . $query;
    }

    $stringToSign = implode("\n", [
        $timestamp,
        strtoupper($method),
        $path,
        $bodyHash,
    ]);

    $expectedSignature = hash_hmac($algorithm, $stringToSign, $signingKey);

    if (!hash_equals($expectedSignature, $signature)) {
        sendErrorResponse(401, 'Invalid signature');
        return;
    }
}

// Route the request
$route = matchRoute($method, $uri);

if ($route === null) {
    sendErrorResponse(404, 'Endpoint not found');
    return;
}

// Call the route handler
call_user_func($route, $requestData);

/**
 * Match the request to a route handler.
 *
 * @param string $method The HTTP method
 * @param string $uri The request URI
 * @return callable|null The route handler or null if not found
 */
function matchRoute(string $method, string $uri): ?callable
{
    $routes = [
        'GET' => [
            '/health' => 'handleHealth',
            '/network/metadata' => 'handleNetworkMetadata',
            '/wallet/address' => 'handleWalletAddress',
            '/wallet/balance' => 'handleWalletBalance',
        ],
        'POST' => [
            '/tx/submit' => 'handleTxSubmit',
            '/contract/call' => 'handleContractCall',
            '/proof/generate' => 'handleProofGenerate',
            '/contract/deploy' => 'handleContractDeploy',
            '/contract/join' => 'handleContractJoin',
            '/wallet/transfer' => 'handleWalletTransfer',
        ],
    ];

    // Check for exact match
    if (isset($routes[$method][$uri])) {
        return $routes[$method][$uri];
    }

    // Check for dynamic routes (e.g., /tx/{hash}/status)
    if ($method === 'GET' && preg_match('#^/tx/([a-f0-9]+)/status$#', $uri, $matches)) {
        return function ($data) use ($matches) {
            handleTxStatus($matches[1], $data);
        };
    }

    return null;
}

/**
 * Handle GET /health
 */
function handleHealth(array $data): void
{
    sendJsonResponse([
        'status' => 'ok',
        'message' => 'Bridge service is healthy',
        'timestamp' => time(),
        'version' => '1.0.0-fake',
    ]);
}

/**
 * Handle GET /network/metadata
 */
function handleNetworkMetadata(array $data): void
{
    sendJsonResponse([
        'network_id' => 'testnet-fake',
        'network_name' => 'Midnight Fake Testnet',
        'chain_id' => '0x1234',
        'block_height' => 12345,
        'block_time' => 5,
        'protocol_version' => '1.0.0',
        'min_gas_price' => '1000000000',
        'peers' => 42,
        'syncing' => false,
    ]);
}

/**
 * Handle POST /tx/submit
 */
function handleTxSubmit(array $data): void
{
    // Validate required fields
    if (empty($data)) {
        sendErrorResponse(400, 'Missing transaction data');
        return;
    }

    // Generate a deterministic fake transaction hash
    $txHash = hash('sha256', json_encode($data) . time());

    sendJsonResponse([
        'tx_hash' => $txHash,
        'status' => 'pending',
        'timestamp' => time(),
    ]);
}

/**
 * Handle GET /tx/{hash}/status
 */
function handleTxStatus(string $txHash, array $data): void
{
    // Return a deterministic status based on the hash
    $status = (hexdec(substr($txHash, 0, 1)) % 3 === 0) ? 'confirmed' : 'pending';

    sendJsonResponse([
        'tx_hash' => $txHash,
        'status' => $status,
        'confirmations' => $status === 'confirmed' ? 6 : 0,
        'block_height' => $status === 'confirmed' ? 12346 : null,
        'timestamp' => time(),
    ]);
}

/**
 * Handle POST /contract/call
 */
function handleContractCall(array $data): void
{
    // Validate required fields
    if (!isset($data['contract_address']) || !isset($data['entrypoint'])) {
        sendErrorResponse(400, 'Missing contract_address or entrypoint');
        return;
    }

    // Return deterministic result based on entrypoint
    $entrypoint = $data['entrypoint'];
    $result = match ($entrypoint) {
        'get_balance' => ['balance' => '1000000000000000000'],
        'get_name' => ['name' => 'Fake Contract'],
        'get_owner' => ['owner' => '0x1234567890abcdef1234567890abcdef12345678'],
        'is_paused' => ['paused' => false],
        default => ['value' => 'fake_result_' . $entrypoint],
    };

    sendJsonResponse([
        'success' => true,
        'result' => $result,
        'gas_used' => 21000,
        'timestamp' => time(),
    ]);
}

/**
 * Handle POST /proof/generate
 */
function handleProofGenerate(array $data): void
{
    // Validate required fields
    if (!isset($data['contract_name']) || !isset($data['entrypoint'])) {
        sendErrorResponse(400, 'Missing contract_name or entrypoint');
        return;
    }

    // Generate a deterministic fake proof
    $proofData = json_encode([
        'contract_name' => $data['contract_name'],
        'entrypoint' => $data['entrypoint'],
        'public_inputs' => $data['public_inputs'] ?? [],
        'timestamp' => time(),
    ]);

    $proof = base64_encode(hash('sha256', $proofData, true));

    sendJsonResponse([
        'proof' => $proof,
        'public_inputs' => $data['public_inputs'] ?? [],
        'verification_key' => base64_encode('fake_vk_' . $data['contract_name']),
        'generated_at' => time(),
    ]);
}

/**
 * Handle POST /contract/deploy
 */
function handleContractDeploy(array $data): void
{
    // Validate required fields
    if (!isset($data['contract_path'])) {
        sendErrorResponse(400, 'Missing contract_path');
        return;
    }

    // Generate deterministic contract address and tx hash
    $contractAddress = '0x' . substr(hash('sha256', $data['contract_path'] . time()), 0, 40);
    $txHash = hash('sha256', json_encode($data) . time());

    sendJsonResponse([
        'contract_address' => $contractAddress,
        'tx_hash' => $txHash,
        'status' => 'pending',
        'constructor_args' => $data['constructor_args'] ?? [],
        'timestamp' => time(),
    ]);
}

/**
 * Handle POST /contract/join
 */
function handleContractJoin(array $data): void
{
    // Validate required fields
    if (!isset($data['contract_address'])) {
        sendErrorResponse(400, 'Missing contract_address');
        return;
    }

    // Generate a deterministic tx hash
    $txHash = hash('sha256', json_encode($data) . time());

    sendJsonResponse([
        'success' => true,
        'tx_hash' => $txHash,
        'contract_address' => $data['contract_address'],
        'participant_id' => hash('sha256', 'participant_' . time()),
        'timestamp' => time(),
    ]);
}

/**
 * Handle GET /wallet/address
 */
function handleWalletAddress(array $data): void
{
    sendJsonResponse([
        'address' => '0xfake1234567890abcdef1234567890abcdef1234',
        'public_key' => base64_encode('fake_public_key_data'),
        'timestamp' => time(),
    ]);
}

/**
 * Handle GET /wallet/balance
 */
function handleWalletBalance(array $data): void
{
    $address = $_GET['address'] ?? '0xfake1234567890abcdef1234567890abcdef1234';

    sendJsonResponse([
        'address' => $address,
        'balance' => '5000000000000000000',
        'balance_formatted' => '5.0',
        'unit' => 'DUST',
        'timestamp' => time(),
    ]);
}

/**
 * Handle POST /wallet/transfer
 */
function handleWalletTransfer(array $data): void
{
    // Validate required fields
    if (!isset($data['to_address']) || !isset($data['amount'])) {
        sendErrorResponse(400, 'Missing to_address or amount');
        return;
    }

    // Generate a deterministic tx hash
    $txHash = hash('sha256', json_encode($data) . time());

    sendJsonResponse([
        'tx_hash' => $txHash,
        'status' => 'pending',
        'from_address' => '0xfake1234567890abcdef1234567890abcdef1234',
        'to_address' => $data['to_address'],
        'amount' => $data['amount'],
        'timestamp' => time(),
    ]);
}

/**
 * Send a JSON response.
 *
 * @param array $data The response data
 * @param int $statusCode The HTTP status code
 */
function sendJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Send an error response.
 *
 * @param int $statusCode The HTTP status code
 * @param string $message The error message
 */
function sendErrorResponse(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $message,
        'status_code' => $statusCode,
        'timestamp' => time(),
    ], JSON_PRETTY_PRINT);
}
