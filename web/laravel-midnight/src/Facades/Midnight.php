<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Facades;

use Illuminate\Support\Facades\Facade;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\DTO\ProofResponse;

/**
 * Midnight Facade
 *
 * Provides static access to the MidnightClient for low-level communication
 * with the Midnight bridge service and blockchain network.
 *
 * @method static NetworkMetadata getNetworkMetadata() Retrieve network metadata from the Midnight network
 * @method static TxHash submitTransaction(ContractCall $call) Submit a transaction to the Midnight network
 * @method static array getTransactionStatus(string $txHash) Get the current status of a submitted transaction
 * @method static ContractCallResult callReadOnly(ContractCall $call) Execute a read-only contract call
 * @method static ProofResponse generateProof(ProofRequest $request) Generate a zero-knowledge proof for a contract operation
 * @method static bool healthCheck() Check if the bridge service and Midnight network are healthy and reachable
 *
 * @see \VersionTwo\Midnight\Contracts\MidnightClient
 * @see \VersionTwo\Midnight\Services\MidnightClientService
 */
class Midnight extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return MidnightClient::class;
    }
}
