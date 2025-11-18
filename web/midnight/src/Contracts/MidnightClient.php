<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Contracts;

use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\DTO\ProofResponse;

/**
 * Low-level client interface for communicating with the Midnight bridge service.
 *
 * This interface defines the core methods for interacting with the Midnight network
 * through the bridge service. It handles network metadata retrieval, transaction
 * submission and status checking, read-only contract calls, proof generation, and
 * health monitoring.
 *
 * All methods in this interface should throw appropriate exceptions on failure
 * (e.g., NetworkException, ProofFailedException, MidnightException).
 */
interface MidnightClient
{
    /**
     * Retrieve network metadata from the Midnight network.
     *
     * This method fetches essential network information such as chain ID,
     * protocol parameters, and network configuration. The result should
     * typically be cached to minimize bridge calls.
     *
     * @return NetworkMetadata The network metadata including chain ID and protocol parameters
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the network is unreachable
     * @throws \VersionTwo\Midnight\Exceptions\MidnightException If the bridge returns an error
     */
    public function getNetworkMetadata(): NetworkMetadata;

    /**
     * Submit a transaction to the Midnight network.
     *
     * This method sends a contract call (either public or private) to the network
     * for processing. The transaction will be validated, potentially generate proofs
     * if needed, and then submitted to the blockchain.
     *
     * @param ContractCall $call The contract call to submit, containing contract address,
     *                           entrypoint, and public/private arguments
     * @return TxHash The transaction hash that can be used to track the transaction status
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the submission fails
     * @throws \VersionTwo\Midnight\Exceptions\ProofFailedException If proof generation fails
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If the contract call is invalid
     */
    public function submitTransaction(ContractCall $call): TxHash;

    /**
     * Get the current status of a submitted transaction.
     *
     * This method queries the network to check whether a transaction has been
     * confirmed, is pending, or has failed.
     *
     * @param string $txHash The transaction hash to check
     * @return array An associative array containing status information
     *               (will be replaced with a typed DTO in future versions)
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the status check fails
     * @throws \VersionTwo\Midnight\Exceptions\MidnightException If the transaction hash is invalid
     */
    public function getTransactionStatus(string $txHash): array;

    /**
     * Execute a read-only contract call.
     *
     * This method calls a contract method that only reads state without
     * modifying it. No transaction is submitted, and no fees are charged.
     * Results can be cached to improve performance.
     *
     * @param ContractCall $call The read-only contract call, typically with only
     *                           public arguments
     * @return ContractCallResult The result of the contract call, including any
     *                            returned data
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the call fails
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If the contract method reverts
     */
    public function callReadOnly(ContractCall $call): ContractCallResult;

    /**
     * Generate a zero-knowledge proof for a contract operation.
     *
     * This method communicates with the proof server (via the bridge) to generate
     * a ZK proof for private inputs. The proof can then be included in a transaction
     * to maintain privacy while proving correctness.
     *
     * @param ProofRequest $request The proof request containing circuit name, public
     *                              inputs, and private inputs
     * @return ProofResponse The generated proof along with any public outputs
     * @throws \VersionTwo\Midnight\Exceptions\ProofFailedException If proof generation fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the proof server is unreachable
     */
    public function generateProof(ProofRequest $request): ProofResponse;

    /**
     * Check if the bridge service and Midnight network are healthy and reachable.
     *
     * This method performs a quick health check to verify that the bridge service
     * is running and can communicate with the Midnight network. Useful for
     * monitoring and pre-flight checks.
     *
     * @return bool True if the bridge and network are healthy, false otherwise
     */
    public function healthCheck(): bool;
}
