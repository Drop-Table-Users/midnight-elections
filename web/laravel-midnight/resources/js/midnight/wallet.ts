/**
 * Wallet connection and management module
 *
 * This module provides the core wallet integration functionality for connecting
 * to Midnight wallets (e.g., Lace Beta), managing connection state, and
 * providing wallet API access.
 *
 * @module midnight/wallet
 */

import type {
  MidnightInjectedWallet,
  MidnightWalletApi,
  MidnightWindow,
  WalletState,
  ServiceUriConfig,
  WalletConnectionState,
  WalletEvent,
  WalletEventListener,
  WalletEventType,
} from './types';

import {
  WalletNotFoundError,
  WalletConnectionError,
  WalletStateError,
  ServiceUriError,
} from './errors';

// Extend window with Midnight types
declare const window: MidnightWindow;

/**
 * Cached wallet API instance to avoid repeated connection requests
 */
let cachedApi: MidnightWalletApi | null = null;

/**
 * Current wallet connection state
 */
let connectionState: WalletConnectionState = 'disconnected';

/**
 * Event listeners registry
 */
const eventListeners: Map<WalletEventType, Set<WalletEventListener>> = new Map();

/**
 * Get the injected Midnight wallet from the browser window
 *
 * @param walletName - Name of the wallet to retrieve (default: 'mnLace')
 * @returns The injected wallet instance
 * @throws {WalletNotFoundError} If wallet is not found
 *
 * @example
 * ```typescript
 * try {
 *   const wallet = getInjectedWallet();
 *   console.log('Wallet found:', wallet.name);
 * } catch (error) {
 *   console.error('Wallet not installed');
 * }
 * ```
 */
export function getInjectedWallet(walletName: string = 'mnLace'): MidnightInjectedWallet {
  if (!window.midnight) {
    throw new WalletNotFoundError(walletName, {
      suggestion: 'window.midnight is not defined',
    });
  }

  const wallet = window.midnight[walletName];
  if (!wallet) {
    throw new WalletNotFoundError(walletName, {
      availableWallets: Object.keys(window.midnight),
    });
  }

  return wallet;
}

/**
 * Check if a Midnight wallet is available in the browser
 *
 * @param walletName - Name of the wallet to check (default: 'mnLace')
 * @returns True if wallet is detected
 *
 * @example
 * ```typescript
 * if (isWalletAvailable()) {
 *   // Show connect wallet button
 * } else {
 *   // Show install wallet prompt
 * }
 * ```
 */
export function isWalletAvailable(walletName: string = 'mnLace'): boolean {
  try {
    getInjectedWallet(walletName);
    return true;
  } catch {
    return false;
  }
}

/**
 * Connect to the Midnight wallet and request user permission
 *
 * This function will prompt the user to approve the connection if not already connected.
 * The wallet API instance is cached to avoid repeated permission requests.
 *
 * @param walletName - Name of the wallet to connect (default: 'mnLace')
 * @param forceReconnect - Force a new connection even if cached (default: false)
 * @returns Promise resolving to the wallet API instance
 * @throws {WalletNotFoundError} If wallet is not found
 * @throws {WalletConnectionError} If connection fails
 *
 * @example
 * ```typescript
 * async function handleConnect() {
 *   try {
 *     setConnectionState('connecting');
 *     const api = await connectWallet();
 *     setConnectionState('connected');
 *     const state = await api.state();
 *     console.log('Connected with addresses:', state.addresses);
 *   } catch (error) {
 *     setConnectionState('error');
 *     if (error instanceof WalletConnectionError) {
 *       alert(error.message);
 *     }
 *   }
 * }
 * ```
 */
