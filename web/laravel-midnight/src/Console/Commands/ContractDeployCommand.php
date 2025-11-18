<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Console\Commands;

use Illuminate\Console\Command;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\MidnightException;

/**
 * Deploy a compiled Midnight contract command.
 *
 * This command deploys a compiled Midnight contract to the network. It accepts
 * the path to the compiled contract file and optional initialization arguments
 * in JSON format. The command will prompt for confirmation before deploying
 * to prevent accidental deployments.
 *
 * Usage:
 *   php artisan midnight:contract:deploy path/to/contract.cmpct
 *   php artisan midnight:contract:deploy path/to/contract.cmpct --init-args='{"param":"value"}'
 *
 * Exit codes:
 *   0 - Contract deployed successfully
 *   1 - Deployment failed or was cancelled
 *
 * @package VersionTwo\Midnight\Console\Commands
 */
class ContractDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midnight:contract:deploy
                            {path : Path to the compiled contract file}
                            {--init-args= : Initialization arguments as JSON string}
                            {--no-interaction : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy a compiled Midnight contract';

    /**
     * Create a new command instance.
     *
     * @param ContractGateway $gateway The contract gateway service
     */
    public function __construct(
        private readonly ContractGateway $gateway
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * This method validates the contract path, parses initialization arguments,
     * prompts for confirmation (unless --no-interaction is set), and deploys
     * the contract to the Midnight network. Upon successful deployment, it
     * displays the contract address.
     *
     * @return int The exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $contractPath = $this->argument('path');
        $initArgsJson = $this->option('init-args');

        // Validate contract file exists
        if (!file_exists($contractPath)) {
            $this->error("Contract file not found: {$contractPath}");
            return Command::FAILURE;
        }

        // Parse initialization arguments
        $initArgs = [];
        if ($initArgsJson) {
            try {
                $initArgs = json_decode($initArgsJson, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($initArgs)) {
                    $this->error('Initialization arguments must be a JSON object.');
                    return Command::FAILURE;
                }
            } catch (\JsonException $e) {
                $this->error('Invalid JSON in init-args: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Display deployment information
        $this->info('Contract Deployment');
        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Contract File', $contractPath],
                ['File Size', $this->formatBytes(filesize($contractPath))],
                ['Init Arguments', empty($initArgs) ? 'None' : json_encode($initArgs)],
            ]
        );

        // Confirm deployment (unless --no-interaction is set)
        if (!$this->option('no-interaction')) {
            $this->newLine();

            if (!$this->confirm('Do you want to proceed with deployment?', false)) {
                $this->comment('Deployment cancelled.');
                return Command::FAILURE;
            }
        }

        try {
            $this->newLine();
            $this->comment('Deploying contract to Midnight network...');

            // Deploy the contract
            $contractAddress = $this->gateway->deploy($contractPath, $initArgs);

            $this->newLine();
            $this->info('✓ Contract deployed successfully!');
            $this->newLine();

            $this->table(
                ['Property', 'Value'],
                [
                    ['Contract Address', $contractAddress],
                    ['Network', config('midnight.network', 'unknown')],
                ]
            );

            // Display helpful next steps
            $this->newLine();
            $this->comment('Next steps:');
            $this->line('  • Save the contract address for future interactions');
            $this->line('  • Use midnight:contract:join to join the contract (if required)');
            $this->line('  • Call contract methods using the ContractGateway service');

            return Command::SUCCESS;

        } catch (ContractException $e) {
            $this->newLine();
            $this->error('Contract deployment failed!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;

        } catch (MidnightException $e) {
            $this->newLine();
            $this->error('Deployment failed!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;

        } catch (\InvalidArgumentException $e) {
            $this->newLine();
            $this->error('Invalid deployment parameters!');
            $this->error($e->getMessage());

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
     * Format bytes to human-readable size.
     *
     * @param int $bytes The number of bytes
     * @param int $precision The decimal precision
     * @return string The formatted size string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
