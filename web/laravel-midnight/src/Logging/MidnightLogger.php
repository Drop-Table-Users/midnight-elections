<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Logging;

use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Midnight Logger
 *
 * Provides centralized logging for Midnight operations with automatic
 * context enrichment, sensitive data redaction, and configurable log channels.
 *
 * @package VersionTwo\Midnight\Logging
 */
class MidnightLogger
{
    /**
     * @var LoggerInterface The underlying logger instance
     */
    private LoggerInterface $logger;

    /**
     * @var array<string> Sensitive keys to redact in log context
     */
    private const SENSITIVE_KEYS = [
        'privateInputs',
        'privateArgs',
        'private_inputs',
        'private_args',
        'api_key',
        'signing_key',
        'secret',
        'password',
        'token',
        'key',
    ];

    /**
     * Create a new Midnight logger instance.
     *
     * @param string|null $channel Optional log channel name from config
     */
    public function __construct(?string $channel = null)
    {
        $channel = $channel ?? config('midnight.log_channel');

        $this->logger = $channel
            ? Log::channel($channel)
            : Log::getFacadeRoot();
    }

    /**
     * Log a debug message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug(
            $this->formatMessage($message),
            $this->enrichContext($context)
        );
    }

    /**
     * Log an info message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info(
            $this->formatMessage($message),
            $this->enrichContext($context)
        );
    }

    /**
     * Log a warning message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning(
            $this->formatMessage($message),
            $this->enrichContext($context)
        );
    }

    /**
     * Log an error message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error(
            $this->formatMessage($message),
            $this->enrichContext($context)
        );
    }

    /**
     * Log a critical message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical(
            $this->formatMessage($message),
            $this->enrichContext($context)
        );
    }

    /**
     * Log an exception with full context.
     *
     * @param \Throwable $exception The exception to log
     * @param string|null $message Optional custom message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public function exception(\Throwable $exception, ?string $message = null, array $context = []): void
    {
        $message = $message ?? 'Midnight operation failed: ' . $exception->getMessage();

        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        $this->logger->error(
            $this->formatMessage($message),
            $this->enrichContext($context)
        );
    }

    /**
     * Format the log message with Midnight prefix.
     *
     * @param string $message The raw message
     * @return string The formatted message
     */
    private function formatMessage(string $message): string
    {
        return '[Midnight] ' . $message;
    }

    /**
     * Enrich context with metadata and redact sensitive data.
     *
     * @param array<string, mixed> $context The context array
     * @return array<string, mixed> The enriched and sanitized context
     */
    private function enrichContext(array $context): array
    {
        // Add default metadata
        $enriched = array_merge([
            'timestamp' => now()->toIso8601String(),
            'network' => config('midnight.network.name'),
            'environment' => app()->environment(),
        ], $context);

        // Redact sensitive data
        return $this->redactSensitiveData($enriched);
    }

    /**
     * Recursively redact sensitive data from context.
     *
     * @param array<string, mixed> $data The data to sanitize
     * @return array<string, mixed> The sanitized data
     */
    private function redactSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            // Check if key is sensitive
            if ($this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $data[$key] = $this->redactSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * Check if a key is sensitive and should be redacted.
     *
     * @param string $key The key to check
     * @return bool True if sensitive, false otherwise
     */
    private function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($lowerKey, strtolower($sensitiveKey))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the underlying logger instance.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Create a logger instance with a specific channel.
     *
     * @param string $channel The log channel name
     * @return self
     */
    public static function channel(string $channel): self
    {
        return new self($channel);
    }
}
