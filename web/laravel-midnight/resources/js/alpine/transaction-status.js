// resources/js/alpine/transaction-status.js
import { queryTransactionStatus, waitForTransactionConfirmation } from '../midnight/client';

/**
 * Alpine.js component for transaction status tracking
 *
 * @example
 * ```html
 * <div x-data="transactionStatus({
 *   transactionHash: '0x...',
 *   autoPoll: true
 * })">
 *   <div x-show="transactionHash">
 *     <!-- Status Icon -->
 *     <div x-show="status === 'pending'" class="spinner"></div>
 *     <div x-show="status === 'confirmed'" class="success-icon">✓</div>
 *     <div x-show="status === 'failed'" class="error-icon">✗</div>
 *
 *     <!-- Status Info -->
 *     <h3 x-text="statusTitle"></h3>
 *     <p x-text="statusMessage"></p>
 *
 *     <!-- Transaction Hash -->
 *     <p>Hash: <code x-text="displayHash"></code></p>
 *     <button @click="copyHash">Copy</button>
 *
 *     <!-- Additional Info -->
 *     <div x-show="blockNumber">
 *       Block: <span x-text="blockNumber"></span>
 *     </div>
 *     <div x-show="confirmations">
 *       Confirmations: <span x-text="confirmations"></span>
 *     </div>
 *
 *     <!-- Error -->
 *     <div x-show="errorMessage" x-text="errorMessage"></div>
 *
 *     <!-- Actions -->
 *     <button @click="refresh" :disabled="isRefreshing" x-show="isPending">
 *       Refresh Status
 *     </button>
 *   </div>
 *
 *   <div x-show="!transactionHash">
 *     <p x-text="emptyMessage"></p>
 *   </div>
 * </div>
 * ```
 */
