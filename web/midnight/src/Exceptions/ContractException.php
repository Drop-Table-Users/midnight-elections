<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Exceptions;

/**
 * Exception thrown when contract-related errors occur.
 *
 * This exception is used for:
 * - Contract deployment failures
 * - Contract call/execution errors
 * - Contract state access errors
 * - Invalid contract addresses
 * - Contract revert errors
 * - Missing or invalid contract methods
 *
 * @package VersionTwo\Midnight\Exceptions
 */
class ContractException extends MidnightException
{
    /**
     * Create exception for contract deployment failure.
     *
     * @param string $reason The failure reason
     * @param string|null $contractPath The path to the compiled contract (if available)
     * @param array<string, mixed> $context Additional context
     * @return static
     */
    public static function deploymentFailed(string $reason, ?string $contractPath = null, array $context = []): static
    {
        $message = "Contract deployment failed: {$reason}";
        if ($contractPath !== null) {
            $message .= " (contract: {$contractPath})";
        }

        return new static(
            message: $message,
            context: array_merge([
                'reason' => $reason,
                'contract_path' => $contractPath,
            ], $context)
        );
    }

    /**
     * Create exception for contract call failure.
     *
     * @param string $contractAddress The contract address
     * @param string $entrypoint The entrypoint/method name
     * @param string $reason The failure reason
     * @param array<string, mixed> $context Additional context
     * @return static
     */
    public static function callFailed(
        string $contractAddress,
        string $entrypoint,
        string $reason,
        array $context = []
    ): static {
        return new static(
            message: "Contract call to {$contractAddress}::{$entrypoint} failed: {$reason}",
            context: array_merge([
                'contract_address' => $contractAddress,
                'entrypoint' => $entrypoint,
                'reason' => $reason,
            ], $context)
        );
    }

    /**
     * Create exception for contract execution revert.
     *
     * @param string $contractAddress The contract address
     * @param string $entrypoint The entrypoint/method name
     * @param string $revertReason The revert reason
     * @return static
     */
    public static function executionReverted(
        string $contractAddress,
        string $entrypoint,
        string $revertReason
    ): static {
        return new static(
            message: "Contract execution reverted at {$contractAddress}::{$entrypoint}: {$revertReason}",
            context: [
                'contract_address' => $contractAddress,
                'entrypoint' => $entrypoint,
                'revert_reason' => $revertReason,
            ]
        );
    }

    /**
     * Create exception for invalid contract address.
     *
     * @param string $address The invalid address
     * @param string $reason The reason it's invalid
     * @return static
     */
    public static function invalidAddress(string $address, string $reason = 'Invalid format'): static
    {
        return new static(
            message: "Invalid contract address '{$address}': {$reason}",
            context: [
                'address' => $address,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create exception for contract not found.
     *
     * @param string $contractAddress The contract address that wasn't found
     * @return static
     */
    public static function notFound(string $contractAddress): static
    {
        return new static(
            message: "Contract not found at address: {$contractAddress}",
            context: ['contract_address' => $contractAddress]
        );
    }

    /**
     * Create exception for missing or invalid contract method.
     *
     * @param string $contractAddress The contract address
     * @param string $method The method/entrypoint name
     * @return static
     */
    public static function methodNotFound(string $contractAddress, string $method): static
    {
        return new static(
            message: "Method '{$method}' not found on contract at {$contractAddress}",
            context: [
                'contract_address' => $contractAddress,
                'method' => $method,
            ]
        );
    }

    /**
     * Create exception for invalid contract arguments.
     *
     * @param string $entrypoint The entrypoint/method name
     * @param string $details Details about the invalid arguments
     * @return static
     */
    public static function invalidArguments(string $entrypoint, string $details): static
    {
        return new static(
            message: "Invalid arguments for {$entrypoint}: {$details}",
            context: [
                'entrypoint' => $entrypoint,
                'details' => $details,
            ]
        );
    }

    /**
     * Create exception for contract join failure.
     *
     * @param string $contractAddress The contract address
     * @param string $reason The failure reason
     * @return static
     */
    public static function joinFailed(string $contractAddress, string $reason): static
    {
        return new static(
            message: "Failed to join contract at {$contractAddress}: {$reason}",
            context: [
                'contract_address' => $contractAddress,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create exception for contract state read failure.
     *
     * @param string $contractAddress The contract address
     * @param string $selector The state selector/key
     * @param string $reason The failure reason
     * @return static
     */
    public static function stateReadFailed(string $contractAddress, string $selector, string $reason): static
    {
        return new static(
            message: "Failed to read state '{$selector}' from contract at {$contractAddress}: {$reason}",
            context: [
                'contract_address' => $contractAddress,
                'selector' => $selector,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create exception for compilation errors.
     *
     * @param string $contractPath The path to the contract source
     * @param string $errors The compilation error messages
     * @return static
     */
    public static function compilationFailed(string $contractPath, string $errors): static
    {
        return new static(
            message: "Contract compilation failed for {$contractPath}",
            context: [
                'contract_path' => $contractPath,
                'errors' => $errors,
            ]
        );
    }
}
