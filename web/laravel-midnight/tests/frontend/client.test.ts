/**
 * Tests for client.ts module
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  createMidnightClient,
  getMidnightClient,
  buildContractCall,
  submitTransaction,
  buildAndSubmitVoteTx,
  getTransactionStatus,
  waitForConfirmation,
  clearClientCache,
} from '@/midnight/client';
import {
  TransactionError,
  ContractCallError,
  ClientInitializationError,
} from '@/midnight/errors';
import {
  setupWindowMidnightMock,
  clearWindowMidnightMock,
  createMockWalletApi,
} from './mocks/wallet.mock';
import { createMockMidnightClient, mockRpcFetch, mockRpcFetchError } from './mocks/client.mock';

// Mock the wallet module
vi.mock('@/midnight/wallet', () => {
  const mockApi = {
    state: vi.fn().mockResolvedValue({ addresses: ['0x123'], network: 'testnet' }),
    submitTransaction: vi.fn().mockResolvedValue('0xmocktxhash123456789'),
    signMessage: vi.fn().mockResolvedValue('0xmocksignature'),
    getBalance: vi.fn().mockResolvedValue('1000000000'),
  };

  return {
    getServiceUris: vi.fn().mockResolvedValue({
      rpc: 'https://rpc.testnet.midnight.network',
      proof: 'https://proof.testnet.midnight.network',
    }),
    connectWallet: vi.fn().mockResolvedValue(mockApi),
    getCachedWalletApi: vi.fn().mockReturnValue(mockApi),
  };
});

describe('client module', () => {
  beforeEach(() => {
    clearClientCache();
    vi.clearAllMocks();
    setupWindowMidnightMock();
  });

  afterEach(() => {
    clearWindowMidnightMock();
    clearClientCache();
  });

  describe('createMidnightClient', () => {
    it('should create a client with valid configuration', async () => {
      const client = await createMidnightClient({
        rpcUrl: 'https://rpc.testnet.midnight.network',
        proofServerUrl: 'https://proof.testnet.midnight.network',
      });

      expect(client).toBeDefined();
      expect(client.rpcUrl).toBe('https://rpc.testnet.midnight.network');
      expect(client.proofServerUrl).toBe('https://proof.testnet.midnight.network');
      expect(client.initialized).toBe(true);
    });

    it('should include optional configuration', async () => {
      const client = await createMidnightClient({
        rpcUrl: 'https://rpc.testnet.midnight.network',
        network: 'testnet',
        timeout: 60000,
      });

      expect(client.network).toBe('testnet');
      expect(client.timeout).toBe(60000);
    });

    it('should use default timeout of 30000ms', async () => {
      const client = await createMidnightClient({
        rpcUrl: 'https://rpc.testnet.midnight.network',
      });

      expect(client.timeout).toBe(30000);
    });

    it('should throw ClientInitializationError for invalid RPC URL', async () => {
      await expect(
        createMidnightClient({
          rpcUrl: '',
        })
      ).rejects.toThrow(ClientInitializationError);
    });

    it('should throw ClientInitializationError for non-string RPC URL', async () => {
      await expect(
        createMidnightClient({
          rpcUrl: null as any,
        })
      ).rejects.toThrow(ClientInitializationError);
    });
  });

  describe('getMidnightClient', () => {
    it('should create and cache client instance', async () => {
      const client1 = await getMidnightClient();
      const client2 = await getMidnightClient();

      expect(client1).toBe(client2);
    });

    it('should create new client when forceNew is true', async () => {
      const client1 = await getMidnightClient();
      const client2 = await getMidnightClient(true);

      expect(client1).not.toBe(client2);
    });

    it('should use service URIs from wallet', async () => {
      const client = await getMidnightClient();

      expect(client.rpcUrl).toBe('https://rpc.testnet.midnight.network');
      expect(client.proofServerUrl).toBe('https://proof.testnet.midnight.network');
    });

    it('should recreate client if RPC URL changes', async () => {
      const { getServiceUris } = await import('@/midnight/wallet');

      const client1 = await getMidnightClient();

      // Change RPC URL
      vi.mocked(getServiceUris).mockResolvedValueOnce({
        rpc: 'https://rpc.mainnet.midnight.network',
        proof: 'https://proof.mainnet.midnight.network',
      });

      const client2 = await getMidnightClient();

      expect(client1).not.toBe(client2);
      expect(client2.rpcUrl).toBe('https://rpc.mainnet.midnight.network');
    });
  });

  describe('buildContractCall', () => {
    it('should build contract call with valid parameters', async () => {
      const tx = await buildContractCall({
        contractAddress: '0x1234567890abcdef',
        entrypoint: 'cast_vote',
        publicArgs: { candidateId: 1 },
        privateArgs: { ballot: new Uint8Array([1, 2, 3]) },
      });

      expect(tx).toBeInstanceOf(Uint8Array);
    });

    it('should throw ContractCallError for invalid address', async () => {
      await expect(
        buildContractCall({
          contractAddress: '',
          entrypoint: 'cast_vote',
        })
      ).rejects.toThrow(ContractCallError);
    });

    it('should throw ContractCallError for invalid entrypoint', async () => {
      await expect(
        buildContractCall({
          contractAddress: '0x1234567890abcdef',
          entrypoint: '',
        })
      ).rejects.toThrow(ContractCallError);
    });

    it('should throw ContractCallError for non-string entrypoint', async () => {
      await expect(
        buildContractCall({
          contractAddress: '0x1234567890abcdef',
          entrypoint: null as any,
        })
      ).rejects.toThrow(ContractCallError);
    });

    it('should handle public args only', async () => {
      const tx = await buildContractCall({
        contractAddress: '0x1234567890abcdef',
        entrypoint: 'cast_vote',
        publicArgs: { candidateId: 1 },
      });

      expect(tx).toBeDefined();
    });

    it('should handle private args only', async () => {
      const tx = await buildContractCall({
        contractAddress: '0x1234567890abcdef',
        entrypoint: 'cast_vote',
        privateArgs: { ballot: new Uint8Array([1, 2, 3]) },
      });

      expect(tx).toBeDefined();
    });

    it('should handle no args', async () => {
      const tx = await buildContractCall({
        contractAddress: '0x1234567890abcdef',
        entrypoint: 'get_results',
      });

      expect(tx).toBeDefined();
    });
  });

  describe('submitTransaction', () => {
    it('should submit transaction successfully', async () => {
      const tx = new Uint8Array([1, 2, 3, 4]);
      const txHash = await submitTransaction(tx);

      expect(txHash).toBe('0xmocktxhash123456789');
    });

    it('should accept string transaction', async () => {
      const txHash = await submitTransaction('0x01020304');

      expect(txHash).toBeDefined();
    });

    it('should throw TransactionError when wallet not connected', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      vi.mocked(getCachedWalletApi).mockReturnValue(null);

      await expect(submitTransaction(new Uint8Array([1, 2, 3]))).rejects.toThrow(
        TransactionError
      );
      await expect(submitTransaction(new Uint8Array([1, 2, 3]))).rejects.toThrow(
        'Wallet not connected'
      );
    });

    it('should handle user rejection without retry', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      const mockApi = createMockWalletApi({
        submitTransaction: vi.fn().mockRejectedValue(new Error('User rejected the request')),
      });
      vi.mocked(getCachedWalletApi).mockReturnValue(mockApi);

      await expect(submitTransaction(new Uint8Array([1, 2, 3]))).rejects.toThrow(
        'User rejected'
      );

      // Should not retry for user rejection
      expect(mockApi.submitTransaction).toHaveBeenCalledTimes(1);
    });

    it('should handle insufficient funds without retry', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      const mockApi = createMockWalletApi({
        submitTransaction: vi.fn().mockRejectedValue(new Error('insufficient funds')),
      });
      vi.mocked(getCachedWalletApi).mockReturnValue(mockApi);

      await expect(submitTransaction(new Uint8Array([1, 2, 3]))).rejects.toThrow(
        'Insufficient funds'
      );

      // Should not retry for insufficient funds
      expect(mockApi.submitTransaction).toHaveBeenCalledTimes(1);
    });

    it('should retry on network errors', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      const mockApi = createMockWalletApi({
        submitTransaction: vi
          .fn()
          .mockRejectedValueOnce(new Error('Network error'))
          .mockRejectedValueOnce(new Error('Network error'))
          .mockResolvedValueOnce('0xtxhash'),
      });
      vi.mocked(getCachedWalletApi).mockReturnValue(mockApi);

      const txHash = await submitTransaction(new Uint8Array([1, 2, 3]));

      expect(txHash).toBe('0xtxhash');
      expect(mockApi.submitTransaction).toHaveBeenCalledTimes(3);
    });

    it('should fail after max retries', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      const mockApi = createMockWalletApi({
        submitTransaction: vi.fn().mockRejectedValue(new Error('Network error')),
      });
      vi.mocked(getCachedWalletApi).mockReturnValue(mockApi);

      await expect(submitTransaction(new Uint8Array([1, 2, 3]), 3)).rejects.toThrow(
        TransactionError
      );

      expect(mockApi.submitTransaction).toHaveBeenCalledTimes(3);
    });

    it('should use exponential backoff for retries', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      const mockApi = createMockWalletApi({
        submitTransaction: vi
          .fn()
          .mockRejectedValueOnce(new Error('Network error'))
          .mockResolvedValueOnce('0xtxhash'),
      });
      vi.mocked(getCachedWalletApi).mockReturnValue(mockApi);

      const startTime = Date.now();
      await submitTransaction(new Uint8Array([1, 2, 3]));
      const duration = Date.now() - startTime;

      // First retry should wait ~1000ms
      expect(duration).toBeGreaterThan(900);
    });
  });

  describe('buildAndSubmitVoteTx', () => {
    it('should build and submit vote transaction', async () => {
      const txHash = await buildAndSubmitVoteTx({
        contractAddress: '0x1234567890abcdef',
        candidateId: 1,
        encryptedBallot: new Uint8Array([1, 2, 3]),
      });

      expect(txHash).toBe('0xmocktxhash123456789');
    });

    it('should connect wallet before building transaction', async () => {
      const { connectWallet } = await import('@/midnight/wallet');

      await buildAndSubmitVoteTx({
        contractAddress: '0x1234567890abcdef',
        candidateId: 1,
        encryptedBallot: new Uint8Array([1, 2, 3]),
      });

      expect(connectWallet).toHaveBeenCalled();
    });

    it('should handle string candidate ID', async () => {
      const txHash = await buildAndSubmitVoteTx({
        contractAddress: '0x1234567890abcdef',
        candidateId: 'candidate-123',
        encryptedBallot: new Uint8Array([1, 2, 3]),
      });

      expect(txHash).toBeDefined();
    });

    it('should handle string encrypted ballot', async () => {
      const txHash = await buildAndSubmitVoteTx({
        contractAddress: '0x1234567890abcdef',
        candidateId: 1,
        encryptedBallot: '0x010203',
      });

      expect(txHash).toBeDefined();
    });

    it('should throw ContractCallError for invalid contract address', async () => {
      await expect(
        buildAndSubmitVoteTx({
          contractAddress: '',
          candidateId: 1,
          encryptedBallot: new Uint8Array([1, 2, 3]),
        })
      ).rejects.toThrow(ContractCallError);
    });

    it('should propagate transaction errors', async () => {
      const { getCachedWalletApi } = await import('@/midnight/wallet');
      const mockApi = createMockWalletApi({
        submitTransaction: vi.fn().mockRejectedValue(new Error('User rejected the request')),
      });
      vi.mocked(getCachedWalletApi).mockReturnValue(mockApi);

      await expect(
        buildAndSubmitVoteTx({
          contractAddress: '0x1234567890abcdef',
          candidateId: 1,
          encryptedBallot: new Uint8Array([1, 2, 3]),
        })
      ).rejects.toThrow(TransactionError);
    });
  });

  describe('getTransactionStatus', () => {
    it('should fetch transaction status', async () => {
      global.fetch = mockRpcFetch({
        blockNumber: 12345,
        timestamp: Date.now(),
      }) as any;

      const status = await getTransactionStatus('0xtxhash');

      expect(status.hash).toBe('0xtxhash');
      expect(status.status).toBe('confirmed');
      expect(status.blockNumber).toBe(12345);
    });

    it('should return pending status for unconfirmed transaction', async () => {
      global.fetch = mockRpcFetch(null) as any;

      const status = await getTransactionStatus('0xtxhash');

      expect(status.status).toBe('pending');
    });

    it('should throw TransactionError on RPC failure', async () => {
      global.fetch = mockRpcFetchError('Service Unavailable') as any;

      await expect(getTransactionStatus('0xtxhash')).rejects.toThrow(TransactionError);
    });

    it('should include transaction hash in error context', async () => {
      global.fetch = mockRpcFetchError() as any;

      try {
        await getTransactionStatus('0xtxhash123');
      } catch (error) {
        expect(error).toBeInstanceOf(TransactionError);
        expect((error as TransactionError).context?.txHash).toBe('0xtxhash123');
      }
    });
  });

  describe('waitForConfirmation', () => {
    it('should wait for transaction confirmation', async () => {
      let callCount = 0;
      global.fetch = vi.fn().mockImplementation(() => {
        callCount++;
        return Promise.resolve({
          ok: true,
          json: () =>
            Promise.resolve({
              jsonrpc: '2.0',
              id: 1,
              result: callCount >= 2 ? { blockNumber: 12345, timestamp: Date.now() } : null,
            }),
        });
      }) as any;

      const result = await waitForConfirmation('0xtxhash', 10000, 100);

      expect(result.status).toBe('confirmed');
      expect(result.blockNumber).toBe(12345);
    });

    it('should timeout if transaction not confirmed', async () => {
      global.fetch = mockRpcFetch(null) as any;

      await expect(waitForConfirmation('0xtxhash', 500, 100)).rejects.toThrow(
        'Transaction confirmation timeout'
      );
    });

    it('should throw if transaction fails on-chain', async () => {
      global.fetch = mockRpcFetch({
        status: 'failed',
        error: 'Execution reverted',
      }) as any;

      // Mock getTransactionStatus to return failed status
      const mockStatus = {
        hash: '0xtxhash',
        status: 'failed' as const,
        error: 'Execution reverted',
      };

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: () =>
          Promise.resolve({
            jsonrpc: '2.0',
            id: 1,
            result: { status: 'failed', error: 'Execution reverted' },
          }),
      }) as any;

      // Override the behavior to return failed status
      vi.spyOn(await import('@/midnight/client'), 'getTransactionStatus').mockResolvedValue(
        mockStatus
      );

      await expect(waitForConfirmation('0xtxhash', 5000, 100)).rejects.toThrow(
        'Transaction failed on-chain'
      );
    });

    it('should use default timeout and poll interval', async () => {
      let callCount = 0;
      global.fetch = vi.fn().mockImplementation(() => {
        callCount++;
        return Promise.resolve({
          ok: true,
          json: () =>
            Promise.resolve({
              jsonrpc: '2.0',
              id: 1,
              result: callCount >= 2 ? { blockNumber: 12345 } : null,
            }),
        });
      }) as any;

      const result = await waitForConfirmation('0xtxhash');

      expect(result.status).toBe('confirmed');
    });
  });

  describe('clearClientCache', () => {
    it('should clear cached client', async () => {
      const client1 = await getMidnightClient();
      clearClientCache();
      const client2 = await getMidnightClient();

      expect(client1).not.toBe(client2);
    });

    it('should be safe to call when no client is cached', () => {
      expect(() => clearClientCache()).not.toThrow();
    });
  });

  describe('edge cases', () => {
    it('should handle very large transaction payloads', async () => {
      const largeTx = new Uint8Array(1000000); // 1MB
      const txHash = await submitTransaction(largeTx);

      expect(txHash).toBeDefined();
    });

    it('should handle concurrent transaction submissions', async () => {
      const tx1 = new Uint8Array([1, 2, 3]);
      const tx2 = new Uint8Array([4, 5, 6]);
      const tx3 = new Uint8Array([7, 8, 9]);

      const [hash1, hash2, hash3] = await Promise.all([
        submitTransaction(tx1),
        submitTransaction(tx2),
        submitTransaction(tx3),
      ]);

      expect(hash1).toBeDefined();
      expect(hash2).toBeDefined();
      expect(hash3).toBeDefined();
    });

    it('should handle non-Error exceptions in contract calls', async () => {
      const mockClient = createMockMidnightClient({
        buildContractCall: vi.fn().mockRejectedValue('String error'),
      });

      // Override getMidnightClient to return our mock
      vi.spyOn(await import('@/midnight/client'), 'getMidnightClient').mockResolvedValue(
        mockClient
      );

      await expect(
        buildContractCall({
          contractAddress: '0x1234567890abcdef',
          entrypoint: 'cast_vote',
        })
      ).rejects.toThrow(ContractCallError);
    });
  });
});
