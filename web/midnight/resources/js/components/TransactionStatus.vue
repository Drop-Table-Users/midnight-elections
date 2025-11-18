<template>
  <div class="transaction-status">
    <!-- Transaction Info -->
    <div v-if="transactionHash" class="status-container" :class="statusClass">
      <!-- Status Icon -->
      <div class="status-icon">
        <!-- Pending -->
        <svg
          v-if="status === 'pending' || status === 'submitted'"
          class="animate-spin h-8 w-8"
          :class="iconColorClass"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>

        <!-- Confirmed -->
        <svg
          v-else-if="status === 'confirmed'"
          class="h-8 w-8"
          :class="iconColorClass"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>

        <!-- Failed -->
        <svg
          v-else-if="status === 'failed'"
          class="h-8 w-8"
          :class="iconColorClass"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>

        <!-- Unknown -->
        <svg
          v-else
          class="h-8 w-8"
          :class="iconColorClass"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>

      <!-- Status Content -->
      <div class="status-content">
        <h3 class="status-title">{{ statusTitle }}</h3>
        <p class="status-message">{{ statusMessage }}</p>

        <!-- Transaction Hash -->
        <div class="transaction-info">
          <div class="info-row">
            <span class="info-label">Transaction Hash:</span>
            <div class="hash-container">
              <code class="hash-value" :title="transactionHash">
                {{ displayHash }}
              </code>
              <button
                v-if="showCopyButton"
                type="button"
                @click="copyHash"
                class="btn btn-sm btn-ghost"
                :aria-label="`Copy transaction hash ${transactionHash}`"
                title="Copy hash"
              >
                <svg
                  v-if="!copied"
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-4 h-4 text-green-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Explorer Link -->
          <div v-if="explorerUrl" class="info-row">
            <a
              :href="explorerUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="explorer-link"
              :aria-label="`View transaction ${transactionHash} on block explorer`"
            >
              <span>View on Explorer</span>
              <svg
                class="w-4 h-4 ml-1"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                />
              </svg>
            </a>
          </div>

          <!-- Additional Info -->
          <div v-if="blockNumber" class="info-row">
            <span class="info-label">Block:</span>
            <span class="info-value">{{ blockNumber }}</span>
          </div>

          <div v-if="confirmations !== undefined && confirmations > 0" class="info-row">
            <span class="info-label">Confirmations:</span>
            <span class="info-value">{{ confirmations }}</span>
          </div>

          <div v-if="timestamp" class="info-row">
            <span class="info-label">Time:</span>
            <span class="info-value">{{ formattedTimestamp }}</span>
          </div>
        </div>

        <!-- Progress Indicator -->
        <div v-if="showProgress && (status === 'pending' || status === 'submitted')" class="progress-container">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
          </div>
          <p class="progress-text">{{ progressText }}</p>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-display" role="alert">
          <svg
            class="w-5 h-5 inline-block mr-2"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          {{ errorMessage }}
        </div>

        <!-- Actions -->
        <div v-if="showRefreshButton && (status === 'pending' || status === 'submitted')" class="actions">
          <button
            type="button"
            @click="handleRefresh"
            :disabled="isRefreshing"
            class="btn btn-secondary"
            :class="{ 'opacity-50 cursor-not-allowed': isRefreshing }"
          >
            <svg
              class="w-4 h-4 mr-2"
              :class="{ 'animate-spin': isRefreshing }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            {{ isRefreshing ? 'Refreshing...' : 'Refresh Status' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <p class="text-gray-500">{{ emptyMessage }}</p>
    </div>

    <!-- Slot for additional content -->
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { queryTransactionStatus } from '../midnight/client';
import type { TransactionStatus as TxStatus, TransactionInfo } from '../midnight/types';

export interface TransactionStatusProps {
  /** Transaction hash to track */
  transactionHash?: string;
  /** Explorer base URL (hash will be appended) */
  explorerBaseUrl?: string;
  /** Auto-poll for status updates */
  autoPoll?: boolean;
  /** Polling interval in milliseconds */
  pollInterval?: number;
  /** Maximum number of poll attempts */
  maxPollAttempts?: number;
  /** Show copy button for hash */
  showCopyButton?: boolean;
  /** Show refresh button */
  showRefreshButton?: boolean;
  /** Show progress indicator */
  showProgress?: boolean;
  /** Empty state message */
  emptyMessage?: string;
  /** Number of characters to show from hash (when truncated) */
  hashTruncateLength?: number;
}

const props = withDefaults(defineProps<TransactionStatusProps>(), {
  transactionHash: '',
  explorerBaseUrl: '',
  autoPoll: true,
  pollInterval: 3000,
  maxPollAttempts: 30,
  showCopyButton: true,
  showRefreshButton: true,
  showProgress: true,
  emptyMessage: 'No transaction to display',
  hashTruncateLength: 10,
});

export interface TransactionStatusEmits {
  (e: 'statusChange', status: TxStatus, info: TransactionInfo): void;
  (e: 'confirmed', info: TransactionInfo): void;
  (e: 'failed', info: TransactionInfo): void;
}

const emit = defineEmits<TransactionStatusEmits>();

// Local state
const status = ref<TxStatus>('unknown');
const blockNumber = ref<number | undefined>();
const confirmations = ref<number | undefined>();
const timestamp = ref<number | undefined>();
const errorMessage = ref<string | undefined>();
const copied = ref(false);
const isRefreshing = ref(false);
const pollAttempts = ref(0);
let pollTimer: ReturnType<typeof setInterval> | null = null;

