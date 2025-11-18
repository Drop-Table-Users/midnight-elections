<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Contracts;

use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;

/**
 * Higher-level contract abstraction interface for application code.
 *
 * This interface provides a developer-friendly API for deploying contracts,
 * joining existing contracts, calling contract methods, and reading contract state.
 * It abstracts away the low-level details of contract interaction and provides
 * a clean, Laravel-style API.
 */
interface ContractGateway
{
    /**
     * Deploy a new contract to the Midnight network.
     *
     * This method takes a compiled contract (typically a .cmpct file or similar)
     * and deploys it to the network with optional initialization arguments.
     * The contract will be deployed using the configured wallet.
     *
     * @param string $compiledContractPath Absolute or relative path to the compiled
     *                                     contract file
     * @param array<string, mixed> $initArgs Optional initialization arguments for the
     *                                       contract constructor
     * @return string The deployed contract address on the Midnight network
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If deployment fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the network is unreachable
     * @throws \InvalidArgumentException If the contract file doesn't exist or is invalid
     */
    public function deploy(string $compiledContractPath, array $initArgs = []): string;

    /**
     * Join an existing contract on the Midnight network.
     *
     * This method allows the current wallet to join a deployed contract,
     * which may be required before interacting with certain contract types.
     * This is particularly relevant for contracts that maintain a participant list.
     *
     * @param string $contractAddress The address of the contract to join
     * @param array<string, mixed> $args Optional arguments required for joining
     * @return ContractCallResult The result of the join operation, may include
     *                            confirmation data or participant info
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If joining fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the network is unreachable
     * @throws \InvalidArgumentException If the contract address is invalid
     */
    public function join(string $contractAddress, array $args = []): ContractCallResult;

    /**
     * Call a contract method that modifies state.
     *
     * This method submits a transaction to invoke a contract entrypoint.
     * It supports both public arguments (visible on-chain) and private arguments
     * (used for ZK proof generation but not revealed on-chain).
     *
     * The method returns immediately with a transaction hash. Use the MidnightClient
     * to poll for transaction confirmation if needed.
     *
     * @param string $contractAddress The address of the contract to call
     * @param string $entrypoint The name of the contract method/entrypoint to invoke
     * @param array<string, mixed> $publicArgs Public arguments visible on the blockchain
     * @param array<string, mixed> $privateArgs Private arguments for ZK proof generation,
     *                                           not revealed on-chain
     * @return TxHash The transaction hash for tracking the submission
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If the call is invalid
     * @throws \VersionTwo\Midnight\Exceptions\ProofFailedException If proof generation fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If submission fails
     * @throws \InvalidArgumentException If contract address or entrypoint is invalid
     */
    public function call(
        string $contractAddress,
        string $entrypoint,
        array $publicArgs = [],
        array $privateArgs = []
    ): TxHash;

    /**
     * Read contract state without modifying it.
     *
     * This method queries a contract's public state or calls a read-only method.
     * No transaction is submitted, no fees are charged, and results are typically
     * cached for improved performance.
     *
     * @param string $contractAddress The address of the contract to read from
     * @param string $selector The state variable or read-only method to query
     * @param array<string, mixed> $args Optional arguments for parameterized queries
     * @return ContractCallResult The result containing the requested data
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If the read operation fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the network is unreachable
     * @throws \InvalidArgumentException If contract address or selector is invalid
     */
    public function read(
        string $contractAddress,
        string $selector,
        array $args = []
    ): ContractCallResult;
}
