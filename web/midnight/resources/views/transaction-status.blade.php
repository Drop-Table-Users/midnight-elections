{{-- Midnight Transaction Status Component --}}
@props(['hash' => ''])

<div class="midnight-transaction" x-data="midnightTransaction('{{ $hash }}')">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Transaction Status</span>
        <span
            class="midnight-transaction-status"
            :class="{
                'midnight-transaction-status--pending': status === 'pending',
                'midnight-transaction-status--confirmed': status === 'confirmed',
                'midnight-transaction-status--failed': status === 'failed'
            }"
            x-text="status"
        ></span>
    </div>

    <div class="mb-2">
        <label class="midnight-label">Transaction Hash</label>
        <p class="midnight-transaction-hash" x-text="txHash"></p>
    </div>

    <div x-show="transaction" class="space-y-2">
        <div>
            <label class="midnight-label">From</label>
            <p class="midnight-wallet-address" x-text="transaction?.from"></p>
        </div>

        <div>
            <label class="midnight-label">To</label>
            <p class="midnight-wallet-address" x-text="transaction?.to"></p>
        </div>

        <div>
            <label class="midnight-label">Value</label>
            <p class="text-sm text-gray-900" x-text="transaction?.value"></p>
        </div>

        <div x-show="transaction?.blockNumber">
            <label class="midnight-label">Block Number</label>
            <p class="text-sm text-gray-900" x-text="transaction?.blockNumber"></p>
        </div>
    </div>

    <div x-show="loading" class="midnight-loading py-4">
        <div class="midnight-spinner"></div>
    </div>

    <div x-show="error" class="midnight-alert-error mt-4">
        <p x-text="error"></p>
    </div>
</div>

<script>
    function midnightTransaction(initialHash) {
        return {
            txHash: initialHash,
            status: 'pending',
            transaction: null,
            loading: false,
            error: '',
            pollInterval: null,

            init() {
                if (this.txHash) {
                    this.fetchTransaction();
                    this.startPolling();
                }
            },

            async fetchTransaction() {
                this.loading = true;
                this.error = '';

                try {
                    const midnight = window.Midnight;
                    if (!midnight) {
                        throw new Error('Midnight API not initialized');
                    }

                    this.transaction = await midnight.client.getTransaction(this.txHash);
                    this.status = this.transaction.status;

                    // Stop polling if transaction is confirmed or failed
                    if (this.status !== 'pending') {
                        this.stopPolling();
                    }
                } catch (err) {
                    this.error = err.message;
                    console.error('Failed to fetch transaction:', err);
                } finally {
                    this.loading = false;
                }
            },

            startPolling() {
                // Poll every 5 seconds
                this.pollInterval = setInterval(() => {
                    this.fetchTransaction();
                }, 5000);
            },

            stopPolling() {
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                }
            },

            destroy() {
                this.stopPolling();
            }
        };
    }
</script>
