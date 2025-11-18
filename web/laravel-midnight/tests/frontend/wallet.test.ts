/**
 * Tests for wallet.ts module
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getInjectedWallet,
  isWalletAvailable,
  connectWallet,
  getWalletState,
  getServiceUris,
  disconnectWallet,
  isWalletConnected,
  getConnectionState,
  addEventListener,
  removeEventListener,
  clearEventListeners,
  waitForWallet,
  getCachedWalletApi,
} from '@/midnight/wallet';
import {
  WalletNotFoundError,
  WalletConnectionError,
  WalletStateError,
  ServiceUriError,
} from '@/midnight/errors';
import {
  setupWindowMidnightMock,
  clearWindowMidnightMock,
  createMockInjectedWallet,
  createRejectingWallet,
  createLockedWallet,
  createWalletWithoutServiceUri,
  createWalletWithIncompleteServiceUri,
  mockWalletState,
  mockServiceUriConfig,
} from './mocks/wallet.mock';

describe('wallet module', () => {
  beforeEach(() => {
    clearWindowMidnightMock();
    disconnectWallet();
    clearEventListeners();
  });

  afterEach(() => {
    clearWindowMidnightMock();
    clearEventListeners();
  });

  describe('getInjectedWallet', () => {
    it('should return wallet when available', () => {
      setupWindowMidnightMock();
      const wallet = getInjectedWallet();
      expect(wallet).toBeDefined();
      expect(wallet.name).toBe('mnLace');
    });

    it('should throw WalletNotFoundError when window.midnight is undefined', () => {
      expect(() => getInjectedWallet()).toThrow(WalletNotFoundError);
      expect(() => getInjectedWallet()).toThrow('not installed');
    });

    it('should throw WalletNotFoundError when specified wallet not found', () => {
      setupWindowMidnightMock();
      expect(() => getInjectedWallet('nonexistent')).toThrow(WalletNotFoundError);
    });

    it('should support custom wallet names', () => {
      const customWallet = createMockInjectedWallet();
      // @ts-ignore
      global.window = global.window || {};
      // @ts-ignore
      global.window.midnight = {
        customWallet,
      };

      const wallet = getInjectedWallet('customWallet');
      expect(wallet).toBe(customWallet);
    });
  });

  describe('isWalletAvailable', () => {
    it('should return true when wallet is available', () => {
      setupWindowMidnightMock();
      expect(isWalletAvailable()).toBe(true);
    });

    it('should return false when wallet is not available', () => {
      expect(isWalletAvailable()).toBe(false);
    });

    it('should return false when wrong wallet name specified', () => {
      setupWindowMidnightMock();
      expect(isWalletAvailable('nonexistent')).toBe(false);
    });
  });

  describe('connectWallet', () => {
    it('should connect to wallet and return API', async () => {
      setupWindowMidnightMock();
      const api = await connectWallet();

      expect(api).toBeDefined();
      expect(isWalletConnected()).toBe(true);
      expect(getConnectionState()).toBe('connected');
    });

    it('should return cached API on subsequent calls', async () => {
      setupWindowMidnightMock();
      const api1 = await connectWallet();
      const api2 = await connectWallet();

      expect(api1).toBe(api2);
    });

    it('should force reconnect when forceReconnect is true', async () => {
      const mockWallet = createMockInjectedWallet();
      setupWindowMidnightMock(mockWallet);

      await connectWallet();
      expect(mockWallet.enable).toHaveBeenCalledTimes(1);

      await connectWallet('mnLace', true);
      expect(mockWallet.enable).toHaveBeenCalledTimes(2);
    });

    it('should throw error when wallet not available', async () => {
      // Will throw either WalletNotFoundError or WalletConnectionError depending on where it fails
      await expect(connectWallet()).rejects.toThrow();
    });

    it('should handle user rejection', async () => {
      const rejectingWallet = createRejectingWallet();
      setupWindowMidnightMock(rejectingWallet);

      await expect(connectWallet()).rejects.toThrow(WalletConnectionError);
      await expect(connectWallet()).rejects.toThrow('User rejected');
      expect(getConnectionState()).toBe('error');
    });

    it('should handle locked wallet', async () => {
      const lockedWallet = createLockedWallet();
      setupWindowMidnightMock(lockedWallet);

      await expect(connectWallet()).rejects.toThrow(WalletConnectionError);
      await expect(connectWallet()).rejects.toThrow('locked');
      expect(getConnectionState()).toBe('error');
    });

    it('should emit connect event on successful connection', async () => {
      setupWindowMidnightMock();
      const listener = vi.fn();
      addEventListener('connect', listener);

      await connectWallet();

      expect(listener).toHaveBeenCalledTimes(1);
      expect(listener).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'connect',
          data: { walletName: 'mnLace' },
        })
      );
    });

    it('should set connection state to connecting then connected', async () => {
      setupWindowMidnightMock();

      const connectPromise = connectWallet();
      // State should be connecting immediately
      expect(getConnectionState()).toBe('connecting');

      await connectPromise;
      expect(getConnectionState()).toBe('connected');
    });
  });

  describe('getWalletState', () => {
    it('should return wallet state', async () => {
      setupWindowMidnightMock();
      const state = await getWalletState();

      expect(state).toEqual(mockWalletState);
      expect(state.addresses).toBeDefined();
      expect(state.network).toBe('testnet');
    });

    it('should connect wallet if not connected', async () => {
      const mockWallet = createMockInjectedWallet();
      setupWindowMidnightMock(mockWallet);

      await getWalletState();

      expect(mockWallet.enable).toHaveBeenCalled();
    });

    it('should throw WalletStateError on failure', async () => {
      const mockWallet = createMockInjectedWallet({
        enable: vi.fn().mockResolvedValue({
          state: vi.fn().mockRejectedValue(new Error('State error')),
          submitTransaction: vi.fn(),
        }),
      });
      setupWindowMidnightMock(mockWallet);

      await expect(getWalletState()).rejects.toThrow(WalletStateError);
    });
  });

  describe('getServiceUris', () => {
    it('should return service URI configuration', async () => {
      setupWindowMidnightMock();
      const config = await getServiceUris();

      expect(config).toEqual(mockServiceUriConfig);
      expect(config.rpc).toBeDefined();
      expect(config.proof).toBeDefined();
    });

    it('should throw ServiceUriError when not supported', async () => {
      const wallet = createWalletWithoutServiceUri();
      setupWindowMidnightMock(wallet);

      await expect(getServiceUris()).rejects.toThrow(ServiceUriError);
      await expect(getServiceUris()).rejects.toThrow('not support');
    });

    it('should throw ServiceUriError when RPC is missing', async () => {
      const wallet = createWalletWithIncompleteServiceUri();
      setupWindowMidnightMock(wallet);

      await expect(getServiceUris()).rejects.toThrow(ServiceUriError);
      await expect(getServiceUris()).rejects.toThrow('RPC');
    });

    it('should throw WalletNotFoundError when wallet not available', async () => {
      await expect(getServiceUris()).rejects.toThrow(WalletNotFoundError);
    });
  });

  describe('disconnectWallet', () => {
    it('should clear cached API and emit disconnect event', async () => {
      setupWindowMidnightMock();
      const listener = vi.fn();
      addEventListener('disconnect', listener);

      await connectWallet();
      expect(isWalletConnected()).toBe(true);

      disconnectWallet();

      expect(isWalletConnected()).toBe(false);
      expect(getConnectionState()).toBe('disconnected');
      expect(getCachedWalletApi()).toBeNull();
      expect(listener).toHaveBeenCalledTimes(1);
    });

    it('should be safe to call when not connected', () => {
      expect(() => disconnectWallet()).not.toThrow();
      expect(isWalletConnected()).toBe(false);
    });
  });

  describe('isWalletConnected', () => {
    it('should return false when not connected', () => {
      expect(isWalletConnected()).toBe(false);
    });

    it('should return true when connected', async () => {
      setupWindowMidnightMock();
      await connectWallet();
      expect(isWalletConnected()).toBe(true);
    });

    it('should return false after disconnect', async () => {
      setupWindowMidnightMock();
      await connectWallet();
      disconnectWallet();
      expect(isWalletConnected()).toBe(false);
    });
  });

  describe('getConnectionState', () => {
    it('should return disconnected by default', () => {
      expect(getConnectionState()).toBe('disconnected');
    });

    it('should return connected after successful connection', async () => {
      setupWindowMidnightMock();
      await connectWallet();
      expect(getConnectionState()).toBe('connected');
    });

    it('should return error after connection failure', async () => {
      const rejectingWallet = createRejectingWallet();
      setupWindowMidnightMock(rejectingWallet);

      try {
        await connectWallet();
      } catch {
        // Ignore error
      }

      expect(getConnectionState()).toBe('error');
    });
  });

  describe('event listeners', () => {
    it('should add and trigger event listeners', async () => {
      setupWindowMidnightMock();
      const listener = vi.fn();

      addEventListener('connect', listener);
      await connectWallet();

      expect(listener).toHaveBeenCalledTimes(1);
      expect(listener).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'connect',
          timestamp: expect.any(Number),
        })
      );
    });

    it('should support multiple listeners for same event', async () => {
      setupWindowMidnightMock();
      const listener1 = vi.fn();
      const listener2 = vi.fn();

      addEventListener('connect', listener1);
      addEventListener('connect', listener2);
      await connectWallet();

      expect(listener1).toHaveBeenCalledTimes(1);
      expect(listener2).toHaveBeenCalledTimes(1);
    });

    it('should remove specific event listener', async () => {
      setupWindowMidnightMock();
      const listener = vi.fn();

      addEventListener('connect', listener);
      removeEventListener('connect', listener);
      await connectWallet();

      expect(listener).not.toHaveBeenCalled();
    });

    it('should clear all listeners for specific event type', async () => {
      setupWindowMidnightMock();
      const listener1 = vi.fn();
      const listener2 = vi.fn();

      addEventListener('connect', listener1);
      addEventListener('connect', listener2);
      clearEventListeners('connect');
      await connectWallet();

      expect(listener1).not.toHaveBeenCalled();
      expect(listener2).not.toHaveBeenCalled();
    });

    it('should clear all listeners when no event type specified', async () => {
      setupWindowMidnightMock();
      const connectListener = vi.fn();
      const disconnectListener = vi.fn();

      addEventListener('connect', connectListener);
      addEventListener('disconnect', disconnectListener);
      clearEventListeners();

      await connectWallet();
      disconnectWallet();

      expect(connectListener).not.toHaveBeenCalled();
      expect(disconnectListener).not.toHaveBeenCalled();
    });

    it('should handle errors in event listeners gracefully', async () => {
      setupWindowMidnightMock();
      const errorListener = vi.fn(() => {
        throw new Error('Listener error');
      });
      const goodListener = vi.fn();

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      addEventListener('connect', errorListener);
      addEventListener('connect', goodListener);

      await connectWallet();

      expect(errorListener).toHaveBeenCalledTimes(1);
      expect(goodListener).toHaveBeenCalledTimes(1);
      expect(consoleSpy).toHaveBeenCalled();

      consoleSpy.mockRestore();
    });
  });

  describe('waitForWallet', () => {
    it('should resolve immediately if wallet is available', async () => {
      setupWindowMidnightMock();

      const wallet = await waitForWallet('mnLace', 1000);

      expect(wallet).toBeDefined();
      expect(wallet.name).toBe('mnLace');
    });

    it('should reject with timeout if wallet not found', async () => {
      await expect(waitForWallet('mnLace', 500)).rejects.toThrow(WalletNotFoundError);
    });

    it('should detect wallet that becomes available', async () => {
      const detectPromise = waitForWallet('mnLace', 2000);

      // Simulate wallet becoming available after 200ms
      setTimeout(() => {
        setupWindowMidnightMock();
      }, 200);

      const wallet = await detectPromise;
      expect(wallet).toBeDefined();
    });

    it('should use default timeout of 5000ms', async () => {
      const startTime = Date.now();

      try {
        await waitForWallet('mnLace');
      } catch {
        const duration = Date.now() - startTime;
        // Should be around 5000ms (with some tolerance)
        expect(duration).toBeGreaterThan(4500);
        expect(duration).toBeLessThan(5500);
      }
    }, 6000);
  });

  describe('getCachedWalletApi', () => {
    it('should return null when not connected', () => {
      expect(getCachedWalletApi()).toBeNull();
    });

    it('should return API after connection', async () => {
      setupWindowMidnightMock();
      await connectWallet();

      const api = getCachedWalletApi();
      expect(api).toBeDefined();
      expect(api).not.toBeNull();
    });

    it('should return null after disconnect', async () => {
      setupWindowMidnightMock();
      await connectWallet();
      disconnectWallet();

      expect(getCachedWalletApi()).toBeNull();
    });
  });

  describe('edge cases', () => {
    it('should handle wallet enable returning non-standard errors', async () => {
      const wallet = createMockInjectedWallet({
        enable: vi.fn().mockRejectedValue('String error'),
      });
      setupWindowMidnightMock(wallet);

      await expect(connectWallet()).rejects.toThrow(WalletConnectionError);
      await expect(connectWallet()).rejects.toThrow('Unknown error');
    });

    it('should handle concurrent connection attempts', async () => {
      setupWindowMidnightMock();

      const [api1, api2, api3] = await Promise.all([
        connectWallet(),
        connectWallet(),
        connectWallet(),
      ]);

      // All should return the same cached instance
      expect(api1).toBe(api2);
      expect(api2).toBe(api3);
    });

    it('should clear cache on connection error', async () => {
      const rejectingWallet = createRejectingWallet();
      setupWindowMidnightMock(rejectingWallet);

      try {
        await connectWallet();
      } catch {
        // Ignore error
      }

      expect(getCachedWalletApi()).toBeNull();
    });
  });
});
