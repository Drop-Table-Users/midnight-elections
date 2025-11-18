<template>
  <div class="wallet-connect">
    <!-- Not Available State -->
    <div v-if="!isAvailable" class="wallet-not-available">
      <div class="alert alert-warning" role="alert">
        <svg
          class="w-5 h-5 inline-block mr-2"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
        <span>{{ walletNotAvailableMessage }}</span>
      </div>
    </div>

    <!-- Wallet Actions -->
    <div v-else class="wallet-actions">
      <!-- Disconnected State -->
      <div v-if="!isConnected" class="wallet-disconnected">
        <button
          type="button"
          @click="handleConnect"
          :disabled="isConnecting"
          class="btn btn-primary"
          :class="{ 'opacity-50 cursor-not-allowed': isConnecting }"
          :aria-busy="isConnecting"
        >
          <span v-if="isConnecting" class="inline-flex items-center">
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5"
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
            Connecting...
          </span>
          <span v-else>
            <svg
              class="w-5 h-5 inline-block mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
            {{ connectButtonText }}
          </span>
        </button>
      </div>

      <!-- Connected State -->
      <div v-else class="wallet-connected">
        <div class="wallet-info" :class="displayMode === 'compact' ? 'compact' : 'full'">
          <!-- Address Display -->
          <div class="wallet-address">
            <span class="label" v-if="showLabels">Address:</span>
            <code class="address-value" :title="address || undefined">
              {{ displayAddress }}
            </code>
            <button
              v-if="showCopyButton"
              type="button"
              @click="copyAddress"
              class="btn btn-sm btn-ghost"
              :aria-label="`Copy address ${address}`"
              title="Copy address"
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

          <!-- Network Display -->
          <div v-if="showNetwork && network" class="wallet-network">
            <span class="label" v-if="showLabels">Network:</span>
            <span class="network-badge">
              <span class="network-indicator"></span>
              {{ network }}
            </span>
          </div>

          <!-- Disconnect Button -->
          <button
            v-if="showDisconnectButton"
            type="button"
            @click="handleDisconnect"
            class="btn btn-sm btn-secondary"
            aria-label="Disconnect wallet"
          >
            {{ disconnectButtonText }}
          </button>
        </div>
      </div>

      <!-- Error Display -->
      <div v-if="error" class="wallet-error" role="alert">
        <div class="alert alert-error">
          <svg
            class="w-5 h-5 inline-block mr-2"
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
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useMidnightWallet } from '../composables/useMidnightWallet';

export interface WalletConnectProps {
  /** Text for connect button */
  connectButtonText?: string;
  /** Text for disconnect button */
  disconnectButtonText?: string;
  /** Message when wallet is not available */
  walletNotAvailableMessage?: string;
  /** Show network indicator */
  showNetwork?: boolean;
  /** Show disconnect button */
  showDisconnectButton?: boolean;
  /** Show copy address button */
  showCopyButton?: boolean;
  /** Show labels for address/network */
  showLabels?: boolean;
  /** Display mode: 'full' or 'compact' */
  displayMode?: 'full' | 'compact';
  /** Number of characters to show from address (when truncated) */
  addressTruncateLength?: number;
}

const props = withDefaults(defineProps<WalletConnectProps>(), {
  connectButtonText: 'Connect Wallet',
  disconnectButtonText: 'Disconnect',
  walletNotAvailableMessage: 'Midnight wallet not detected. Please install Lace or another compatible wallet.',
  showNetwork: true,
  showDisconnectButton: true,
  showCopyButton: true,
  showLabels: true,
  displayMode: 'full',
  addressTruncateLength: 8,
});

export interface WalletConnectEmits {
  (e: 'connected', address: string): void;
  (e: 'disconnected'): void;
  (e: 'error', error: string): void;
}

const emit = defineEmits<WalletConnectEmits>();

// Use wallet composable
const {
  isAvailable,
  isConnected,
  isConnecting,
  address,
  network,
  error,
  connect,
  disconnect,
} = useMidnightWallet();

// Local state
const copied = ref(false);

// Computed
const displayAddress = computed(() => {
  if (!address.value) return '';

  if (props.displayMode === 'compact' && props.addressTruncateLength > 0) {
    const len = props.addressTruncateLength;
    return `${address.value.slice(0, len)}...${address.value.slice(-len)}`;
  }

  return address.value;
});

// Methods
const handleConnect = async () => {
  try {
    await connect();
    if (address.value) {
      emit('connected', address.value);
    }
  } catch (err) {
    const errorMessage = err instanceof Error ? err.message : 'Connection failed';
    emit('error', errorMessage);
  }
};

const handleDisconnect = () => {
  disconnect();
  emit('disconnected');
};

const copyAddress = async () => {
  if (!address.value) return;

  try {
    await navigator.clipboard.writeText(address.value);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Failed to copy address:', err);
  }
};

// Watch for errors
watch(error, (newError) => {
  if (newError) {
    emit('error', newError);
  }
});
</script>

<style scoped>
.wallet-connect {
  @apply w-full;
}

.wallet-not-available {
  @apply mb-4;
}

.alert {
  @apply px-4 py-3 rounded-lg;
}

.alert-warning {
  @apply bg-yellow-50 text-yellow-800 border border-yellow-200;
}

.alert-error {
  @apply bg-red-50 text-red-800 border border-red-200;
}

.btn {
  @apply px-4 py-2 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-500;
}

.btn-ghost {
  @apply bg-transparent hover:bg-gray-100 text-gray-600;
}

.btn-sm {
  @apply px-3 py-1 text-sm;
}

.wallet-info {
  @apply bg-gray-50 rounded-lg p-4 space-y-3;
}

.wallet-info.compact {
  @apply flex items-center space-x-3 space-y-0;
}

.wallet-address {
  @apply flex items-center space-x-2;
}

.label {
  @apply text-sm font-medium text-gray-700;
}

.address-value {
  @apply bg-white px-3 py-1 rounded border border-gray-300 text-sm font-mono;
}

.wallet-network {
  @apply flex items-center space-x-2;
}

.network-badge {
  @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800;
}

.network-indicator {
  @apply w-2 h-2 rounded-full bg-green-500 mr-2;
}

.wallet-error {
  @apply mt-3;
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