export async function connectWallet(
  walletName: string = 'mnLace',
  forceReconnect: boolean = false
): Promise<MidnightWalletApi> {
  // Return cached API if available and not forcing reconnection
  if (cachedApi && !forceReconnect) {
    return cachedApi;
  }

  try {
    setConnectionState('connecting');
    const injected = getInjectedWallet(walletName);
    const api = await injected.enable();
    cachedApi = api;
    setConnectionState('connected');
    emitEvent('connect', { walletName });
    return api;
  } catch (error) {
    setConnectionState('error');
    cachedApi = null;

    // Handle common error scenarios
    if (error instanceof Error) {
      if (error.message.includes('reject')) {
        throw WalletConnectionError.userRejected();
      }
      if (error.message.includes('lock')) {
        throw WalletConnectionError.walletLocked();
      }
      throw new WalletConnectionError(error.message, { originalError: error });
    }

    throw new WalletConnectionError('Unknown error occurred during wallet connection');
  }
}

/**
 * Get the current wallet state (addresses, public keys, etc.)
 *
 * @returns Promise resolving to wallet state
 * @throws {WalletStateError} If state retrieval fails
 *
 * @example
 * ```typescript
 * const state = await getWalletState();
 * console.log('Wallet addresses:', state.addresses);
 * console.log('Network:', state.network);
 * ```
 */
export async function getWalletState(): Promise<WalletState> {
  try {
    const api = await connectWallet();
    const state = await api.state();
    return state;
  } catch (error) {
    if (error instanceof WalletConnectionError || error instanceof WalletNotFoundError) {
      throw error;
    }
    if (error instanceof Error) {
      throw new WalletStateError(error.message, { originalError: error });
    }
    throw new WalletStateError('Failed to retrieve wallet state');
  }
}

/**
 * Get service URIs (RPC, proof server, etc.) from the wallet
 *
 * @param walletName - Name of the wallet (default: 'mnLace')
 * @returns Promise resolving to service URI configuration
 * @throws {ServiceUriError} If service URIs are not available
 *
 * @example
 * ```typescript
 * const { rpc, proof } = await getServiceUris();
 * console.log('RPC endpoint:', rpc);
 * console.log('Proof server:', proof);
 * ```
 */
export async function getServiceUris(walletName: string = 'mnLace'): Promise<ServiceUriConfig> {
  try {
    const injected = getInjectedWallet(walletName);

    if (!injected.serviceUriConfig) {
      throw ServiceUriError.notSupported();
    }

    const cfg = await injected.serviceUriConfig();

    if (!cfg.rpc) {
      throw ServiceUriError.missingRpc();
    }

    return {
      rpc: cfg.rpc,
      proof: cfg.proof,
      indexer: cfg.indexer,
      ...cfg,
    };
  } catch (error) {
    if (error instanceof ServiceUriError || error instanceof WalletNotFoundError) {
      throw error;
    }
    if (error instanceof Error) {
      throw new ServiceUriError(error.message, { originalError: error });
    }
    throw new ServiceUriError('Failed to retrieve service URIs');
  }
}

/**
 * Disconnect from the wallet and clear cached state
 *
 * Note: This only clears the local cache. The actual wallet permission
 * may need to be revoked from the wallet extension UI.
 *
 * @example
 * ```typescript
 * function handleDisconnect() {
 *   disconnectWallet();
 *   setConnectionState('disconnected');
 * }
 * ```
 */
export function disconnectWallet(): void {
  cachedApi = null;
  setConnectionState('disconnected');
  emitEvent('disconnect', {});
}

/**
 * Check if wallet is currently connected
 *
 * @returns True if wallet is connected
 *
 * @example
 * ```typescript
 * if (isWalletConnected()) {
 *   // Show disconnect button
 * } else {
 *   // Show connect button
 * }
 * ```
 */
export function isWalletConnected(): boolean {
  return connectionState === 'connected' && cachedApi !== null;
}

/**
 * Get current wallet connection state
 *
 * @returns Current connection state
 *
 * @example
 * ```typescript
 * const state = getConnectionState();
 * if (state === 'connecting') {
 *   showLoadingSpinner();
 * }
 * ```
 */
export function getConnectionState(): WalletConnectionState {
  return connectionState;
}

