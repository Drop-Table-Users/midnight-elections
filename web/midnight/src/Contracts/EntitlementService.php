<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Contracts;

use VersionTwo\Midnight\DTO\EntitlementToken;
use VersionTwo\Midnight\DTO\TxHash;

/**
 * Entitlement service interface for voting and identity-based flows.
 *
 * This interface provides methods for working with entitlement tokens, which are
 * used in privacy-preserving voting systems and other scenarios where users need
 * to prove eligibility without revealing their identity.
 *
 * Entitlement tokens enable zero-knowledge proofs of authorization, allowing users
 * to participate in activities (like voting) while maintaining privacy about their
 * specific identity and choices.
 */
interface EntitlementService
{
    /**
     * Request an entitlement token for a given identity.
     *
     * This method requests an entitlement token that proves the identity's
     * eligibility to perform certain actions (e.g., cast a vote). The actual
     * KYC and identity verification checks happen in the bridge/contract layer.
     *
     * The returned token can be used later to perform authorized actions without
     * revealing the specific identity, maintaining privacy while proving eligibility.
     *
     * @param string $identity The identity identifier (e.g., voter ID, DID, or other
     *                         unique identifier that has been verified off-chain)
     * @return EntitlementToken The entitlement token that can be used for authorized
     *                          operations
     * @throws \VersionTwo\Midnight\Exceptions\MidnightException If the identity is not eligible
     *                                                           or verification fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the entitlement service is unreachable
     * @throws \InvalidArgumentException If the identity format is invalid
     */
    public function requestEntitlement(string $identity): EntitlementToken;

    /**
     * Use an entitlement token to cast a vote or perform a private action.
     *
     * This method consumes an entitlement token to execute a private contract
     * operation, typically casting a vote. The entitlement token proves eligibility
     * without revealing the voter's specific identity or choice.
     *
     * The vote payload is processed privately using zero-knowledge proofs, ensuring
     * that the vote is valid and the voter is eligible, while maintaining both
     * voter privacy and vote secrecy.
     *
     * @param EntitlementToken $token The entitlement token proving eligibility
     * @param string $contractAddress The address of the voting contract or other
     *                                authorized contract
     * @param array<string, mixed> $votePayload The vote or action data. For voting,
     *                                          this typically includes the candidate
     *                                          choice or ballot data
     * @return TxHash The transaction hash for tracking the vote/action submission
     * @throws \VersionTwo\Midnight\Exceptions\ContractException If the vote is invalid or
     *                                                           token has already been used
     * @throws \VersionTwo\Midnight\Exceptions\ProofFailedException If proof generation fails
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If submission fails
     * @throws \InvalidArgumentException If the contract address or payload is invalid
     */
    public function useEntitlementForVote(
        EntitlementToken $token,
        string $contractAddress,
        array $votePayload
    ): TxHash;
}
