@props([
    'txHash' => null,
    'showDetails' => true,
    'autoRefresh' => true,
    'refreshInterval' => 5000,
])

<div
    x-data="{
        txHash: '{{ $txHash }}',
        status: 'pending',
        confirmations: 0,
        blockNumber: null,
        timestamp: null,
        gasUsed: null,
        error: null,
        loading: true,
        autoRefresh: {{ $autoRefresh ? 'true' : 'false' }},
        refreshInterval: {{ $refreshInterval }},
        intervalId: null,

        async fetchStatus() {
            if (!this.txHash) {
                this.error = 'No transaction hash provided';
                this.loading = false;
                return;
            }

            try {
                const response = await fetch(`/api/midnight/transaction/${this.txHash}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch transaction status');
                }

                const data = await response.json();
                this.updateStatus(data);
                this.loading = false;

                // Stop auto-refresh if transaction is confirmed or failed
                if (this.status === 'confirmed' || this.status === 'failed') {
                    this.stopAutoRefresh();
                }
            } catch (err) {
                this.error = err.message;
                this.loading = false;
                this.stopAutoRefresh();
            }
        },

        updateStatus(data) {
            this.status = data.status || 'pending';
            this.confirmations = data.confirmations || 0;
            this.blockNumber = data.blockNumber || null;
            this.timestamp = data.timestamp || null;
            this.gasUsed = data.gasUsed || null;

            this.$dispatch('transaction-status-updated', {
                txHash: this.txHash,
                status: this.status,
                confirmations: this.confirmations
            });
        },

        startAutoRefresh() {
            if (this.autoRefresh && !this.intervalId) {
                this.intervalId = setInterval(() => {
                    this.fetchStatus();
                }, this.refreshInterval);
            }
        },

        stopAutoRefresh() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },

        getStatusColor() {
            switch (this.status) {
                case 'confirmed':
                    return 'green';
                case 'failed':
                    return 'red';
                case 'pending':
                    return 'yellow';
                default:
                    return 'gray';
            }
        },

        getStatusIcon() {
            switch (this.status) {
                case 'confirmed':
                    return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                case 'failed':
                    return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z';
                case 'pending':
                    return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
                default:
                    return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            }
        },

        formatTimestamp(ts) {
            if (!ts) return 'N/A';
            const date = new Date(ts * 1000);
            return date.toLocaleString();
        },

        formatHash(hash) {
            if (!hash) return '';
            return hash.substring(0, 10) + '...' + hash.substring(hash.length - 8);
        },

        copyToClipboard() {
            if (navigator.clipboard && this.txHash) {
                navigator.clipboard.writeText(this.txHash);
                this.$dispatch('notify', { message: 'Transaction hash copied to clipboard' });
            }
        }
    }"
    x-init="
        fetchStatus();
        startAutoRefresh();
    "
    x-effect="
        // Update status when txHash prop changes
        if (txHash !== '{{ $txHash }}') {
            txHash = '{{ $txHash }}';
            fetchStatus();
            startAutoRefresh();
        }
    "
    @destroy.window="stopAutoRefresh()"
    class="midnight-transaction-status bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4"
>
    <!-- Loading State -->
    <div x-show="loading" x-cloak class="flex items-center justify-center py-8">
        <x-midnight::loading-spinner class="w-8 h-8 text-indigo-600 dark:text-indigo-400"></x-midnight::loading-spinner>
        <span class="ml-3 text-gray-600 dark:text-gray-400">Loading transaction status...</span>
    </div>

    <!-- Error State -->
    <div x-show="error && !loading" x-cloak>
        <x-midnight::error-alert x-text="error"></x-midnight::error-alert>
    </div>

    <!-- Transaction Details -->
    <div x-show="!loading && !error" x-cloak class="space-y-4">
        <!-- Status Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div
                    class="w-3 h-3 rounded-full mr-3"
                    :class="{
                        'bg-green-500': status === 'confirmed',
                        'bg-red-500': status === 'failed',
                        'bg-yellow-500 midnight-pulse': status === 'pending',
                        'bg-gray-500': status !== 'confirmed' && status !== 'failed' && status !== 'pending'
                    }"
                ></div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white capitalize" x-text="status"></h4>
            </div>

            <button
                @click="fetchStatus()"
                :disabled="loading"
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                type="button"
                title="Refresh status"
                aria-label="Refresh transaction status"
            >
                <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </button>
        </div>

        <!-- Transaction Hash -->
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Transaction Hash</p>
                    <p class="text-sm font-mono text-gray-900 dark:text-white truncate" x-text="txHash"></p>
                </div>
                <button
                    @click="copyToClipboard()"
                    class="ml-3 p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                    type="button"
                    title="Copy to clipboard"
                    aria-label="Copy transaction hash to clipboard"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Confirmations Progress -->
        <div x-show="status === 'pending' || status === 'confirmed'">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-700 dark:text-gray-300">Confirmations</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="`${confirmations}/12`"></span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                    class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-500"
                    :style="`width: ${Math.min((confirmations / 12) * 100, 100)}%`"
                ></div>
            </div>
        </div>

        @if($showDetails)
        <!-- Additional Details -->
        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Block Number</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="blockNumber || 'Pending'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Timestamp</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatTimestamp(timestamp)"></p>
            </div>
            <div x-show="gasUsed">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Gas Used</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="gasUsed ? gasUsed.toLocaleString() : 'N/A'"></p>
            </div>
        </div>
        @endif

        <!-- View on Explorer -->
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <a
                :href="`{{ config('midnight.explorer_url', 'https://explorer.midnight.network') }}/tx/${txHash}`"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
            >
                View on Explorer
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
        </div>
    </div>
</div>
