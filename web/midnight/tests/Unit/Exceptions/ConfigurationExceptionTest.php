<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use VersionTwo\Midnight\Exceptions\ConfigurationException;
use VersionTwo\Midnight\Exceptions\MidnightException;

#[CoversClass(ConfigurationException::class)]
final class ConfigurationExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_midnight_exception(): void
    {
        $exception = new ConfigurationException('Test');

        $this->assertInstanceOf(MidnightException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_creates_missing_required_exception(): void
    {
        $key = 'midnight.bridge_url';

        $exception = ConfigurationException::missingRequired($key);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Required configuration key '{$key}' is missing", $exception->getMessage());
        $this->assertSame([
            'config_key' => $key,
            'suggestion' => null,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_missing_required_with_suggestion(): void
    {
        $key = 'midnight.api_key';
        $suggestion = 'Set MIDNIGHT_API_KEY in your .env file';

        $exception = ConfigurationException::missingRequired($key, $suggestion);

        $this->assertSame(
            "Required configuration key '{$key}' is missing. {$suggestion}",
            $exception->getMessage()
        );
        $this->assertSame([
            'config_key' => $key,
            'suggestion' => $suggestion,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('missingRequiredProvider')]
    public function it_handles_various_missing_required_configurations(
        string $key,
        ?string $suggestion
    ): void {
        $exception = ConfigurationException::missingRequired($key, $suggestion);

        $this->assertStringContainsString($key, $exception->getMessage());
        $this->assertSame($key, $exception->getContext()['config_key']);
        $this->assertSame($suggestion, $exception->getContext()['suggestion']);
    }

    #[Test]
    public function it_creates_invalid_value_exception(): void
    {
        $key = 'midnight.timeout';
        $value = 'not-a-number';
        $expectedType = 'integer or float';

        $exception = ConfigurationException::invalidValue($key, $value, $expectedType);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame(
            "Invalid value for configuration key '{$key}': expected {$expectedType}, got string",
            $exception->getMessage()
        );
        $this->assertSame([
            'config_key' => $key,
            'invalid_value' => $value,
            'expected_type' => $expectedType,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('invalidValueProvider')]
    public function it_handles_various_invalid_values(
        string $key,
        mixed $value,
        string $expectedType
    ): void {
        $exception = ConfigurationException::invalidValue($key, $value, $expectedType);

        $this->assertStringContainsString($key, $exception->getMessage());
        $this->assertStringContainsString($expectedType, $exception->getMessage());
        $this->assertSame($value, $exception->getContext()['invalid_value']);
    }

    #[Test]
    public function it_creates_invalid_bridge_config_exception(): void
    {
        $reason = 'Base URI must start with http:// or https://';

        $exception = ConfigurationException::invalidBridgeConfig($reason);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Invalid bridge configuration: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    #[DataProvider('bridgeConfigReasonProvider')]
    public function it_handles_various_bridge_config_reasons(string $reason): void
    {
        $exception = ConfigurationException::invalidBridgeConfig($reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function it_creates_missing_environment_variable_exception(): void
    {
        $envVar = 'MIDNIGHT_BRIDGE_URL';

        $exception = ConfigurationException::missingEnvironmentVariable($envVar);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Required environment variable '{$envVar}' is not set", $exception->getMessage());
        $this->assertSame([
            'env_var' => $envVar,
            'config_key' => null,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_missing_environment_variable_with_config_key(): void
    {
        $envVar = 'MIDNIGHT_API_KEY';
        $configKey = 'midnight.api_key';

        $exception = ConfigurationException::missingEnvironmentVariable($envVar, $configKey);

        $this->assertSame(
            "Required environment variable '{$envVar}' is not set (needed for config key '{$configKey}')",
            $exception->getMessage()
        );
        $this->assertSame([
            'env_var' => $envVar,
            'config_key' => $configKey,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('environmentVariableProvider')]
    public function it_handles_various_environment_variables(string $envVar, ?string $configKey): void
    {
        $exception = ConfigurationException::missingEnvironmentVariable($envVar, $configKey);

        $this->assertStringContainsString($envVar, $exception->getMessage());
        $this->assertSame($envVar, $exception->getContext()['env_var']);
        $this->assertSame($configKey, $exception->getContext()['config_key']);
    }

    #[Test]
    public function it_creates_invalid_network_exception(): void
    {
        $network = 'invalid-net';
        $validNetworks = ['devnet', 'testnet', 'mainnet'];

        $exception = ConfigurationException::invalidNetwork($network, $validNetworks);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame(
            "Invalid network '{$network}'. Valid networks: devnet, testnet, mainnet",
            $exception->getMessage()
        );
        $this->assertSame([
            'network' => $network,
            'valid_networks' => $validNetworks,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('networkProvider')]
    public function it_handles_various_network_configurations(string $network, array $validNetworks): void
    {
        $exception = ConfigurationException::invalidNetwork($network, $validNetworks);

        $this->assertStringContainsString($network, $exception->getMessage());
        $this->assertSame($network, $exception->getContext()['network']);
        $this->assertSame($validNetworks, $exception->getContext()['valid_networks']);
    }

    #[Test]
    public function it_creates_invalid_timeout_exception(): void
    {
        $key = 'midnight.request_timeout';
        $value = 500;
        $min = 1;
        $max = 300;

        $exception = ConfigurationException::invalidTimeout($key, $value, $min, $max);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame(
            "Invalid timeout value for '{$key}': {$value}. Must be between {$min} and {$max} seconds",
            $exception->getMessage()
        );
        $this->assertSame([
            'config_key' => $key,
            'value' => $value,
            'min' => $min,
            'max' => $max,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('timeoutProvider')]
    public function it_handles_various_timeout_values(
        string $key,
        float|int $value,
        float|int $min,
        float|int $max
    ): void {
        $exception = ConfigurationException::invalidTimeout($key, $value, $min, $max);

        $this->assertStringContainsString($key, $exception->getMessage());
        $this->assertSame($value, $exception->getContext()['value']);
        $this->assertSame($min, $exception->getContext()['min']);
        $this->assertSame($max, $exception->getContext()['max']);
    }

    #[Test]
    public function it_creates_invalid_cache_config_exception(): void
    {
        $reason = 'Cache driver not supported';

        $exception = ConfigurationException::invalidCacheConfig($reason);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Invalid cache configuration: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    #[DataProvider('cacheConfigReasonProvider')]
    public function it_handles_various_cache_config_reasons(string $reason): void
    {
        $exception = ConfigurationException::invalidCacheConfig($reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function it_creates_invalid_queue_config_exception(): void
    {
        $reason = 'Queue connection not configured';

        $exception = ConfigurationException::invalidQueueConfig($reason);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Invalid queue configuration: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    #[DataProvider('queueConfigReasonProvider')]
    public function it_handles_various_queue_config_reasons(string $reason): void
    {
        $exception = ConfigurationException::invalidQueueConfig($reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function it_creates_not_configured_exception(): void
    {
        $issue = 'Bridge URL is not set';

        $exception = ConfigurationException::notConfigured($issue);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Midnight package not properly configured: {$issue}", $exception->getMessage());
        $this->assertSame([
            'issue' => $issue,
            'fix_suggestion' => null,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_not_configured_with_fix_suggestion(): void
    {
        $issue = 'API credentials missing';
        $fixSuggestion = 'Run php artisan midnight:configure';

        $exception = ConfigurationException::notConfigured($issue, $fixSuggestion);

        $this->assertSame(
            "Midnight package not properly configured: {$issue}. {$fixSuggestion}",
            $exception->getMessage()
        );
        $this->assertSame([
            'issue' => $issue,
            'fix_suggestion' => $fixSuggestion,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('notConfiguredProvider')]
    public function it_handles_various_configuration_issues(string $issue, ?string $fixSuggestion): void
    {
        $exception = ConfigurationException::notConfigured($issue, $fixSuggestion);

        $this->assertStringContainsString($issue, $exception->getMessage());
        $this->assertSame($issue, $exception->getContext()['issue']);
        $this->assertSame($fixSuggestion, $exception->getContext()['fix_suggestion']);
    }

    #[Test]
    public function it_creates_invalid_signing_config_exception(): void
    {
        $reason = 'Private key format invalid';

        $exception = ConfigurationException::invalidSigningConfig($reason);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame("Invalid request signing configuration: {$reason}", $exception->getMessage());
        $this->assertSame(['reason' => $reason], $exception->getContext());
    }

    #[Test]
    #[DataProvider('signingConfigReasonProvider')]
    public function it_handles_various_signing_config_reasons(string $reason): void
    {
        $exception = ConfigurationException::invalidSigningConfig($reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function it_creates_validation_failed_exception(): void
    {
        $errors = [
            'midnight.timeout' => 'Must be a positive number',
            'midnight.bridge_url' => 'Must be a valid URL',
        ];

        $exception = ConfigurationException::validationFailed($errors);

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertSame(
            'Configuration validation failed: midnight.timeout: Must be a positive number; midnight.bridge_url: Must be a valid URL',
            $exception->getMessage()
        );
        $this->assertSame(['validation_errors' => $errors], $exception->getContext());
    }

    #[Test]
    public function it_creates_validation_failed_with_single_error(): void
    {
        $errors = ['midnight.api_key' => 'Cannot be empty'];

        $exception = ConfigurationException::validationFailed($errors);

        $this->assertStringContainsString('midnight.api_key', $exception->getMessage());
        $this->assertStringContainsString('Cannot be empty', $exception->getMessage());
    }

    #[Test]
    #[DataProvider('validationErrorsProvider')]
    public function it_handles_various_validation_errors(array $errors): void
    {
        $exception = ConfigurationException::validationFailed($errors);

        $context = $exception->getContext();
        $this->assertArrayHasKey('validation_errors', $context);
        $this->assertSame($errors, $context['validation_errors']);

        foreach ($errors as $key => $error) {
            $this->assertStringContainsString($key, $exception->getMessage());
            $this->assertStringContainsString($error, $exception->getMessage());
        }
    }

    #[Test]
    public function all_factory_methods_return_configuration_exception_instance(): void
    {
        $methods = [
            ConfigurationException::missingRequired('key'),
            ConfigurationException::invalidValue('key', 123, 'string'),
            ConfigurationException::invalidBridgeConfig('reason'),
            ConfigurationException::missingEnvironmentVariable('VAR'),
            ConfigurationException::invalidNetwork('net', ['devnet']),
            ConfigurationException::invalidTimeout('key', 500, 1, 300),
            ConfigurationException::invalidCacheConfig('reason'),
            ConfigurationException::invalidQueueConfig('reason'),
            ConfigurationException::notConfigured('issue'),
            ConfigurationException::invalidSigningConfig('reason'),
            ConfigurationException::validationFailed(['key' => 'error']),
        ];

        foreach ($methods as $exception) {
            $this->assertInstanceOf(ConfigurationException::class, $exception);
            $this->assertInstanceOf(MidnightException::class, $exception);
        }
    }

    public static function missingRequiredProvider(): array
    {
        return [
            'without suggestion' => ['midnight.bridge_url', null],
            'with suggestion' => [
                'midnight.api_key',
                'Set MIDNIGHT_API_KEY in your .env file',
            ],
            'nested key' => [
                'midnight.networks.devnet.rpc_url',
                'Configure the devnet RPC endpoint',
            ],
        ];
    }

    public static function invalidValueProvider(): array
    {
        return [
            'string instead of int' => ['timeout', 'five', 'integer'],
            'int instead of string' => ['api_key', 123, 'string'],
            'array instead of string' => ['bridge_url', ['url'], 'string'],
            'null instead of array' => ['networks', null, 'array'],
            'bool instead of int' => ['retry_count', true, 'integer'],
        ];
    }

    public static function bridgeConfigReasonProvider(): array
    {
        return [
            'invalid url' => ['Base URI must start with http:// or https://'],
            'missing port' => ['Port must be specified for custom bridge'],
            'invalid timeout' => ['Timeout must be a positive number'],
        ];
    }

    public static function environmentVariableProvider(): array
    {
        return [
            'without config key' => ['MIDNIGHT_BRIDGE_URL', null],
            'with config key' => ['MIDNIGHT_API_KEY', 'midnight.api_key'],
            'custom variable' => ['CUSTOM_MIDNIGHT_VAR', 'midnight.custom.setting'],
        ];
    }

    public static function networkProvider(): array
    {
        return [
            'standard networks' => ['invalid', ['devnet', 'testnet', 'mainnet']],
            'custom networks' => ['wrong', ['custom-net-1', 'custom-net-2']],
            'single network' => ['bad', ['production']],
        ];
    }

    public static function timeoutProvider(): array
    {
        return [
            'int timeout' => ['request_timeout', 500, 1, 300],
            'float timeout' => ['connection_timeout', 45.5, 0.1, 60.0],
            'negative value' => ['retry_timeout', -10, 0, 100],
            'zero value' => ['poll_timeout', 0, 1, 30],
        ];
    }

    public static function cacheConfigReasonProvider(): array
    {
        return [
            'unsupported driver' => ['Cache driver "custom" is not supported'],
            'missing store' => ['Cache store not configured'],
            'invalid ttl' => ['Cache TTL must be a positive integer'],
        ];
    }

    public static function queueConfigReasonProvider(): array
    {
        return [
            'no connection' => ['Queue connection not configured'],
            'invalid driver' => ['Queue driver "midnight" is not available'],
            'missing queue name' => ['Queue name must be specified'],
        ];
    }

    public static function notConfiguredProvider(): array
    {
        return [
            'without fix' => ['Bridge URL is not set', null],
            'with fix' => [
                'API credentials missing',
                'Run php artisan midnight:configure',
            ],
            'service provider' => [
                'Service provider not registered',
                'Add MidnightServiceProvider to config/app.php',
            ],
        ];
    }

    public static function signingConfigReasonProvider(): array
    {
        return [
            'invalid key format' => ['Private key format invalid'],
            'missing key' => ['Signing key not provided'],
            'wrong algorithm' => ['Signing algorithm not supported'],
        ];
    }

    public static function validationErrorsProvider(): array
    {
        return [
            'single error' => [
                ['midnight.timeout' => 'Must be a positive number'],
            ],
            'multiple errors' => [
                [
                    'midnight.timeout' => 'Must be a positive number',
                    'midnight.bridge_url' => 'Must be a valid URL',
                    'midnight.api_key' => 'Cannot be empty',
                ],
            ],
            'nested keys' => [
                [
                    'midnight.networks.devnet.url' => 'Invalid URL format',
                    'midnight.networks.testnet.timeout' => 'Out of range',
                ],
            ],
        ];
    }
}
