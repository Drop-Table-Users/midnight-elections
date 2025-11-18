<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Contracts;

use VersionTwo\Midnight\DTO\ProofResponse;

/**
 * Zero-knowledge proof generation client interface.
 *
 * This interface provides methods for generating ZK proofs for contract operations
 * that involve private inputs. Proofs are generated via the bridge service which
 * communicates with a Midnight proof server.
 *
 * Proofs enable privacy-preserving operations where certain inputs remain hidden
 * from the blockchain while still proving their correctness.
 */
interface ProofClient
{
    /**
     * Generate a zero-knowledge proof for a contract operation.
     *
     * This method generates a ZK proof for a specific contract entrypoint.
     * The proof demonstrates that the private inputs satisfy the contract's
     * constraints without revealing the private inputs themselves.
     *
     * The generated proof can then be included in a contract call transaction,
     * allowing the contract to verify the correctness of the private computation
     * while maintaining privacy.
     *
     * @param string $contractName The name of the contract (must match the compiled
     *                             contract identifier)
     * @param string $entrypoint The specific entrypoint/method that requires the proof
     * @param array<string, mixed> $publicInputs Public inputs visible on-chain, used in
     *                                           both proof generation and verification
     * @param array<string, mixed> $privateInputs Private inputs that remain confidential,
     *                                            used only for proof generation
     * @return ProofResponse The generated proof along with any public outputs and
     *                       verification data
     * @throws \VersionTwo\Midnight\Exceptions\ProofFailedException If proof generation fails
     *                                                               (e.g., invalid inputs,
     *                                                               constraint violations)
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the proof server is unreachable
     * @throws \InvalidArgumentException If contract name, entrypoint, or inputs are invalid
     */
    public function generateForContract(
        string $contractName,
        string $entrypoint,
        array $publicInputs,
        array $privateInputs
    ): ProofResponse;
}
