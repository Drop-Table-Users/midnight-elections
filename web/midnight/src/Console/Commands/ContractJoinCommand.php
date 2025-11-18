<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Console\Commands;

use Illuminate\Console\Command;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\MidnightException;

/**
 * Join an existing Midnight contract command.
 *
 * This command allows the current wallet to join an existing deployed contract.
 * Joining a contract may be required before interacting with certain contract types,
 * particularly those that maintain a participant list or require registration.
 *
 * Usage:
 *   php artisan midnight:contract:join <contract-address>
 *   php artisan midnight:contract:join <contract-address> --args='{"param":"value"}'
 *
 * Exit codes:
 *   0 - Successfully joined the contract
 *   1 - Join operation failed
 *
 * @package VersionTwo\Midnight\Console\Commands
 */
class ContractJoinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midnight:contract:join
                            {address : The contract address to join}
                            {--args= : Join arguments as JSON string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Join an existing Midnight contract';

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
     * This method validates the contract address, parses optional join arguments,
     * and attempts to join the contract. Upon successful join, it displays the
     * result including any confirmation data or participant information.
     *
     * @return int The exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $contractAddress = $this->argument('address');
        $argsJson = $this->option('args');

        // Validate contract address format
        if (!$this->isValidAddress($contractAddress)) {
            $this->error('Invalid contract address format.');
            $this->comment('Expected format: A valid Midnight contract address (e.g., 0x...)');
            return Command::FAILURE;
        }

        // Parse join arguments
        $args = [];
        if ($argsJson) {
            try {
                $args = json_decode($argsJson, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($args)) {
                    $this->error('Join arguments must be a JSON object.');
                    return Command::FAILURE;
                }
            } catch (\JsonException $e) {
                $this->error('Invalid JSON in args: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Display join information
        $this->info('Contract Join Operation');
        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Contract Address', $contractAddress],
                ['Join Arguments', empty($args) ? 'None' : json_encode($args)],
                ['Network', config('midnight.network', 'unknown')],
            ]
        );

        try {
            $this->newLine();
            $this->comment('Joining contract on Midnight network...');

            // Join the contract
            $result = $this->gateway->join($contractAddress, $args);

            $this->newLine();

            if ($result->success) {
                $this->info('✓ Successfully joined the contract!');
            } else {
                $this->warn('⚠️  Join operation completed with warnings.');
            }

            $this->newLine();

            // Display join result details
            $this->info('Join Result:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Success', $result->success ? 'Yes' : 'No'],
                    ['Error', $result->error ?? 'None'],
                    ['Has Value', $result->hasValue() ? 'Yes' : 'No'],
                ]
            );

            // Display the result value if available
            if ($result->hasValue()) {
                $this->newLine();
                $this->comment('Result Data:');

                if (is_array($result->value)) {
                    $resultData = [];
                    foreach ($result->value as $key => $value) {
                        $resultData[] = [
                            $key,
                            is_array($value) ? json_encode($value) : (string) $value
                        ];
                    }
                    $this->table(['Key', 'Value'], $resultData);
                } else {
                    $this->line(json_encode($result->value, JSON_PRETTY_PRINT));
                }
            }

            // Display metadata if available
            if (!empty($result->metadata)) {
                $this->newLine();
                $this->comment('Additional Metadata:');
                $metadataRows = [];
                foreach ($result->metadata as $key => $value) {
                    $metadataRows[] = [
                        $key,
                        is_array($value) ? json_encode($value) : (string) $value
                    ];
                }
                $this->table(['Key', 'Value'], $metadataRows);
            }

            // Display helpful next steps
            $this->newLine();
            $this->comment('Next steps:');
            $this->line('  • You can now interact with the contract');
            $this->line('  • Use the ContractGateway service to call contract methods');
            $this->line('  • Monitor your transactions using midnight:network:info');

            return Command::SUCCESS;

        } catch (ContractException $e) {
            $this->newLine();
            $this->error('Failed to join contract!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;

        } catch (MidnightException $e) {
            $this->newLine();
            $this->error('Join operation failed!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->comment('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;

        } catch (\InvalidArgumentException $e) {
            $this->newLine();
            $this->error('Invalid join parameters!');
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
     * Validate if the given string is a valid contract address.
     *
     * @param string $address The address to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidAddress(string $address): bool
    {
        // Basic validation - adjust regex pattern based on Midnight address format
        // This is a placeholder and should be adjusted to match actual Midnight address format
        if (empty($address) || strlen($address) < 10) {
            return false;
        }

        // Check if it starts with a common prefix (adjust based on actual format)
        // For now, we accept any non-empty string with reasonable length
        return true;
    }
}
