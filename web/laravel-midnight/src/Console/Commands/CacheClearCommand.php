<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Clear Midnight-related caches command.
 *
 * This command provides a way to clear cached data related to Midnight operations,
 * including network metadata, contract state, and other cached information. It
 * supports clearing all Midnight caches or specific cache tags.
 *
 * Usage:
 *   php artisan midnight:cache:clear               # Clear all Midnight caches
 *   php artisan midnight:cache:clear --tags=network,contracts  # Clear specific tags
 *
 * Exit codes:
 *   0 - Cache cleared successfully
 *   1 - Cache clear operation failed
 *
 * @package VersionTwo\Midnight\Console\Commands
 */
class CacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midnight:cache:clear
                            {--tags= : Comma-separated list of cache tags to clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Midnight-related caches';

    /**
     * Available cache tags for Midnight.
     *
     * @var array<string, string>
     */
    private const AVAILABLE_TAGS = [
        'network' => 'Network metadata and configuration',
        'contracts' => 'Contract state and metadata',
        'proofs' => 'Zero-knowledge proof caches',
        'wallet' => 'Wallet state and balances',
        'transactions' => 'Transaction status and history',
    ];

    /**
     * Create a new command instance.
     *
     * @param CacheRepository $cache The cache repository
     */
    public function __construct(
        private readonly CacheRepository $cache
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * This method clears Midnight-related caches either for all tags or for
     * specific tags if provided via the --tags option. It displays what was
     * cleared and provides feedback on the operation's success.
     *
     * @return int The exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $tagsOption = $this->option('tags');

        try {
            // Determine which tags to clear
            $tagsToClear = $this->parseTags($tagsOption);

            if (empty($tagsToClear)) {
                $this->error('No valid tags specified.');
                $this->displayAvailableTags();
                return Command::FAILURE;
            }

            // Display what will be cleared
            $this->info('Midnight Cache Clear Operation');
            $this->newLine();

            if ($tagsOption === null) {
                $this->comment('Clearing ALL Midnight caches...');
            } else {
                $this->comment('Clearing specific cache tags: ' . implode(', ', $tagsToClear));
            }

            $this->newLine();

            // Clear the caches
            $clearedCount = $this->clearCaches($tagsToClear);

            // Display results
            $this->newLine();
            $this->info('✓ Cache cleared successfully!');
            $this->newLine();

            $this->table(
                ['Property', 'Value'],
                [
                    ['Tags Cleared', count($tagsToClear)],
                    ['Cache Keys Affected', $clearedCount > 0 ? $clearedCount : 'All'],
                    ['Cache Store', config('midnight.cache.store', 'default')],
                    ['Cache Prefix', config('midnight.cache.prefix', 'midnight')],
                ]
            );

            // Display which tags were cleared
            if (!empty($tagsToClear)) {
                $this->newLine();
                $this->comment('Cleared Tags:');
                foreach ($tagsToClear as $tag) {
                    $description = self::AVAILABLE_TAGS[$tag] ?? 'Unknown';
                    $this->line("  • {$tag}: {$description}");
                }
            }

            $this->newLine();
            $this->comment('Note: Fresh data will be fetched from the Midnight network on next request.');

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Failed to clear cache!');
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
     * Parse the tags option into an array of tag names.
     *
     * @param string|null $tagsOption The tags option value
     * @return array<int, string> Array of tag names to clear
     */
    private function parseTags(?string $tagsOption): array
    {
        // If no tags specified, return all available tags
        if ($tagsOption === null) {
            return array_keys(self::AVAILABLE_TAGS);
        }

        // Parse comma-separated tags
        $requestedTags = array_map('trim', explode(',', $tagsOption));

        // Validate tags and return only valid ones
        $validTags = [];
        foreach ($requestedTags as $tag) {
            if (isset(self::AVAILABLE_TAGS[$tag])) {
                $validTags[] = $tag;
            } else {
                $this->warn("Unknown tag: {$tag} (skipped)");
            }
        }

        return $validTags;
    }

    /**
     * Clear caches for the specified tags.
     *
     * @param array<int, string> $tags The tags to clear
     * @return int Number of keys cleared (0 if using tags)
     */
    private function clearCaches(array $tags): int
    {
        $prefix = config('midnight.cache.prefix', 'midnight');
        $clearedCount = 0;

        // Get the configured cache store
        $store = config('midnight.cache.store');
        $cache = $store ? cache()->store($store) : $this->cache;

        // Try to use tagged cache clearing if the driver supports it
        try {
            // Check if the cache driver supports tags
            if (method_exists($cache, 'tags')) {
                // Clear each tag
                foreach ($tags as $tag) {
                    $tagKey = "{$prefix}:{$tag}";
                    $cache->tags($tagKey)->flush();
                    $clearedCount++;
                }
            } else {
                // Fallback: Clear by prefix pattern
                $this->clearByPrefix($cache, $prefix, $tags);
            }
        } catch (\Exception $e) {
            // Fallback to clearing by prefix if tagging fails
            $this->clearByPrefix($cache, $prefix, $tags);
        }

        return $clearedCount;
    }

    /**
     * Clear cache by prefix pattern (fallback for non-tagging drivers).
     *
     * @param CacheRepository $cache The cache repository
     * @param string $prefix The cache prefix
     * @param array<int, string> $tags The tags to clear
     * @return void
     */
    private function clearByPrefix(CacheRepository $cache, string $prefix, array $tags): void
    {
        // For each tag, attempt to clear known cache keys
        foreach ($tags as $tag) {
            $patterns = $this->getCacheKeyPatterns($prefix, $tag);

            foreach ($patterns as $pattern) {
                try {
                    $cache->forget($pattern);
                } catch (\Exception $e) {
                    // Continue even if some keys fail
                    if ($this->output->isVerbose()) {
                        $this->warn("Failed to clear: {$pattern}");
                    }
                }
            }
        }
    }

    /**
     * Get cache key patterns for a specific tag.
     *
     * @param string $prefix The cache prefix
     * @param string $tag The tag name
     * @return array<int, string> Array of cache key patterns
     */
    private function getCacheKeyPatterns(string $prefix, string $tag): array
    {
        return match ($tag) {
            'network' => [
                "{$prefix}:network_metadata",
                "{$prefix}:chain_id",
                "{$prefix}:protocol_params",
            ],
            'contracts' => [
                "{$prefix}:contract:*",
                "{$prefix}:contract_state:*",
            ],
            'proofs' => [
                "{$prefix}:proof:*",
                "{$prefix}:proof_cache:*",
            ],
            'wallet' => [
                "{$prefix}:wallet:*",
                "{$prefix}:balance:*",
            ],
            'transactions' => [
                "{$prefix}:tx:*",
                "{$prefix}:transaction:*",
            ],
            default => ["{$prefix}:{$tag}:*"],
        };
    }

    /**
     * Display available cache tags.
     *
     * @return void
     */
    private function displayAvailableTags(): void
    {
        $this->newLine();
        $this->comment('Available cache tags:');

        $tagData = [];
        foreach (self::AVAILABLE_TAGS as $tag => $description) {
            $tagData[] = [$tag, $description];
        }

        $this->table(['Tag', 'Description'], $tagData);

        $this->newLine();
        $this->comment('Usage examples:');
        $this->line('  php artisan midnight:cache:clear');
        $this->line('  php artisan midnight:cache:clear --tags=network');
        $this->line('  php artisan midnight:cache:clear --tags=network,contracts');
    }
}
