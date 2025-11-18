// resources/js/composables/useMidnightWallet.ts
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { Ref } from 'vue';
import {
  isWalletAvailable,
  connectWallet as walletConnect,
  disconnectWallet as walletDisconnect,
  getWalletState,
  getWalletAddress,
  getNetworkInfo,
  isWalletConnected as checkWalletConnected,
  submitTransaction
} from '../midnight/wallet';
import type { WalletState, WalletConnectionState } from '../midnight/types';

export interface UseMidnightWalletReturn {
  // State
  isAvailable: Ref<boolean>;
  isConnected: Ref<boolean>;
  isConnecting: Ref<boolean>;
  connectionState: Ref<WalletConnectionState>;
  walletState: Ref<WalletState | null>;
  address: Ref<string | null>;
  network: Ref<string | null>;
  error: Ref<string | null>;

  // Methods
  connect: () => Promise<void>;
  disconnect: () => void;
  refreshState: () => Promise<void>;
  submit: (tx: Uint8Array | string) => Promise<string>;
}

/**
 * Vue composable for Midnight wallet integration
 *
 * Provides reactive state and methods for wallet connection,
 * state management, and transaction submission.
 *
 * @example
 * ```ts
 * const {
 *   isConnected,
 *   address,
 *   connect,
 *   disconnect,
 *   error
 * } = useMidnightWallet();
 *
 * // Connect to wallet
 * await connect();
 *
 * // Display address
 * console.log(address.value);
 * ```
 */
export function useMidnightWallet(): UseMidnightWalletReturn {
  // Reactive state
  const isAvailable = ref<boolean>(false);
  const isConnected = ref<boolean>(false);
  const isConnecting = ref<boolean>(false);
  const connectionState = ref<WalletConnectionState>('disconnected');
  const walletState = ref<WalletState | null>(null);
  const address = ref<string | null>(null);
  const network = ref<string | null>(null);
  const error = ref<string | null>(null);

  /**
   * Check if wallet is available on mount
   */
  const checkAvailability = () => {
    isAvailable.value = isWalletAvailable();
    if (isAvailable.value) {
      isConnected.value = checkWalletConnected();
      connectionState.value = isConnected.value ? 'connected' : 'disconnected';
    }
  };

  /**
   * Connect to the Midnight wallet
   */
  const connect = async () => {
    if (isConnecting.value || isConnected.value) {
      return;
    }

    error.value = null;
    isConnecting.value = true;
    connectionState.value = 'connecting';

    try {
      await walletConnect();
      await refreshState();

      isConnected.value = true;
      connectionState.value = 'connected';
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to connect to wallet';
      error.value = errorMessage;
      connectionState.value = 'error';
      isConnected.value = false;

      throw err;
    } finally {
      isConnecting.value = false;
    }
  };

  /**
   * Disconnect from the wallet
   */
  const disconnect = () => {
    walletDisconnect();

    isConnected.value = false;
    connectionState.value = 'disconnected';
    walletState.value = null;
    address.value = null;
    network.value = null;
    error.value = null;
  };

  /**
   * Refresh wallet state from the wallet
   */
  const refreshState = async () => {
    if (!isConnected.value) {
      throw new Error('Wallet not connected');
    }

    try {
      const state = await getWalletState(true);
      walletState.value = state;

      // Update derived values
      const addr = await getWalletAddress();
      address.value = addr;

      const net = await getNetworkInfo();
      network.value = net;

      error.value = null;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to refresh wallet state';
      error.value = errorMessage;
      throw err;
    }
  };

  /**
   * Submit a transaction through the wallet
   */
  const submit = async (tx: Uint8Array | string): Promise<string> => {
    if (!isConnected.value) {
      throw new Error('Wallet not connected');
    }

    try {
      error.value = null;
      const txHash = await submitTransaction(tx);
      return txHash;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Transaction submission failed';
      error.value = errorMessage;
      throw err;
    }
  };

  // Check availability on mount
  onMounted(() => {
    checkAvailability();
  });

  // Cleanup on unmount
  onUnmounted(() => {
    // Optional: disconnect on unmount if desired
    // disconnect();
  });

  return {
    // State
    isAvailable,
    isConnected,
    isConnecting,
    connectionState,
    walletState,
    address,
    network,
    error,

    // Methods
    connect,
    disconnect,
    refreshState,
    submit,
  };
}
