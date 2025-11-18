<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Exceptions;

/**
 * Exception thrown when configuration errors occur.
 *
 * This exception is used for:
 * - Missing required configuration
 * - Invalid configuration values
 * - Configuration validation failures
 * - Environment variable issues
 * - Service provider configuration errors
 *
 * @package VersionTwo\Midnight\Exceptions
 */
class ConfigurationException extends MidnightException
{
    /**
     * Create exception for missing required configuration.
     *
     * @param string $key The missing configuration key
     * @param string|null $suggestion Optional suggestion for fixing the issue
     * @return static
     */
    public static function missingRequired(string $key, ?string $suggestion = null): static
    {
        $message = "Required configuration key '{$key}' is missing";
        if ($suggestion !== null) {
            $message .= ". {$suggestion}";
        }

        return new static(
            message: $message,
            context: [
                'config_key' => $key,
                'suggestion' => $suggestion,
            ]
        );
    }

    /**
     * Create exception for invalid configuration value.
     *
     * @param string $key The configuration key
     * @param mixed $value The invalid value
     * @param string $expectedType The expected type or format
     * @return static
     */
    public static function invalidValue(string $key, mixed $value, string $expectedType): static
    {
        return new static(
            message: "Invalid value for configuration key '{$key}': expected {$expectedType}, got " . gettype($value),
            context: [
                'config_key' => $key,
                'invalid_value' => $value,
                'expected_type' => $expectedType,
            ]
        );
    }

    /**
     * Create exception for invalid bridge configuration.
     *
     * @param string $reason The reason the bridge configuration is invalid
     * @return static
     */
    public static function invalidBridgeConfig(string $reason): static
    {
        return new static(
            message: "Invalid bridge configuration: {$reason}",
            context: ['reason' => $reason]
        );
    }

    /**
     * Create exception for missing environment variable.
     *
     * @param string $envVar The missing environment variable name
     * @param string|null $configKey The related config key (if applicable)
     * @return static
     */
    public static function missingEnvironmentVariable(string $envVar, ?string $configKey = null): static
    {
        $message = "Required environment variable '{$envVar}' is not set";
        if ($configKey !== null) {
            $message .= " (needed for config key '{$configKey}')";
        }

        return new static(
            message: $message,
            context: [
                'env_var' => $envVar,
                'config_key' => $configKey,
            ]
        );
    }

    /**
     * Create exception for invalid network configuration.
     *
     * @param string $network The invalid network name
     * @param array<string> $validNetworks List of valid network names
     * @return static
     */
    public static function invalidNetwork(string $network, array $validNetworks): static
    {
        return new static(
            message: "Invalid network '{$network}'. Valid networks: " . implode(', ', $validNetworks),
            context: [
                'network' => $network,
                'valid_networks' => $validNetworks,
            ]
        );
    }

    /**
     * Create exception for invalid timeout value.
     *
     * @param string $key The configuration key
     * @param float|int $value The invalid timeout value
     * @param float|int $min The minimum allowed value
     * @param float|int $max The maximum allowed value
     * @return static
     */
    public static function invalidTimeout(string $key, float|int $value, float|int $min, float|int $max): static
    {
        return new static(
            message: "Invalid timeout value for '{$key}': {$value}. Must be between {$min} and {$max} seconds",
            context: [
                'config_key' => $key,
                'value' => $value,
                'min' => $min,
                'max' => $max,
            ]
        );
    }

    /**
     * Create exception for invalid cache configuration.
     *
     * @param string $reason The reason the cache configuration is invalid
     * @return static
     */
    public static function invalidCacheConfig(string $reason): static
    {
        return new static(
            message: "Invalid cache configuration: {$reason}",
            context: ['reason' => $reason]
        );
    }

    /**
     * Create exception for invalid queue configuration.
     *
     * @param string $reason The reason the queue configuration is invalid
     * @return static
     */
    public static function invalidQueueConfig(string $reason): static
    {
        return new static(
            message: "Invalid queue configuration: {$reason}",
            context: ['reason' => $reason]
        );
    }

    /**
     * Create exception for package not properly configured.
     *
     * @param string $issue Description of the configuration issue
     * @param string|null $fixSuggestion Suggestion for how to fix it
     * @return static
     */
    public static function notConfigured(string $issue, ?string $fixSuggestion = null): static
    {
        $message = "Midnight package not properly configured: {$issue}";
        if ($fixSuggestion !== null) {
            $message .= ". {$fixSuggestion}";
        }

        return new static(
            message: $message,
            context: [
                'issue' => $issue,
                'fix_suggestion' => $fixSuggestion,
            ]
        );
    }

    /**
     * Create exception for invalid signing configuration.
     *
     * @param string $reason The reason the signing configuration is invalid
     * @return static
     */
    public static function invalidSigningConfig(string $reason): static
    {
        return new static(
            message: "Invalid request signing configuration: {$reason}",
            context: ['reason' => $reason]
        );
    }

    /**
     * Create exception for configuration validation failure.
     *
     * @param array<string, string> $errors Array of validation errors (key => error message)
     * @return static
     */
    public static function validationFailed(array $errors): static
    {
        $message = 'Configuration validation failed: ' . implode('; ', array_map(
            fn($key, $error) => "{$key}: {$error}",
            array_keys($errors),
            $errors
        ));

        return new static(
            message: $message,
            context: ['validation_errors' => $errors]
        );
    }
}
