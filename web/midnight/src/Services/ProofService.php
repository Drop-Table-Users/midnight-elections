<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\Contracts\ProofClient;
use VersionTwo\Midnight\DTO\ProofRequest;
use VersionTwo\Midnight\DTO\ProofResponse;
use VersionTwo\Midnight\Exceptions\ProofFailedException;

/**
 * Implementation of the ProofClient interface.
 *
 * This service provides zero-knowledge proof generation functionality for
 * contract operations involving private inputs. It communicates with the
 * Midnight bridge's proof server to generate ZK proofs.
 *
 * Features:
 * - Graceful error handling for proof generation failures
 * - Input validation
 * - Comprehensive logging
 * - Detailed error messages for debugging
 */
class ProofService implements ProofClient
{
    /**
     * Create a new ProofService instance.
     *
     * @param MidnightClient $client The Midnight client for bridge communication
     */
    public function __construct(
        private readonly MidnightClient $client,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function generateForContract(
        string $contractName,
        string $entrypoint,
        array $publicInputs,
        array $privateInputs
    ): ProofResponse {
        Log::debug('ProofService: Generating proof for contract', [
            'contract' => $contractName,
            'entrypoint' => $entrypoint,
            'public_inputs_count' => count($publicInputs),
            'private_inputs_count' => count($privateInputs),
        ]);

        // Validate inputs
        $this->validateContractName($contractName);
        $this->validateEntrypoint($entrypoint);
        $this->validateInputs($publicInputs, $privateInputs);

        try {
            $request = new ProofRequest(
                contractName: $contractName,
                entrypoint: $entrypoint,
                publicInputs: $publicInputs,
                privateInputs: $privateInputs,
            );

            $proof = $this->client->generateProof($request);

            if ($proof->isEmpty()) {
                throw ProofFailedException::generationFailed(
                    $contractName,
                    $entrypoint,
                    'Proof generation returned empty proof'
                );
            }

            Log::info('ProofService: Proof generated successfully', [
                'contract' => $contractName,
                'entrypoint' => $entrypoint,
                'generation_time' => $proof->generationTime,
                'has_public_outputs' => $proof->hasPublicOutputs(),
            ]);

            return $proof;
        } catch (ProofFailedException $e) {
            Log::error('ProofService: Proof generation failed', [
                'error' => $e->getMessage(),
                'contract' => $contractName,
                'entrypoint' => $entrypoint,
                'context' => $e->getContext(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('ProofService: Unexpected error during proof generation', [
                'error' => $e->getMessage(),
                'contract' => $contractName,
                'entrypoint' => $entrypoint,
            ]);

            throw ProofFailedException::generationFailed(
                $contractName,
                $entrypoint,
                $e->getMessage(),
                ['original_error' => get_class($e)]
            );
        }
    }

    /**
     * Validate contract name.
     *
     * @param string $contractName The contract name to validate
     * @return void
     * @throws InvalidArgumentException If the contract name is invalid
     */
    private function validateContractName(string $contractName): void
    {
        if (empty($contractName)) {
            throw new InvalidArgumentException('Contract name cannot be empty');
        }

        // Additional validation could be added here for Midnight-specific naming conventions
        if (strlen($contractName) > 255) {
            throw new InvalidArgumentException('Contract name is too long (max 255 characters)');
        }
    }

    /**
     * Validate entrypoint name.
     *
     * @param string $entrypoint The entrypoint to validate
     * @return void
     * @throws InvalidArgumentException If the entrypoint is invalid
     */
    private function validateEntrypoint(string $entrypoint): void
    {
        if (empty($entrypoint)) {
            throw new InvalidArgumentException('Entrypoint cannot be empty');
        }

        if (strlen($entrypoint) > 255) {
            throw new InvalidArgumentException('Entrypoint name is too long (max 255 characters)');
        }
    }

    /**
     * Validate proof inputs.
     *
     * @param array<string, mixed> $publicInputs The public inputs
     * @param array<string, mixed> $privateInputs The private inputs
     * @return void
     * @throws InvalidArgumentException If the inputs are invalid
     */
    private function validateInputs(array $publicInputs, array $privateInputs): void
    {
        // Private inputs are required for proof generation
        if (empty($privateInputs)) {
            throw new InvalidArgumentException(
                'Private inputs cannot be empty for proof generation'
            );
        }

        // Validate that inputs are associative arrays (not sequential)
        // This helps catch common mistakes where developers pass indexed arrays
        if (!$this->isAssociativeArray($publicInputs) && !empty($publicInputs)) {
            throw new InvalidArgumentException(
                'Public inputs must be an associative array (key-value pairs)'
            );
        }

        if (!$this->isAssociativeArray($privateInputs)) {
            throw new InvalidArgumentException(
                'Private inputs must be an associative array (key-value pairs)'
            );
        }
    }

    /**
     * Check if an array is associative.
     *
     * @param array<mixed> $array The array to check
     * @return bool
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Validate proof request before submission.
     *
     * This method can be used to perform pre-flight validation of proof
     * inputs without actually generating the proof.
     *
     * @param string $contractName The contract name
     * @param string $entrypoint The entrypoint
     * @param array<string, mixed> $publicInputs The public inputs
     * @param array<string, mixed> $privateInputs The private inputs
     * @return array<string, string> Array of validation errors (empty if valid)
     */
    public function validateProofRequest(
        string $contractName,
        string $entrypoint,
        array $publicInputs,
        array $privateInputs
    ): array {
        $errors = [];

        try {
            $this->validateContractName($contractName);
        } catch (InvalidArgumentException $e) {
            $errors['contract_name'] = $e->getMessage();
        }

        try {
            $this->validateEntrypoint($entrypoint);
        } catch (InvalidArgumentException $e) {
            $errors['entrypoint'] = $e->getMessage();
        }

        try {
            $this->validateInputs($publicInputs, $privateInputs);
        } catch (InvalidArgumentException $e) {
            $errors['inputs'] = $e->getMessage();
        }

        return $errors;
    }
}