export default function transactionStatus(config = {}) {
  return {
    // Configuration
    transactionHash: config.transactionHash || '',
    explorerBaseUrl: config.explorerBaseUrl || '',
    autoPoll: config.autoPoll !== undefined ? config.autoPoll : true,
    pollInterval: config.pollInterval || 3000,
    maxPollAttempts: config.maxPollAttempts || 30,
    hashTruncateLength: config.hashTruncateLength || 10,
    emptyMessage: config.emptyMessage || 'No transaction to display',

    // State
    status: 'unknown',
    blockNumber: null,
    confirmations: null,
    timestamp: null,
    errorMessage: null,
    isRefreshing: false,
    copied: false,
    pollAttempts: 0,
    pollTimer: null,

    // Computed
    get displayHash() {
      if (!this.transactionHash) return '';
      const len = this.hashTruncateLength;
      return `${this.transactionHash.slice(0, len)}...${this.transactionHash.slice(-len)}`;
    },

    get explorerUrl() {
      if (!this.explorerBaseUrl || !this.transactionHash) return '';
      return `${this.explorerBaseUrl}/tx/${this.transactionHash}`;
    },

    get statusTitle() {
      switch (this.status) {
        case 'pending':
        case 'submitted':
          return 'Transaction Pending';
        case 'confirmed':
          return 'Transaction Confirmed';
        case 'failed':
          return 'Transaction Failed';
        default:
          return 'Transaction Status Unknown';
      }
    },

    get statusMessage() {
      switch (this.status) {
        case 'pending':
        case 'submitted':
          return 'Your transaction is being processed on the blockchain.';
        case 'confirmed':
          return 'Your transaction has been successfully confirmed.';
        case 'failed':
          return 'Your transaction has failed. Please try again.';
        default:
          return 'Unable to determine transaction status.';
      }
    },

    get formattedTimestamp() {
      if (!this.timestamp) return '';
      return new Date(this.timestamp * 1000).toLocaleString();
    },

    get statusClass() {
      return {
        pending: this.status === 'pending' || this.status === 'submitted',
        confirmed: this.status === 'confirmed',
        failed: this.status === 'failed',
        unknown: this.status === 'unknown',
      };
    },

    get isPending() {
      return this.status === 'pending' || this.status === 'submitted';
    },

    get isConfirmed() {
      return this.status === 'confirmed';
    },

    get isFailed() {
      return this.status === 'failed';
    },

    get progressPercent() {
      if (this.status === 'confirmed') return 100;
      if (this.status === 'failed') return 0;
      return Math.min((this.pollAttempts / this.maxPollAttempts) * 100, 95);
    },

    get progressText() {
      return `Checking status... (${this.pollAttempts}/${this.maxPollAttempts})`;
    },

    // Methods
    async init() {
      // Watch for transaction hash changes
      this.$watch('transactionHash', (newHash) => {
        if (newHash) {
          this.stopPolling();
          this.status = 'pending';
          this.pollAttempts = 0;
          this.startPolling();
        } else {
          this.stopPolling();
          this.status = 'unknown';
        }
      });

      // Start polling if hash is provided
      if (this.transactionHash && this.autoPoll) {
        this.startPolling();
      }
    },

    async fetchStatus() {
      if (!this.transactionHash) return;

      try {
        this.isRefreshing = true;
        const info = await queryTransactionStatus(this.transactionHash);

        this.status = info.status;
        this.blockNumber = info.blockNumber;
        this.timestamp = info.timestamp;
        this.errorMessage = info.error;

        // Dispatch status change event
        this.$dispatch('transaction-status-change', {
          status: info.status,
          info
        });

        if (info.status === 'confirmed') {
          this.stopPolling();
          this.$dispatch('transaction-confirmed', { info });
        } else if (info.status === 'failed') {
          this.stopPolling();
          this.$dispatch('transaction-failed', { info });
        }

        this.pollAttempts++;

        if (this.pollAttempts >= this.maxPollAttempts) {
          this.stopPolling();
          this.errorMessage = 'Maximum polling attempts reached';
        }
      } catch (err) {
        console.error('Failed to fetch transaction status:', err);
        this.errorMessage = err instanceof Error ? err.message : 'Failed to fetch status';
        this.$dispatch('transaction-error', { error: this.errorMessage });
      } finally {
        this.isRefreshing = false;
      }
    },

    async refresh() {
      await this.fetchStatus();
    },

    startPolling() {
      if (!this.autoPoll || this.pollTimer) return;

      this.pollAttempts = 0;
      this.pollTimer = setInterval(() => {
        this.fetchStatus();
      }, this.pollInterval);

      // Fetch immediately
      this.fetchStatus();
    },

    stopPolling() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    },

    async copyHash() {
      if (!this.transactionHash) return;

      try {
        await navigator.clipboard.writeText(this.transactionHash);
        this.copied = true;
        setTimeout(() => {
          this.copied = false;
        }, 2000);
      } catch (err) {
        console.error('Failed to copy hash:', err);
      }
    },

    async waitForConfirmation() {
      if (!this.transactionHash) return;

      this.stopPolling();

      try {
        const info = await waitForTransactionConfirmation(this.transactionHash, {
          maxAttempts: this.maxPollAttempts,
          pollInterval: this.pollInterval,
        });

        this.status = info.status;
        this.blockNumber = info.blockNumber;
        this.timestamp = info.timestamp;
        this.errorMessage = info.error;

        if (info.status === 'confirmed') {
          this.$dispatch('transaction-confirmed', { info });
        } else if (info.status === 'failed') {
          this.$dispatch('transaction-failed', { info });
        }

        return info;
      } catch (err) {
        this.errorMessage = err instanceof Error ? err.message : 'Confirmation wait failed';
        throw err;
      }
    },

    // Cleanup on destroy
    destroy() {
      this.stopPolling();
    },
  };
}

// Auto-register with Alpine if available
if (typeof window !== 'undefined' && window.Alpine) {
  window.Alpine.data('transactionStatus', transactionStatus);
}
