<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Exceptions;

/**
 * Exception thrown when ZK proof generation or verification fails.
 *
 * This exception is used for:
 * - Proof generation failures
 * - Proof verification failures
 * - Invalid proof inputs
 * - Proof server errors
 * - Circuit constraint violations
 *
 * @package VersionTwo\Midnight\Exceptions
 */
class ProofFailedException extends MidnightException
{
    /**
     * Create exception for proof generation failure.
     *
     * @param string $contractName The contract name
     * @param string $entrypoint The contract entrypoint
     * @param string $reason The failure reason
     * @param array<string, mixed> $context Additional context
     * @return static
     */
    public static function generationFailed(
        string $contractName,
        string $entrypoint,
        string $reason,
        array $context = []
    ): static {
        return new static(
            message: "Proof generation failed for {$contractName}::{$entrypoint}: {$reason}",
            context: array_merge([
                'contract_name' => $contractName,
                'entrypoint' => $entrypoint,
                'reason' => $reason,
            ], $context)
        );
    }

    /**
     * Create exception for proof verification failure.
     *
     * @param string $reason The failure reason
     * @param array<string, mixed> $context Additional context
     * @return static
     */
    public static function verificationFailed(string $reason, array $context = []): static
    {
        return new static(
            message: "Proof verification failed: {$reason}",
            context: array_merge(['reason' => $reason], $context)
        );
    }

    /**
     * Create exception for invalid proof inputs.
     *
     * @param string $inputType The type of invalid input (e.g., 'public', 'private')
     * @param string $details Details about the validation failure
     * @return static
     */
    public static function invalidInputs(string $inputType, string $details): static
    {
        return new static(
            message: "Invalid {$inputType} inputs for proof generation: {$details}",
            context: [
                'input_type' => $inputType,
                'details' => $details,
            ]
        );
    }

    /**
     * Create exception for proof server unavailable.
     *
     * @param string $serverUri The proof server URI
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function serverUnavailable(string $serverUri, ?\Throwable $previous = null): static
    {
        return new static(
            message: "Proof server at {$serverUri} is unavailable",
            previous: $previous,
            context: ['server_uri' => $serverUri]
        );
    }

    /**
     * Create exception for circuit constraint violation.
     *
     * @param string $constraint The constraint that was violated
     * @param string $details Additional details about the violation
     * @return static
     */
    public static function constraintViolation(string $constraint, string $details): static
    {
        return new static(
            message: "Circuit constraint violation: {$constraint}. {$details}",
            context: [
                'constraint' => $constraint,
                'details' => $details,
            ]
        );
    }

    /**
     * Create exception for proof timeout.
     *
     * @param float $timeout The timeout value in seconds
     * @param string $contractName The contract name
     * @param string $entrypoint The contract entrypoint
     * @return static
     */
    public static function timeout(float $timeout, string $contractName, string $entrypoint): static
    {
        return new static(
            message: "Proof generation timed out after {$timeout} seconds for {$contractName}::{$entrypoint}",
            context: [
                'timeout' => $timeout,
                'contract_name' => $contractName,
                'entrypoint' => $entrypoint,
            ]
        );
    }

    /**
     * Create exception for missing witness data.
     *
     * @param string $missingField The missing witness field
     * @return static
     */
    public static function missingWitness(string $missingField): static
    {
        return new static(
            message: "Missing required witness data: {$missingField}",
            context: ['missing_field' => $missingField]
        );
    }
}
