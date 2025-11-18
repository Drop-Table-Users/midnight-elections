@props([
    'showBalance' => false,
    'showNetwork' => true,
    'class' => 'inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200',
])

<div
    x-data="{
        connected: false,
        connecting: false,
        address: null,
        balance: null,
        network: null,
        error: null,

        async connect() {
            this.connecting = true;
            this.error = null;

            try {
                if (typeof window.midnight === 'undefined') {
                    throw new Error('Midnight wallet not detected. Please install a compatible wallet extension.');
                }

                const accounts = await window.midnight.request({ method: 'eth_requestAccounts' });
                this.address = accounts[0];
                this.connected = true;

                @if($showBalance)
                await this.updateBalance();
                @endif

                @if($showNetwork)
                await this.updateNetwork();
                @endif

                // Listen for account changes
                window.midnight.on('accountsChanged', (accounts) => {
                    if (accounts.length === 0) {
                        this.disconnect();
                    } else {
                        this.address = accounts[0];
                        @if($showBalance)
                        this.updateBalance();
                        @endif
                    }
                });

                // Listen for network changes
                window.midnight.on('chainChanged', () => {
                    window.location.reload();
                });

                this.$dispatch('wallet-connected', { address: this.address });
            } catch (err) {
                this.error = err.message;
                this.$dispatch('wallet-error', { error: err.message });
            } finally {
                this.connecting = false;
            }
        },

        disconnect() {
            this.connected = false;
            this.address = null;
            this.balance = null;
            this.network = null;
            this.$dispatch('wallet-disconnected');
        },

        @if($showBalance)
        async updateBalance() {
            try {
                const balance = await window.midnight.request({
                    method: 'eth_getBalance',
                    params: [this.address, 'latest']
                });
                // Convert from Wei to Midnight tokens (adjust decimals as needed)
                this.balance = (parseInt(balance, 16) / 1e18).toFixed(4);
            } catch (err) {
                console.error('Failed to fetch balance:', err);
            }
        },
        @endif

        @if($showNetwork)
        async updateNetwork() {
            try {
                const chainId = await window.midnight.request({ method: 'eth_chainId' });
                const networks = {
                    '0x1': 'Mainnet',
                    '0x5': 'Testnet',
                    '0x539': 'Local'
                };
                this.network = networks[chainId] || 'Unknown';
            } catch (err) {
                console.error('Failed to fetch network:', err);
            }
        },
        @endif

        formatAddress(addr) {
            if (!addr) return '';
            return addr.substring(0, 6) + '...' + addr.substring(addr.length - 4);
        }
    }"
    x-init="
        // Auto-connect if previously connected
        if (typeof window.midnight !== 'undefined' && localStorage.getItem('midnight-wallet-connected') === 'true') {
            connect();
        }
    "
    class="midnight-wallet-connect"
>
    <!-- Error Alert -->
    <div x-show="error" x-cloak class="mb-4">
        <x-midnight::error-alert x-text="error"></x-midnight::error-alert>
    </div>

    <!-- Connection Button (when not connected) -->
    <button
        x-show="!connected"
        x-cloak
        @click="connect()"
        :disabled="connecting"
        :class="connecting ? 'opacity-50 cursor-not-allowed' : ''"
        {{ $attributes->merge(['class' => $class]) }}
        type="button"
        aria-label="Connect Midnight Wallet"
    >
        <svg
            x-show="!connecting"
            class="w-5 h-5 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
        </svg>
        <x-midnight::loading-spinner x-show="connecting" class="w-5 h-5 mr-2"></x-midnight::loading-spinner>
        <span x-text="connecting ? 'Connecting...' : 'Connect Wallet'"></span>
    </button>

    <!-- Connected State -->
    <div
        x-show="connected"
        x-cloak
        class="inline-flex items-center space-x-3"
    >
        @if($showNetwork)
        <x-midnight::network-indicator :network="network" x-show="network"></x-midnight::network-indicator>
        @endif

        <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 rounded-lg px-4 py-2 shadow-sm border border-gray-200 dark:border-gray-700">
            @if($showBalance)
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-show="balance">
                <span x-text="balance"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">MIDNIGHT</span>
            </div>
            <div class="h-4 w-px bg-gray-300 dark:bg-gray-600" x-show="balance"></div>
            @endif

            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full" title="Connected"></div>
                <span class="text-sm font-mono text-gray-900 dark:text-gray-100" x-text="formatAddress(address)"></span>
            </div>

            <button
                @click="disconnect()"
                class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                type="button"
                aria-label="Disconnect Wallet"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
