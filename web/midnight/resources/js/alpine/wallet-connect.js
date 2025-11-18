// resources/js/alpine/wallet-connect.js
import {
  isWalletAvailable,
  connectWallet,
  disconnectWallet,
  getWalletState,
  getWalletAddress,
  getNetworkInfo,
  isWalletConnected,
} from '../midnight/wallet';

/**
 * Alpine.js component for Midnight wallet connection
 *
 * @example
 * ```html
 * <div x-data="walletConnect()">
 *   <div x-show="!isAvailable">
 *     <p x-text="walletNotAvailableMessage"></p>
 *   </div>
 *
 *   <div x-show="isAvailable && !isConnected">
 *     <button @click="connect" :disabled="isConnecting">
 *       <span x-show="isConnecting">Connecting...</span>
 *       <span x-show="!isConnecting">Connect Wallet</span>
 *     </button>
 *   </div>
 *
 *   <div x-show="isConnected">
 *     <p>Address: <code x-text="displayAddress"></code></p>
 *     <p x-show="network">Network: <span x-text="network"></span></p>
 *     <button @click="disconnect">Disconnect</button>
 *   </div>
 *
 *   <div x-show="error" x-text="error"></div>
 * </div>
 * ```
 */
export default function walletConnect(config = {}) {
  return {
    // Configuration
    walletNotAvailableMessage: config.walletNotAvailableMessage ||
      'Midnight wallet not detected. Please install Lace or another compatible wallet.',
    addressTruncateLength: config.addressTruncateLength || 8,
    showNetwork: config.showNetwork !== undefined ? config.showNetwork : true,

    // State
    isAvailable: false,
    isConnected: false,
    isConnecting: false,
    address: null,
    network: null,
    walletState: null,
    error: null,
    copied: false,

    // Computed
    get displayAddress() {
      if (!this.address) return '';
      const len = this.addressTruncateLength;
      return `${this.address.slice(0, len)}...${this.address.slice(-len)}`;
    },

    get connectionState() {
      if (!this.isAvailable) return 'unavailable';
      if (this.isConnecting) return 'connecting';
      if (this.isConnected) return 'connected';
      if (this.error) return 'error';
      return 'disconnected';
    },

    // Methods
    async init() {
      // Check wallet availability on mount
      this.checkAvailability();

      // Set up event listeners if needed
      // (e.g., listen for account changes from wallet)
      if (window.addEventListener) {
        window.addEventListener('midnight:accountsChanged', () => {
          if (this.isConnected) {
            this.refreshState();
          }
        });
      }
    },

    checkAvailability() {
      this.isAvailable = isWalletAvailable();
      if (this.isAvailable) {
        this.isConnected = isWalletConnected();
      }
    },

    async connect() {
      if (this.isConnecting || this.isConnected) {
        return;
      }

      this.error = null;
      this.isConnecting = true;

      try {
        await connectWallet();
        await this.refreshState();

        this.isConnected = true;

        // Dispatch custom event for parent components
        this.$dispatch('wallet-connected', { address: this.address });
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to connect to wallet';
        this.error = errorMessage;
        this.isConnected = false;

        this.$dispatch('wallet-error', { error: errorMessage });
      } finally {
        this.isConnecting = false;
      }
    },

    disconnect() {
      disconnectWallet();

      this.isConnected = false;
      this.walletState = null;
      this.address = null;
      this.network = null;
      this.error = null;

      this.$dispatch('wallet-disconnected');
    },

    async refreshState() {
      if (!this.isConnected) {
        throw new Error('Wallet not connected');
      }

      try {
        const state = await getWalletState(true);
        this.walletState = state;

        const addr = await getWalletAddress();
        this.address = addr;

        if (this.showNetwork) {
          const net = await getNetworkInfo();
          this.network = net;
        }

        this.error = null;
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to refresh wallet state';
        this.error = errorMessage;
        throw err;
      }
    },

    async copyAddress() {
      if (!this.address) return;

      try {
        await navigator.clipboard.writeText(this.address);
        this.copied = true;
        setTimeout(() => {
          this.copied = false;
        }, 2000);
      } catch (err) {
        console.error('Failed to copy address:', err);
      }
    },

    // Helper for keyboard navigation
    handleKeyboard(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (!this.isConnected) {
          this.connect();
        }
      }
    },
  };
}

// Auto-register with Alpine if available
if (typeof window !== 'undefined' && window.Alpine) {
  window.Alpine.data('walletConnect', walletConnect);
}
