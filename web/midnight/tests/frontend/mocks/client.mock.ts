/**
 * Mock implementations for client functionality
 */

import { vi } from 'vitest';

/**
 * Create a mock Midnight client
 */
export function createMockMidnightClient(overrides?: any) {
  return {
    rpcUrl: 'https://rpc.testnet.midnight.network',
    proofServerUrl: 'https://proof.testnet.midnight.network',
    network: 'testnet',
    timeout: 30000,
    initialized: true,
    buildContractCall: vi.fn().mockResolvedValue({
      contractAddress: '0xcontract',
      entrypoint: 'cast_vote',
      args: { candidateId: 1 },
    }),
    serializeTx: vi.fn().mockResolvedValue(new Uint8Array([1, 2, 3, 4])),
    getTransaction: vi.fn().mockResolvedValue({
      hash: '0xtxhash',
      status: 'confirmed',
      blockNumber: 12345,
      timestamp: Date.now(),
    }),
    ...overrides,
  };
}

/**
 * Mock fetch for RPC calls
 */
export function mockRpcFetch(response: any = {}) {
  return vi.fn().mockResolvedValue({
    ok: true,
    json: vi.fn().mockResolvedValue({
      jsonrpc: '2.0',
      id: 1,
      result: response,
    }),
  });
}

/**
 * Mock fetch for RPC calls with error
 */
export function mockRpcFetchError(statusText: string = 'Internal Server Error') {
  return vi.fn().mockResolvedValue({
    ok: false,
    statusText,
  });
}
