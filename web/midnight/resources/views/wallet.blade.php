{{-- Midnight Wallet Component --}}
<div class="midnight-wallet" x-data="midnightWallet()">
    <div class="midnight-wallet-header">
        <h3 class="midnight-wallet-title">Midnight Wallet</h3>
        <button
            x-show="!connected"
            @click="connect"
            class="midnight-btn-primary"
            :disabled="loading"
        >
            <span x-show="!loading">Connect Wallet</span>
            <span x-show="loading" class="midnight-spinner"></span>
        </button>
        <button
            x-show="connected"
            @click="disconnect"
            class="midnight-btn-secondary"
        >
            Disconnect
        </button>
    </div>

    <div class="midnight-card-body" x-show="connected">
        <div class="mb-4">
            <label class="midnight-wallet-balance-label">Address</label>
            <p class="midnight-wallet-address" x-text="address"></p>
        </div>

        <div>
            <label class="midnight-wallet-balance-label">Balance</label>
            <p class="midnight-wallet-balance" x-text="balance"></p>
        </div>
    </div>

    <div x-show="error" class="midnight-alert-error mt-4">
        <p x-text="error"></p>
    </div>
</div>

<script>
    function midnightWallet() {
        return {
            connected: false,
            loading: false,
            address: '',
            balance: '0.000000',
            error: '',

            async connect() {
                this.loading = true;
                this.error = '';

                try {
                    const midnight = window.Midnight;
                    if (!midnight) {
                        throw new Error('Midnight API not initialized');
                    }

                    const connection = await midnight.wallet.connect();
                    this.address = connection.address;
                    this.connected = true;

                    // Get balance
                    this.balance = await midnight.wallet.getBalance();
                } catch (err) {
                    this.error = err.message;
                    console.error('Wallet connection failed:', err);
                } finally {
                    this.loading = false;
                }
            },

            async disconnect() {
                try {
                    const midnight = window.Midnight;
                    if (midnight) {
                        await midnight.wallet.disconnect();
                    }
                    this.connected = false;
                    this.address = '';
                    this.balance = '0.000000';
                } catch (err) {
                    this.error = err.message;
                    console.error('Wallet disconnection failed:', err);
                }
            }
        };
    }
</script>