// Computed
const displayHash = computed(() => {
  if (!props.transactionHash) return '';
  const len = props.hashTruncateLength;
  return `${props.transactionHash.slice(0, len)}...${props.transactionHash.slice(-len)}`;
});

const explorerUrl = computed(() => {
  if (!props.explorerBaseUrl || !props.transactionHash) return '';
  return `${props.explorerBaseUrl}/tx/${props.transactionHash}`;
});

const statusClass = computed(() => {
  return {
    'status-pending': status.value === 'pending' || status.value === 'submitted',
    'status-confirmed': status.value === 'confirmed',
    'status-failed': status.value === 'failed',
    'status-unknown': status.value === 'unknown',
  };
});

const iconColorClass = computed(() => {
  switch (status.value) {
    case 'pending':
    case 'submitted':
      return 'text-blue-500';
    case 'confirmed':
      return 'text-green-500';
    case 'failed':
      return 'text-red-500';
    default:
      return 'text-gray-500';
  }
});

const statusTitle = computed(() => {
  switch (status.value) {
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
});

const statusMessage = computed(() => {
  switch (status.value) {
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
});

const formattedTimestamp = computed(() => {
  if (!timestamp.value) return '';
  return new Date(timestamp.value * 1000).toLocaleString();
});

const progressPercent = computed(() => {
  if (status.value === 'confirmed') return 100;
  if (status.value === 'failed') return 0;
  return Math.min((pollAttempts.value / props.maxPollAttempts) * 100, 95);
});

const progressText = computed(() => {
  return `Checking status... (${pollAttempts.value}/${props.maxPollAttempts})`;
});

// Methods
const copyHash = async () => {
  if (!props.transactionHash) return;

  try {
    await navigator.clipboard.writeText(props.transactionHash);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Failed to copy hash:', err);
  }
};

const fetchStatus = async () => {
  if (!props.transactionHash) return;

  try {
    isRefreshing.value = true;
    const info = await queryTransactionStatus(props.transactionHash);

    status.value = info.status;
    blockNumber.value = info.blockNumber;
    timestamp.value = info.timestamp;
    errorMessage.value = info.error;

    emit('statusChange', info.status, info);

    if (info.status === 'confirmed') {
      stopPolling();
      emit('confirmed', info);
    } else if (info.status === 'failed') {
      stopPolling();
      emit('failed', info);
    }

    pollAttempts.value++;

    if (pollAttempts.value >= props.maxPollAttempts) {
      stopPolling();
    }
  } catch (err) {
    console.error('Failed to fetch transaction status:', err);
    errorMessage.value = err instanceof Error ? err.message : 'Failed to fetch status';
  } finally {
    isRefreshing.value = false;
  }
};

const handleRefresh = () => {
  fetchStatus();
};

const startPolling = () => {
  if (!props.autoPoll || pollTimer) return;

  pollAttempts.value = 0;
  pollTimer = setInterval(() => {
    fetchStatus();
  }, props.pollInterval);

  // Fetch immediately
  fetchStatus();
};

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

// Watch for transaction hash changes
watch(() => props.transactionHash, (newHash) => {
  if (newHash) {
    stopPolling();
    status.value = 'pending';
    pollAttempts.value = 0;
    startPolling();
  } else {
    stopPolling();
    status.value = 'unknown';
  }
}, { immediate: true });

// Lifecycle
onMounted(() => {
  if (props.transactionHash && props.autoPoll) {
    startPolling();
  }
});

onUnmounted(() => {
  stopPolling();
});
</script>

<style scoped>
.transaction-status {
  @apply w-full max-w-2xl mx-auto;
}

.status-container {
  @apply border rounded-lg p-6;
}

.status-pending {
  @apply border-blue-200 bg-blue-50;
}

.status-confirmed {
  @apply border-green-200 bg-green-50;
}

.status-failed {
  @apply border-red-200 bg-red-50;
}

.status-unknown {
  @apply border-gray-200 bg-gray-50;
}

.status-icon {
  @apply flex justify-center mb-4;
}

.status-content {
  @apply space-y-4;
}

.status-title {
  @apply text-xl font-bold text-gray-900 text-center;
}

.status-message {
  @apply text-gray-600 text-center;
}

.transaction-info {
  @apply bg-white rounded-lg p-4 space-y-2;
}

.info-row {
  @apply flex items-center justify-between;
}

.info-label {
  @apply text-sm font-medium text-gray-700;
}

.info-value {
  @apply text-sm text-gray-900;
}

.hash-container {
  @apply flex items-center space-x-2;
}

.hash-value {
  @apply bg-gray-100 px-2 py-1 rounded border border-gray-300 text-xs font-mono;
}

.explorer-link {
  @apply inline-flex items-center text-sm text-blue-600 hover:text-blue-800 hover:underline;
}

.progress-container {
  @apply mt-4;
}

.progress-bar {
  @apply w-full bg-gray-200 rounded-full h-2 overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-500 transition-all duration-300;
}

.progress-text {
  @apply text-xs text-gray-600 mt-2 text-center;
}

.error-display {
  @apply px-4 py-3 bg-red-100 text-red-800 border border-red-300 rounded-lg text-sm;
}

.actions {
  @apply flex justify-center mt-4;
}

.btn {
  @apply inline-flex items-center px-4 py-2 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-500;
}

.btn-ghost {
  @apply bg-transparent hover:bg-gray-100 text-gray-600 px-2 py-1;
}

.btn-sm {
  @apply px-3 py-1 text-sm;
}

.empty-state {
  @apply text-center py-8;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
