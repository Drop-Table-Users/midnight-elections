<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Console\Commands;

use Illuminate\Console\Command;
use VersionTwo\Midnight\Contracts\MidnightClient;
use VersionTwo\Midnight\Exceptions\MidnightException;

/**
 * Display Midnight network information command.
 *
 * This command retrieves and displays comprehensive information about the Midnight
 * network including network name, chain ID, explorer URI, and protocol parameters.
 * It supports outputting the information in both human-readable table format and
 * machine-readable JSON format.
 *
 * Usage:
 *   php artisan midnight:network:info           # Display in table format
 *   php artisan midnight:network:info --json    # Output as JSON
 *
 * Exit codes:
 *   0 - Network information retrieved successfully
 *   1 - Failed to retrieve network information
 *
 * @package VersionTwo\Midnight\Console\Commands
 */
class NetworkInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midnight:network:info
                            {--json : Output network information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display Midnight network information';

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
     * This method fetches network metadata from the Midnight network and displays
     * it either in a human-readable table format or as JSON, depending on the
     * --json option. The information includes network name, chain ID, explorer URI,
     * and all available protocol parameters.
     *
     * @return int The exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        try {
            $this->comment('Fetching Midnight network information...');
            $this->newLine();

            $metadata = $this->client->getNetworkMetadata();

            // Output as JSON if requested
            if ($this->option('json')) {
                $this->line(json_encode($metadata->toArray(), JSON_PRETTY_PRINT));
                return Command::SUCCESS;
            }

            // Display network overview
            $this->info('Network Overview:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Network Name', $metadata->name],
                    ['Chain ID', $metadata->chainId],
                    ['Network Type', $this->getNetworkType($metadata->name)],
                    ['Explorer URI', $metadata->explorerUri ?? 'Not configured'],
                ]
            );

            // Display protocol parameters if available
            if (!empty($metadata->protocolParams)) {
                $this->newLine();
                $this->info('Protocol Parameters:');

                $protocolData = $this->formatProtocolParams($metadata->protocolParams);
                $this->table(['Parameter', 'Value'], $protocolData);
            } else {
                $this->newLine();
                $this->comment('No protocol parameters available.');
            }

            // Display additional network details
            $this->newLine();
            $this->info('Network Status:');

            if ($metadata->isMainnet()) {
                $this->warn('⚠️  You are connected to MAINNET. Transactions will use real assets.');
            } else {
                $this->info('✓ You are connected to a development/test network.');
            }

            if ($metadata->explorerUri) {
                $this->newLine();
                $this->comment('Block Explorer: ' . $metadata->explorerUri);
            }

            return Command::SUCCESS;

        } catch (MidnightException $e) {
            $this->newLine();
            $this->error('Failed to retrieve network information!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Unexpected error occurred!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Format protocol parameters for table display.
     *
     * @param array<string, mixed> $params The protocol parameters
     * @return array<int, array<int, string>> Formatted rows for table display
     */
    private function formatProtocolParams(array $params): array
    {
        $formatted = [];

        foreach ($params as $key => $value) {
            $formatted[] = [
                $this->formatParameterName($key),
                $this->formatParameterValue($value),
            ];
        }

        return $formatted;
    }

    /**
     * Format parameter name for display.
     *
     * @param string $name The parameter name
     * @return string The formatted name
     */
    private function formatParameterName(string $name): string
    {
        // Convert snake_case or camelCase to Title Case
        $name = str_replace(['_', '-'], ' ', $name);
        return ucwords($name);
    }

    /**
     * Format parameter value for display.
     *
     * @param mixed $value The parameter value
     * @return string The formatted value
     */
    private function formatParameterValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }

    /**
     * Get a human-readable network type description.
     *
     * @param string $networkName The network name
     * @return string The network type description
     */
    private function getNetworkType(string $networkName): string
    {
        $name = strtolower($networkName);

        return match ($name) {
            'mainnet' => 'Mainnet (Production)',
            'testnet' => 'Testnet (Testing)',
            'devnet' => 'Devnet (Development)',
            default => ucfirst($name),
        };
    }
}
