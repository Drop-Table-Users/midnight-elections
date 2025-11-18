<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use VersionTwo\Midnight\Contracts\BridgeHttpClient as BridgeHttpClientContract;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Contracts\EntitlementService;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\Contracts\ProofClient;
use VersionTwo\Midnight\Contracts\WalletGateway;
use VersionTwo\Midnight\Http\BridgeHttpClient;
use VersionTwo\Midnight\Services\ContractService;
use VersionTwo\Midnight\Services\EntitlementServiceImpl;
use VersionTwo\Midnight\Services\MidnightService;
use VersionTwo\Midnight\Services\ProofService;
use VersionTwo\Midnight\Services\WalletService;
use VersionTwo\Midnight\View\Directives\MidnightDirectives;

/**
 * Service provider for the Midnight Laravel package.
 *
 * This service provider registers all core services, binds interfaces to their
 * implementations, publishes configuration files, and registers console commands.
 *
 * The provider follows Laravel 12 conventions and implements the following:
 * - Singleton bindings for all major services
 * - Interface-to-implementation bindings for dependency injection
 * - Configuration merging and publishing
 * - Console command registration
 * - Custom Blade directives and components registration
 * - View publishing for customization
 * - Proper service discovery through the provides() method
 *
 * @package VersionTwo\Midnight\Providers
 */
class MidnightServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array<string, string>
     */
    public array $singletons = [
        BridgeHttpClientContract::class => BridgeHttpClient::class,
        BridgeHttpClient::class => BridgeHttpClient::class,
        MidnightClient::class => MidnightService::class,
        ContractGateway::class => ContractService::class,
        ProofClient::class => ProofService::class,
        WalletGateway::class => WalletService::class,
        EntitlementService::class => EntitlementServiceImpl::class,
    ];

    /**
     * Register any application services.
     *
     * This method is called during the application bootstrap process and is responsible
     * for registering all core services into the Laravel container. All services are
     * registered as singletons to ensure a single instance throughout the application
     * lifecycle.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration with user configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/midnight.php',
            'midnight'
        );

        // Register the BridgeHttpClient as a singleton
        // This is the concrete implementation used by all other services
        $this->app->singleton(BridgeHttpClient::class, function ($app) {
            return new BridgeHttpClient(
                baseUri: config('midnight.bridge.base_uri'),
                apiKey: config('midnight.bridge.api_key'),
                timeout: config('midnight.bridge.timeout'),
                logger: config('midnight.log_channel')
                    ? $app->make('log')->channel(config('midnight.log_channel'))
                    : null
            );
        });

        // Bind the BridgeHttpClient contract to the concrete implementation
        $this->app->singleton(
            BridgeHttpClientContract::class,
            BridgeHttpClient::class
        );

        // Register the MidnightClient service
        $this->app->singleton(MidnightClient::class, function ($app) {
            return new MidnightService(
                httpClient: $app->make(BridgeHttpClient::class),
                cache: config('midnight.cache.store')
                    ? $app->make('cache')->store(config('midnight.cache.store'))
                    : null
            );
        });

        // Register the ContractGateway service
        $this->app->singleton(ContractGateway::class, function ($app) {
            return new ContractService(
                client: $app->make(MidnightClient::class),
                cache: config('midnight.cache.store')
                    ? $app->make('cache')->store(config('midnight.cache.store'))
                    : null
            );
        });

        // Register the ProofClient service
        $this->app->singleton(ProofClient::class, function ($app) {
            return new ProofService(
                client: $app->make(MidnightClient::class)
            );
        });

        // Register the WalletGateway service
        $this->app->singleton(WalletGateway::class, function ($app) {
            return new WalletService(
                client: $app->make(MidnightClient::class),
                cache: config('midnight.cache.store')
                    ? $app->make('cache')->store(config('midnight.cache.store'))
                    : null
            );
        });

        // Register the EntitlementService implementation
        $this->app->singleton(EntitlementService::class, function ($app) {
            return new EntitlementServiceImpl(
                contractGateway: $app->make(ContractGateway::class),
                proofClient: $app->make(ProofClient::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all services have been registered. It handles
     * configuration publishing, command registration, and any event listener setup.
     *
     * @return void
     */
    public function boot(): void
    {
        // Only run this if the application is running in the console
        if ($this->app->runningInConsole()) {
            // Publish configuration file
            $this->publishes([
                __DIR__ . '/../Config/midnight.php' => config_path('midnight.php'),
            ], 'midnight-config');

            // Publish JavaScript assets
            $this->publishes([
                __DIR__ . '/../../dist/midnight.es.js' => public_path('vendor/midnight/midnight.es.js'),
                __DIR__ . '/../../dist/midnight.umd.js' => public_path('vendor/midnight/midnight.umd.js'),
            ], 'midnight-assets');

            // Publish CSS assets
            $this->publishes([
                __DIR__ . '/../../dist/midnight.css' => public_path('vendor/midnight/midnight.css'),
            ], 'midnight-assets');

            // Publish views
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/midnight'),
            ], 'midnight-views');

            // Register console commands
            $this->commands([
                \VersionTwo\Midnight\Console\Commands\HealthCheckCommand::class,
                \VersionTwo\Midnight\Console\Commands\NetworkInfoCommand::class,
                \VersionTwo\Midnight\Console\Commands\ContractDeployCommand::class,
                \VersionTwo\Midnight\Console\Commands\ContractJoinCommand::class,
                \VersionTwo\Midnight\Console\Commands\CacheClearCommand::class,
            ]);
        }

        // Load package views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'midnight');

        // Register Blade components
        $this->registerBladeComponents();

        // Register custom Blade directives
        MidnightDirectives::register();

        // Register event listeners
        // This can be expanded as needed for package-specific events
        // Example:
        // Event::listen(
        //     MidnightTransactionSubmitted::class,
        //     MidnightTransactionListener::class
        // );
    }

    /**
     * Register Blade components.
     *
     * This method registers all custom Blade components for the Midnight package,
     * making them available using the midnight:: namespace.
     *
     * @return void
     */
    protected function registerBladeComponents(): void
    {
        // Register anonymous components from the midnight namespace
        Blade::componentNamespace('VersionTwo\\Midnight\\View\\Components', 'midnight');

        // Register individual components
        Blade::component('midnight::components.midnight.wallet-connect', 'midnight::wallet-connect');
        Blade::component('midnight::components.midnight.vote-form', 'midnight::vote-form');
        Blade::component('midnight::components.midnight.transaction-status', 'midnight::transaction-status');
        Blade::component('midnight::components.midnight.network-indicator', 'midnight::network-indicator');
        Blade::component('midnight::components.midnight.loading-spinner', 'midnight::loading-spinner');
        Blade::component('midnight::components.midnight.error-alert', 'midnight::error-alert');
    }

    /**
     * Get the services provided by the provider.
     *
     * This method returns an array of all services provided by this service provider.
     * Laravel uses this information for deferred loading and service discovery.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            BridgeHttpClientContract::class,
            BridgeHttpClient::class,
            MidnightClient::class,
            ContractGateway::class,
            ProofClient::class,
            WalletGateway::class,
            EntitlementService::class,
        ];
    }
}
