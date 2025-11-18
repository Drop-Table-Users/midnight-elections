<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidnightTestController extends Controller
{
    /**
     * Display the Midnight/Lace wallet test page.
     */
    public function index()
    {
        return view('midnight.test', [
            'title' => 'Midnight & Lace Wallet Integration Test',
            'network' => config('midnight.network.name', 'testnet'),
            'bridgeUri' => config('midnight.bridge.base_uri', 'http://127.0.0.1:4100'),
        ]);
    }

    /**
     * API endpoint to check elections-api health.
     */
    public function checkBridge()
    {
        try {
            $client = new \GuzzleHttp\Client();
            $electionsApiUri = config('midnight.elections_api.base_uri', 'http://localhost:3000');
            $response = $client->get($electionsApiUri . '/health');

            return response()->json([
                'status' => 'success',
                'bridge' => 'connected',
                'data' => json_decode($response->getBody()->getContents(), true)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'bridge' => 'disconnected',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint to test contract interaction using elections-api.
     */
    public function testContractCall(Request $request)
    {
        // Increase PHP execution time to 3 minutes for blockchain operations
        set_time_limit(180);

        try {
            $validated = $request->validate([
                'action' => 'required|string|in:open,close,register,vote',
                'candidate_id' => 'required_if:action,register|nullable|string',
                'ballot_data' => 'nullable|array',
            ]);

            Log::info('Contract call test', $validated);

            $timeout = (float) config('midnight.elections_api.timeout', 180.0);
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'connect_timeout' => 10.0,
            ]);
            $electionsApiUri = config('midnight.elections_api.base_uri', 'http://localhost:3000');

            // Map actions to elections-api endpoints
            $endpoint = match($validated['action']) {
                'open' => '/open',
                'close' => '/close',
                'register' => '/register',
                'vote' => '/vote',
                default => throw new \Exception('Unknown action')
            };

            // Prepare request body based on action
            $requestBody = [];

            if ($validated['action'] === 'register' && isset($validated['candidate_id'])) {
                $requestBody['candidateId'] = $validated['candidate_id'];
            }

            if ($validated['action'] === 'vote' && isset($validated['ballot_data'])) {
                $requestBody['ballot'] = $validated['ballot_data'];
            }

            $response = $client->post($electionsApiUri . $endpoint, [
                'json' => $requestBody
            ]);

            return response()->json([
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true)
            ]);
        } catch (\Exception $e) {
            Log::error('Contract call failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint to get wallet status from elections-api.
     */
    public function walletStatus()
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10.0,
                'connect_timeout' => 5.0,
            ]);
            $electionsApiUri = config('midnight.elections_api.base_uri', 'http://localhost:3000');
            $response = $client->get($electionsApiUri . '/wallet-status');

            return response()->json([
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true)
            ]);
        } catch (\Exception $e) {
            Log::error('Wallet status check failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
