/**
 * Mock implementations for wallet functionality
 */

import { vi } from 'vitest';
import type {
  MidnightWalletApi,
  MidnightInjectedWallet,
  WalletState,
  ServiceUriConfig,
} from '@/midnight/types';

/**
 * Mock wallet state
 */
export const mockWalletState: WalletState = {
  addresses: ['0x1234567890abcdef1234567890abcdef12345678'],
  publicKeys: ['0xabcdef1234567890abcdef1234567890abcdef12'],
  network: 'testnet',
  balance: '1000000000',
};

/**
 * Mock service URI configuration
 */
export const mockServiceUriConfig: ServiceUriConfig = {
  rpc: 'https://rpc.testnet.midnight.network',
  proof: 'https://proof.testnet.midnight.network',
  indexer: 'https://indexer.testnet.midnight.network',
};

/**
 * Create a mock wallet API
 */
export function createMockWalletApi(overrides?: Partial<MidnightWalletApi>): MidnightWalletApi {
  return {
    state: vi.fn().mockResolvedValue(mockWalletState),
    submitTransaction: vi.fn().mockResolvedValue('0xmocktxhash123456789'),
    signMessage: vi.fn().mockResolvedValue('0xmocksignature'),
    getBalance: vi.fn().mockResolvedValue('1000000000'),
    ...overrides,
  };
}

/**
 * Create a mock injected wallet
 */
export function createMockInjectedWallet(
  overrides?: Partial<MidnightInjectedWallet>
): MidnightInjectedWallet {
  return {
    enable: vi.fn().mockResolvedValue(createMockWalletApi()),
    serviceUriConfig: vi.fn().mockResolvedValue(mockServiceUriConfig),
    isEnabled: vi.fn().mockResolvedValue(true),
    name: 'mnLace',
    version: '1.0.0',
    ...overrides,
  };
}

/**
 * Setup window.midnight mock
 */
export function setupWindowMidnightMock(wallet?: MidnightInjectedWallet): void {
  const mockWallet = wallet || createMockInjectedWallet();

  // @ts-ignore
  global.window = global.window || {};
  // @ts-ignore
  global.window.midnight = {
    mnLace: mockWallet,
  };
}

/**
 * Clear window.midnight mock
 */
export function clearWindowMidnightMock(): void {
  // @ts-ignore
  if (global.window?.midnight) {
    // @ts-ignore
    delete global.window.midnight;
  }
}

/**
 * Create a mock wallet that throws user rejection error
 */
export function createRejectingWallet(): MidnightInjectedWallet {
  return createMockInjectedWallet({
    enable: vi.fn().mockRejectedValue(new Error('User rejected the request')),
  });
}

/**
 * Create a mock wallet that throws locked wallet error
 */
export function createLockedWallet(): MidnightInjectedWallet {
  return createMockInjectedWallet({
    enable: vi.fn().mockRejectedValue(new Error('Wallet is locked')),
  });
}

/**
 * Create a mock wallet without serviceUriConfig support
 */
export function createWalletWithoutServiceUri(): MidnightInjectedWallet {
  return createMockInjectedWallet({
    serviceUriConfig: undefined,
  });
}

/**
 * Create a mock wallet with incomplete service URI config
 */
export function createWalletWithIncompleteServiceUri(): MidnightInjectedWallet {
  return createMockInjectedWallet({
    serviceUriConfig: vi.fn().mockResolvedValue({
      // Missing RPC
      proof: 'https://proof.testnet.midnight.network',
    }),
  });
}
