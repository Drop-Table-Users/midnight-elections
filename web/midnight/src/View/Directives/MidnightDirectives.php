<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\View\Directives;

use Illuminate\Support\Facades\Blade;

/**
 * Custom Blade directives for Midnight integration.
 *
 * This class provides a set of custom Blade directives that simplify the integration
 * of Midnight blockchain features into Laravel Blade templates. These directives
 * handle common tasks such as including scripts, rendering wallet UI, and displaying
 * transaction status.
 *
 * Directives provided:
 * - @midnightScripts: Include necessary JS/CSS assets
 * - @midnightWallet: Render wallet connection UI
 * - @midnightVoteForm: Render voting form
 * - @midnightTransactionStatus: Show transaction status
 *
 * @package VersionTwo\Midnight\View\Directives
 */
class MidnightDirectives
{
    /**
     * Register all custom Blade directives.
     *
     * This method should be called from the service provider's boot method
     * to register all custom directives with the Blade compiler.
     *
     * @return void
     */
    public static function register(): void
    {
        static::registerMidnightScripts();
        static::registerMidnightWallet();
        static::registerMidnightVoteForm();
        static::registerMidnightTransactionStatus();
    }

    /**
     * Register the @midnightScripts directive.
     *
     * This directive includes all necessary JavaScript and CSS files for Midnight
     * integration. It automatically includes Alpine.js, the Midnight SDK, and
     * custom styles.
     *
     * Usage in Blade:
     * @midnightScripts
     *
     * Or with custom options:
     * @midnightScripts(['version' => '1.0.0', 'defer' => true])
     *
     * @return void
     */
    protected static function registerMidnightScripts(): void
    {
        Blade::directive('midnightScripts', function ($expression) {
            $options = $expression ? "<?php echo json_decode($expression, true); ?>" : '[]';

            return <<<'BLADE'
<?php
    $midnightOptions = <?php echo $options; ?>;
    $defer = $midnightOptions['defer'] ?? true;
    $deferAttr = $defer ? 'defer' : '';
    $version = $midnightOptions['version'] ?? config('midnight.sdk.version', 'latest');
    $alpineVersion = $midnightOptions['alpine_version'] ?? '3.x.x';
    $cdnUrl = $midnightOptions['cdn_url'] ?? config('midnight.sdk.cdn_url');
?>
<!-- Midnight Integration Scripts -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@{{ $alpineVersion }}/dist/cdn.min.js" {{ $deferAttr }}></script>
@if($cdnUrl)
<script src="{{ $cdnUrl }}/midnight-sdk@{{ $version }}.js" {{ $deferAttr }}></script>
@endif
<script {{ $deferAttr }}>
    window.MidnightConfig = {
        network: '{{ config('midnight.network', 'testnet') }}',
        contractAddress: '{{ config('midnight.contracts.voting_address') }}',
        bridgeUrl: '{{ config('midnight.bridge.base_uri') }}',
        enableLogging: {{ config('midnight.debug', false) ? 'true' : 'false' }}
    };
</script>
<style>
    [x-cloak] { display: none !important; }
    .midnight-loading { opacity: 0.6; pointer-events: none; }
    .midnight-pulse { animation: midnight-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes midnight-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }
</style>
BLADE;
        });
    }

    /**
     * Register the @midnightWallet directive.
     *
     * This directive renders a wallet connection button/interface with optional
     * customization for styling and behavior.
     *
     * Usage in Blade:
     * @midnightWallet
     *
     * Or with custom attributes:
     * @midnightWallet(['class' => 'btn-primary', 'showBalance' => true])
     *
     * @return void
     */
    protected static function registerMidnightWallet(): void
    {
        Blade::directive('midnightWallet', function ($expression) {
            return <<<'BLADE'
<?php
    $walletAttrs = <?php echo $expression ?: '[]'; ?>;
    $class = $walletAttrs['class'] ?? 'inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200';
    $showBalance = $walletAttrs['showBalance'] ?? false;
    $showNetwork = $walletAttrs['showNetwork'] ?? true;
?>
<x-midnight::wallet-connect
    :class="$class"
    :show-balance="$showBalance"
    :show-network="$showNetwork"
/>
BLADE;
        });
    }

    /**
     * Register the @midnightVoteForm directive.
     *
     * This directive renders a complete voting form with zero-knowledge proof
     * generation and submission capabilities.
     *
     * Usage in Blade:
     * @midnightVoteForm(['proposalId' => $proposal->id])
     *
     * Or with full customization:
     * @midnightVoteForm([
     *     'proposalId' => $proposal->id,
     *     'options' => ['Yes', 'No', 'Abstain'],
     *     'onSuccess' => 'handleVoteSuccess'
     * ])
     *
     * @return void
     */
    protected static function registerMidnightVoteForm(): void
    {
        Blade::directive('midnightVoteForm', function ($expression) {
            return <<<'BLADE'
<?php
    $voteFormAttrs = <?php echo $expression ?: '[]'; ?>;
    $proposalId = $voteFormAttrs['proposalId'] ?? null;
    $options = $voteFormAttrs['options'] ?? ['Yes', 'No', 'Abstain'];
    $title = $voteFormAttrs['title'] ?? 'Cast Your Vote';
    $description = $voteFormAttrs['description'] ?? 'Select your vote option below. Your vote will be private and verifiable.';
    $onSuccess = $voteFormAttrs['onSuccess'] ?? null;
    $onError = $voteFormAttrs['onError'] ?? null;
?>
<x-midnight::vote-form
    :proposal-id="$proposalId"
    :options="$options"
    :title="$title"
    :description="$description"
    :on-success="$onSuccess"
    :on-error="$onError"
/>
BLADE;
        });
    }

    /**
     * Register the @midnightTransactionStatus directive.
     *
     * This directive displays the current status of a Midnight transaction with
     * real-time updates and visual indicators.
     *
     * Usage in Blade:
     * @midnightTransactionStatus(['txHash' => $transaction->hash])
     *
     * Or with custom options:
     * @midnightTransactionStatus([
     *     'txHash' => $transaction->hash,
     *     'showDetails' => true,
     *     'autoRefresh' => true
     * ])
     *
     * @return void
     */
    protected static function registerMidnightTransactionStatus(): void
    {
        Blade::directive('midnightTransactionStatus', function ($expression) {
            return <<<'BLADE'
<?php
    $txAttrs = <?php echo $expression ?: '[]'; ?>;
    $txHash = $txAttrs['txHash'] ?? null;
    $showDetails = $txAttrs['showDetails'] ?? true;
    $autoRefresh = $txAttrs['autoRefresh'] ?? true;
    $refreshInterval = $txAttrs['refreshInterval'] ?? 5000;
?>
<x-midnight::transaction-status
    :tx-hash="$txHash"
    :show-details="$showDetails"
    :auto-refresh="$autoRefresh"
    :refresh-interval="$refreshInterval"
/>
BLADE;
        });
    }
}
