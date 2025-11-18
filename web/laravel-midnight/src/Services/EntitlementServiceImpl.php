<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Contracts\EntitlementService;
use VersionTwo\Midnight\Contracts\ProofClient;
use VersionTwo\Midnight\DTO\ContractCall;
use VersionTwo\Midnight\DTO\EntitlementToken;
use VersionTwo\Midnight\DTO\TxHash;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\MidnightException;
use VersionTwo\Midnight\Exceptions\ProofFailedException;

/**
 * Implementation of the EntitlementService interface.
 *
 * This service handles entitlement token operations for privacy-preserving
 * voting and authorization flows. It orchestrates proof generation and
 * contract interactions to enable zero-knowledge proofs of eligibility.
 *
 * Features:
 * - Entitlement token validation
 * - ZK proof generation for private voting
 * - Identity verification integration
 * - Comprehensive logging
 * - Error handling for various failure scenarios
 */
class EntitlementServiceImpl implements EntitlementService
{
    /**
     * Default entitlement contract address.
     * This should be configured via the config file in production.
     */
    private const DEFAULT_ENTITLEMENT_CONTRACT = '__entitlement__';

    /**
     * Create a new EntitlementServiceImpl instance.
     *
     * @param ContractGateway $contractGateway The contract gateway for blockchain interactions
     * @param ProofClient $proofClient The proof client for ZK proof generation
     */
    public function __construct(
        private readonly ContractGateway $contractGateway,
        private readonly ProofClient $proofClient,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function requestEntitlement(string $identity): EntitlementToken
    {
        Log::debug('EntitlementService: Requesting entitlement token', [
            'identity' => $this->maskIdentity($identity),
        ]);

        $this->validateIdentity($identity);

        try {
            // Call the entitlement contract to request a token
            // In a real implementation, this would interact with an identity
            // verification service or smart contract
            $result = $this->contractGateway->read(
                contractAddress: $this->getEntitlementContractAddress(),
                selector: 'requestEntitlement',
                args: [
                    'identity' => $identity,
                ]
            );

            if (!$result->success) {
                throw MidnightException::withContext(
                    'Failed to request entitlement token: ' . ($result->error ?? 'Unknown error'),
                    [
                        'identity' => $this->maskIdentity($identity),
                        'result' => $result->toArray(),
                    ]
                );
            }

            // Parse the entitlement token from the result
            $tokenData = $result->value;
            if (!is_array($tokenData)) {
                throw MidnightException::withContext(
                    'Invalid entitlement token response format',
                    ['result' => $result->toArray()]
                );
            }

            $token = EntitlementToken::fromArray(array_merge($tokenData, [
                'identity' => $identity,
            ]));

            // Validate the token
            if (!$token->isValid()) {
                throw MidnightException::withContext(
                    'Received expired entitlement token',
                    [
                        'identity' => $this->maskIdentity($identity),
                        'expires_at' => $token->expiresAt?->format(\DateTimeInterface::ATOM),
                    ]
                );
            }

            Log::info('EntitlementService: Entitlement token issued successfully', [
                'identity' => $this->maskIdentity($identity),
                'expires_at' => $token->expiresAt?->format(\DateTimeInterface::ATOM),
            ]);

            return $token;
        } catch (ContractException $e) {
            Log::error('EntitlementService: Failed to request entitlement token', [
                'error' => $e->getMessage(),
                'identity' => $this->maskIdentity($identity),
            ]);
            throw MidnightException::fromPrevious(
                'Identity is not eligible for entitlement token',
                $e,
                ['identity' => $this->maskIdentity($identity)]
            );
        } catch (MidnightException $e) {
            Log::error('EntitlementService: Entitlement request failed', [
                'error' => $e->getMessage(),
                'identity' => $this->maskIdentity($identity),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('EntitlementService: Unexpected error requesting entitlement', [
                'error' => $e->getMessage(),
                'identity' => $this->maskIdentity($identity),
            ]);
            throw MidnightException::fromPrevious(
                'Failed to request entitlement token',
                $e,
                ['identity' => $this->maskIdentity($identity)]
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function useEntitlementForVote(
        EntitlementToken $token,
        string $contractAddress,
        array $votePayload
    ): TxHash {
        Log::debug('EntitlementService: Using entitlement for vote', [
            'identity' => $this->maskIdentity($token->identity),
            'contract' => $contractAddress,
        ]);

        // Validate the token
        $this->validateToken($token);
        $this->validateContractAddress($contractAddress);
        $this->validateVotePayload($votePayload);

        try {
            // Generate a ZK proof that demonstrates eligibility without revealing identity
            // The proof proves:
            // 1. The voter has a valid entitlement token
            // 2. The token has not been used before
            // 3. The vote is well-formed
            // While keeping the voter's identity and choice private
            $proof = $this->proofClient->generateForContract(
                contractName: $this->extractContractName($contractAddress),
                entrypoint: 'vote',
                publicInputs: [
                    'contract_address' => $contractAddress,
                    'timestamp' => time(),
                ],
                privateInputs: [
                    'entitlement_token' => $token->token,
                    'identity' => $token->identity,
                    'vote' => $votePayload,
                ]
            );

            // Submit the vote transaction with the proof
            $txHash = $this->contractGateway->call(
                contractAddress: $contractAddress,
                entrypoint: 'vote',
                publicArgs: [
                    'proof' => $proof->proof,
                    'public_outputs' => $proof->publicOutputs,
                ],
                privateArgs: [
                    'entitlement_token' => $token->token,
                    'vote_data' => $votePayload,
                ]
            );

            Log::info('EntitlementService: Vote submitted successfully', [
                'tx_hash' => $txHash->value,
                'identity' => $this->maskIdentity($token->identity),
                'contract' => $contractAddress,
                'proof_time' => $proof->generationTime,
            ]);

            return $txHash;
        } catch (ProofFailedException $e) {
            Log::error('EntitlementService: Proof generation failed for vote', [
                'error' => $e->getMessage(),
                'identity' => $this->maskIdentity($token->identity),
                'contract' => $contractAddress,
            ]);
            throw $e;
        } catch (ContractException $e) {
            Log::error('EntitlementService: Vote submission failed', [
                'error' => $e->getMessage(),
                'identity' => $this->maskIdentity($token->identity),
                'contract' => $contractAddress,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('EntitlementService: Unexpected error during vote', [
                'error' => $e->getMessage(),
                'identity' => $this->maskIdentity($token->identity),
                'contract' => $contractAddress,
            ]);
            throw MidnightException::fromPrevious(
                'Failed to submit vote with entitlement token',
                $e,
                [
                    'identity' => $this->maskIdentity($token->identity),
                    'contract' => $contractAddress,
                ]
            );
        }
    }

    /**
     * Validate an identity string.
     *
     * @param string $identity The identity to validate
     * @return void
     * @throws InvalidArgumentException If the identity is invalid
     */
    private function validateIdentity(string $identity): void
    {
        if (empty($identity)) {
            throw new InvalidArgumentException('Identity cannot be empty');
        }

        if (strlen($identity) > 255) {
            throw new InvalidArgumentException('Identity is too long (max 255 characters)');
        }
    }

    /**
     * Validate an entitlement token.
     *
     * @param EntitlementToken $token The token to validate
     * @return void
     * @throws InvalidArgumentException If the token is invalid
     */
    private function validateToken(EntitlementToken $token): void
    {
        if (!$token->isValid()) {
            throw new InvalidArgumentException(
                'Entitlement token has expired or is invalid'
            );
        }

        if (empty($token->token)) {
            throw new InvalidArgumentException('Entitlement token value is empty');
        }
    }

    /**
     * Validate a contract address.
     *
     * @param string $address The contract address to validate
     * @return void
     * @throws InvalidArgumentException If the address is invalid
     */
    private function validateContractAddress(string $address): void
    {
        if (empty($address)) {
            throw new InvalidArgumentException('Contract address cannot be empty');
        }
    }

    /**
     * Validate vote payload.
     *
     * @param array<string, mixed> $payload The vote payload to validate
     * @return void
     * @throws InvalidArgumentException If the payload is invalid
     */
    private function validateVotePayload(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Vote payload cannot be empty');
        }

        // Additional validation could be added here for specific vote formats
        // For example, ensuring required fields are present
    }

    /**
     * Get the entitlement contract address.
     *
     * @return string
     */
    private function getEntitlementContractAddress(): string
    {
        return config('midnight.entitlement.contract_address', self::DEFAULT_ENTITLEMENT_CONTRACT);
    }

    /**
     * Extract contract name from contract address.
     *
     * In a real implementation, this might query a registry or use
     * metadata to map addresses to contract names.
     *
     * @param string $contractAddress The contract address
     * @return string The contract name
     */
    private function extractContractName(string $contractAddress): string
    {
        // Simplified implementation - in production, this would look up the contract
        // name from a registry or configuration
        return 'VotingContract';
    }

    /**
     * Mask an identity for logging (shows first 3 and last 2 characters).
     *
     * @param string $identity The identity to mask
     * @return string The masked identity
     */
    private function maskIdentity(string $identity): string
    {
        if (strlen($identity) <= 5) {
            return str_repeat('*', strlen($identity));
        }

        return substr($identity, 0, 3) . '...' . substr($identity, -2);
    }

    /**
     * Check if an entitlement token has been used.
     *
     * This method queries the voting contract to check if a specific
     * entitlement token has already been consumed.
     *
     * @param EntitlementToken $token The token to check
     * @param string $contractAddress The voting contract address
     * @return bool True if the token has been used, false otherwise
     */
    public function isTokenUsed(EntitlementToken $token, string $contractAddress): bool
    {
        Log::debug('EntitlementService: Checking if token is used', [
            'identity' => $this->maskIdentity($token->identity),
            'contract' => $contractAddress,
        ]);

        try {
            $result = $this->contractGateway->read(
                contractAddress: $contractAddress,
                selector: 'isTokenUsed',
                args: [
                    'token_hash' => hash('sha256', $token->token),
                ]
            );

            return $result->asBool();
        } catch (\Throwable $e) {
            Log::warning('EntitlementService: Failed to check token usage status', [
                'error' => $e->getMessage(),
                'contract' => $contractAddress,
            ]);
            // Conservative approach: assume token might be used if we can't verify
            return true;
        }
    }
}
