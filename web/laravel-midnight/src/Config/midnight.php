<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midnight Integration Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the Midnight integration is enabled.
    | Set to false to disable all Midnight functionality.
    |
    */
    'enabled' => env('MIDNIGHT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Bridge Service Configuration
    |--------------------------------------------------------------------------
    |
    | The bridge service is a Node/TS microservice that handles low-level
    | Midnight operations including contract deployment, transaction submission,
    | ZK proof generation, and ledger queries.
    |
    */
    'bridge' => [
        'base_uri' => env('MIDNIGHT_BRIDGE_BASE_URI', 'http://127.0.0.1:4100'),
        'timeout' => (float) env('MIDNIGHT_BRIDGE_TIMEOUT', 10.0),
        'api_key' => env('MIDNIGHT_BRIDGE_API_KEY'),
        'signing' => [
            'enabled' => env('MIDNIGHT_BRIDGE_SIGNING', false),
            'key' => env('MIDNIGHT_BRIDGE_SIGNING_KEY'),
            'algo' => 'sha256',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Midnight network (devnet, testnet, or mainnet).
    |
    */
    'network' => [
        'name' => env('MIDNIGHT_NETWORK', 'devnet'),
        'chain_id' => env('MIDNIGHT_CHAIN_ID'),
        'explorer_uri' => env('MIDNIGHT_EXPLORER_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for network metadata and contract state.
    | This significantly improves performance by reducing bridge calls.
    |
    */
    'cache' => [
        'store' => env('MIDNIGHT_CACHE_STORE', 'redis'),
        'ttl' => [
            'network_metadata' => 3600, // 1 hour
            'contract_state' => 10, // 10 seconds
        ],
        'prefix' => 'midnight',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the queue for asynchronous transaction submissions.
    |
    */
    'queue' => [
        'connection' => env('MIDNIGHT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')),
        'queue' => env('MIDNIGHT_QUEUE_NAME', 'midnight'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Specify the logging channel for Midnight operations.
    | Set to null to use the default logging channel.
    |
    */
    'log_channel' => env('MIDNIGHT_LOG_CHANNEL'),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for bridge calls and RPC operations.
    |
    */
    'retry' => [
        'times' => (int) env('MIDNIGHT_RETRY_TIMES', 3),
        'sleep' => (int) env('MIDNIGHT_RETRY_SLEEP', 100), // milliseconds
        'backoff_multiplier' => 2,
    ],
];
