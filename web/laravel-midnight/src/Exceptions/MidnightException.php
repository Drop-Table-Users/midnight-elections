<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Exceptions;

use Exception;

/**
 * Base exception class for all Midnight package exceptions.
 *
 * This serves as the root exception that all other Midnight-specific
 * exceptions extend from, allowing consumers to catch all package
 * exceptions with a single catch block if needed.
 *
 * @package VersionTwo\Midnight\Exceptions
 */
class MidnightException extends Exception
{
    /**
     * Additional context data for the exception.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Create a new Midnight exception instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous throwable used for exception chaining
     * @param array<string, mixed> $context Additional context data
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context data.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create a new exception instance with context.
     *
     * @param string $message The exception message
     * @param array<string, mixed> $context Additional context data
     * @return static
     */
    public static function withContext(string $message, array $context = []): static
    {
        return new static($message, 0, null, $context);
    }

    /**
     * Create a new exception instance from a previous exception.
     *
     * @param string $message The exception message
     * @param \Throwable $previous Previous throwable
     * @param array<string, mixed> $context Additional context data
     * @return static
     */
    public static function fromPrevious(string $message, \Throwable $previous, array $context = []): static
    {
        return new static($message, 0, $previous, $context);
    }
}
