<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Console\Commands;

use Illuminate\Console\Command;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\Exceptions\MidnightException;

/**
 * Health check command for Midnight bridge and network connectivity.
 *
 * This command verifies that the Midnight bridge service is reachable and healthy,
 * and displays important network metadata including chain ID, network name, and
 * bridge version information. It's useful for monitoring, debugging, and pre-flight
 * checks before deploying contracts or submitting transactions.
 *
 * Usage:
 *   php artisan midnight:health
 *
 * Exit codes:
 *   0 - Bridge and network are healthy
 *   1 - Bridge or network are unreachable or unhealthy
 *
 * @package VersionTwo\Midnight\Console\Commands
 */
class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midnight:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Midnight bridge and network connectivity';

    /**
     * Create a new command instance.
     *
     * @param MidnightClient $client The Midnight client service
     */
    public function __construct(
        private readonly MidnightClient $client
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * This method performs a health check on the Midnight bridge and network,
     * displaying connection status, network metadata, and relevant configuration
     * information. If the bridge is unreachable or returns an error, the command
     * will display an error message and exit with code 1.
     *
     * @return int The exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $this->info('Checking Midnight bridge and network connectivity...');
        $this->newLine();

        try {
            // Perform health check
            $isHealthy = $this->client->healthCheck();

            if (!$isHealthy) {
                $this->error('❌ Bridge health check failed!');
                $this->error('The Midnight bridge service is not responding correctly.');
                $this->newLine();
                $this->comment('Please verify:');
                $this->comment('  • Bridge service is running');
                $this->comment('  • Configuration in config/midnight.php is correct');
                $this->comment('  • Network connectivity to the bridge URI');
                return Command::FAILURE;
            }

            $this->info('✓ Bridge is healthy and reachable');
            $this->newLine();

            // Fetch and display network metadata
            $this->comment('Fetching network metadata...');
            $metadata = $this->client->getNetworkMetadata();

            $this->newLine();
            $this->info('Network Information:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Network Name', $metadata->name],
                    ['Chain ID', $metadata->chainId],
                    ['Explorer URI', $metadata->explorerUri ?? 'Not configured'],
                    ['Network Type', $metadata->isMainnet() ? 'Mainnet' : 'Devnet/Testnet'],
                ]
            );

            // Display protocol parameters if available
            if (!empty($metadata->protocolParams)) {
                $this->newLine();
                $this->comment('Protocol Parameters:');

                $protocolData = [];
                foreach ($metadata->protocolParams as $key => $value) {
                    $protocolData[] = [
                        $key,
                        is_array($value) ? json_encode($value) : (string) $value
                    ];
                }

                $this->table(['Parameter', 'Value'], $protocolData);
            }

            // Display bridge configuration
            $this->newLine();
            $this->comment('Bridge Configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Base URI', config('midnight.bridge.base_uri')],
                    ['Timeout', config('midnight.bridge.timeout') . 's'],
                    ['Log Channel', config('midnight.log_channel') ?? 'default'],
                    ['Cache Store', config('midnight.cache.store') ?? 'default'],
                ]
            );

            $this->newLine();
            $this->info('✓ All systems operational!');

            return Command::SUCCESS;

        } catch (MidnightException $e) {
            $this->newLine();
            $this->error('❌ Health check failed!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('❌ Unexpected error occurred!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