/**
 * Add an event listener for wallet events
 *
 * @param eventType - Type of event to listen for
 * @param listener - Callback function to invoke when event occurs
 *
 * @example
 * ```typescript
 * addEventListener('connect', (event) => {
 *   console.log('Wallet connected:', event.data);
 * });
 *
 * addEventListener('disconnect', () => {
 *   console.log('Wallet disconnected');
 * });
 * ```
 */
export function addEventListener(eventType: WalletEventType, listener: WalletEventListener): void {
  if (!eventListeners.has(eventType)) {
    eventListeners.set(eventType, new Set());
  }
  eventListeners.get(eventType)!.add(listener);
}

/**
 * Remove an event listener
 *
 * @param eventType - Type of event
 * @param listener - Callback function to remove
 *
 * @example
 * ```typescript
 * const handler = (event) => console.log(event);
 * addEventListener('connect', handler);
 * // Later...
 * removeEventListener('connect', handler);
 * ```
 */
export function removeEventListener(eventType: WalletEventType, listener: WalletEventListener): void {
  const listeners = eventListeners.get(eventType);
  if (listeners) {
    listeners.delete(listener);
  }
}

/**
 * Remove all event listeners for a specific event type or all events
 *
 * @param eventType - Optional event type to clear (clears all if not provided)
 *
 * @example
 * ```typescript
 * // Clear all connect listeners
 * clearEventListeners('connect');
 *
 * // Clear all listeners
 * clearEventListeners();
 * ```
 */
export function clearEventListeners(eventType?: WalletEventType): void {
  if (eventType) {
    eventListeners.delete(eventType);
  } else {
    eventListeners.clear();
  }
}

/**
 * Set the connection state and emit event if changed
 * @param newState - New connection state
 * @internal
 */
function setConnectionState(newState: WalletConnectionState): void {
  if (connectionState !== newState) {
    connectionState = newState;
  }
}

/**
 * Emit a wallet event to all registered listeners
 * @param eventType - Type of event
 * @param data - Event data
 * @internal
 */
function emitEvent(eventType: WalletEventType, data?: unknown): void {
  const listeners = eventListeners.get(eventType);
  if (listeners && listeners.size > 0) {
    const event: WalletEvent = {
      type: eventType,
      data,
      timestamp: Date.now(),
    };

    listeners.forEach((listener) => {
      try {
        listener(event);
      } catch (error) {
        console.error(`Error in wallet event listener for ${eventType}:`, error);
      }
    });
  }
}

/**
 * Wait for wallet to become available (useful for detecting wallet installation)
 *
 * @param walletName - Name of the wallet to wait for
 * @param timeout - Maximum time to wait in milliseconds (default: 5000)
 * @returns Promise resolving when wallet is detected
 * @throws {WalletNotFoundError} If timeout is reached
 *
 * @example
 * ```typescript
 * try {
 *   await waitForWallet('mnLace', 3000);
 *   console.log('Wallet detected!');
 * } catch (error) {
 *   console.log('Wallet not found after 3 seconds');
 * }
 * ```
 */
export async function waitForWallet(
  walletName: string = 'mnLace',
  timeout: number = 5000
): Promise<MidnightInjectedWallet> {
  const startTime = Date.now();

  return new Promise((resolve, reject) => {
    const checkWallet = () => {
      if (isWalletAvailable(walletName)) {
        resolve(getInjectedWallet(walletName));
      } else if (Date.now() - startTime >= timeout) {
        reject(new WalletNotFoundError(walletName, { timeout }));
      } else {
        setTimeout(checkWallet, 100);
      }
    };

    checkWallet();
  });
}

/**
 * Get the cached wallet API instance without triggering a new connection
 *
 * @returns The cached wallet API or null if not connected
 *
 * @example
 * ```typescript
 * const api = getCachedWalletApi();
 * if (api) {
 *   const state = await api.state();
 * }
 * ```
 */
export function getCachedWalletApi(): MidnightWalletApi | null {
  return cachedApi;
}
