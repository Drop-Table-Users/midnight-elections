/**
 * Midnight JS client wrapper module
 *
 * This module provides wrapper functions for interacting with the Midnight blockchain,
 * building transactions, and submitting contract calls through the connected wallet.
 *
 * @module midnight/client
 */

import type {
  VoteTxPayload,
  ContractCallParams,
  MidnightClientConfig,
  TransactionInfo,
  TransactionStatus,
} from './types';

import { getServiceUris, connectWallet, getCachedWalletApi } from './wallet';

import {
  TransactionError,
  ContractCallError,
  ClientInitializationError,
} from './errors';

/**
 * Cached Midnight client instance
 */
let cachedClient: any = null;

/**
 * Cached RPC URL to detect config changes
 */
let cachedRpcUrl: string | null = null;

/**
 * Maximum number of retry attempts for transaction submission
 */
const MAX_RETRY_ATTEMPTS = 3;

/**
 * Delay between retry attempts in milliseconds
 */
const RETRY_DELAY_MS = 1000;

/**
 * Create a Midnight client instance
 *
 * This function initializes the Midnight JS client with the RPC endpoint
 * obtained from the wallet configuration.
 *
 * @param config - Client configuration
 * @returns Promise resolving to Midnight client instance
 * @throws {ClientInitializationError} If client initialization fails
 *
 * @example
 * ```typescript
 * const { rpc } = await getServiceUris();
 * const client = await createMidnightClient({ rpcUrl: rpc });
 * ```
 *
 * @internal
 */
export async function createMidnightClient(config: MidnightClientConfig): Promise<any> {
  const { rpcUrl, proofServerUrl, network, timeout = 30000 } = config;

  // Validate RPC URL
  if (!rpcUrl || typeof rpcUrl !== 'string') {
    throw ClientInitializationError.invalidRpcUrl(rpcUrl);
  }

  try {
    // TODO: Replace with actual Midnight JS client initialization
    // This is a placeholder implementation that needs to be replaced with
    // the real Midnight.js SDK when it becomes available
    //
    // Example (pseudo-code):
    // import { MidnightClient } from '@midnight-ntwrk/midnight-js';
    // const client = new MidnightClient({
    //   rpcUrl,
    //   proofServerUrl,
    //   network,
    //   timeout,
    // });
    // await client.initialize();
    // return client;

    console.warn(
      'createMidnightClient is using placeholder implementation. ' +
      'Replace with actual Midnight JS SDK when available.'
    );

    const client = {
      rpcUrl,
      proofServerUrl,
      network,
      timeout,
      initialized: true,
      // Placeholder methods
      buildContractCall: async (params: ContractCallParams) => {
        console.log('Building contract call:', params);
        return {
          contractAddress: params.contractAddress,
          entrypoint: params.entrypoint,
          args: { ...params.publicArgs, ...params.privateArgs },
        };
      },
      serializeTx: async (tx: any) => {
        console.log('Serializing transaction:', tx);
        return new Uint8Array([1, 2, 3, 4]); // Placeholder
      },
    };

    return client;
  } catch (error) {
    if (error instanceof Error) {
      throw ClientInitializationError.connectionFailed(rpcUrl);
    }
    throw new ClientInitializationError('Unknown error during client initialization');
  }
}

/**
 * Get or create a Midnight client instance
 *
 * This function manages client caching and reuses the same instance
 * if the RPC configuration hasn't changed.
 *
 * @param forceNew - Force creation of a new client instance
 * @returns Promise resolving to Midnight client
 * @throws {ClientInitializationError} If client initialization fails
 *
 * @example
 * ```typescript
 * const client = await getMidnightClient();
 * ```
 */
export async function getMidnightClient(forceNew: boolean = false): Promise<any> {
  const { rpc, proof } = await getServiceUris();

  // Return cached client if RPC URL hasn't changed and not forcing new
  if (cachedClient && cachedRpcUrl === rpc && !forceNew) {
    return cachedClient;
  }

  const client = await createMidnightClient({
    rpcUrl: rpc,
    proofServerUrl: proof,
  });

  cachedClient = client;
  cachedRpcUrl = rpc;

  return client;
}

/**
 * Build a contract call transaction
 *
 * This function constructs a contract call with the specified parameters,
 * preparing it for submission through the wallet.
 *
 * @param params - Contract call parameters
 * @returns Promise resolving to serialized transaction
 * @throws {ContractCallError} If contract call building fails
 *
 * @example
 * ```typescript
 * const tx = await buildContractCall({
 *   contractAddress: '0x123...',
 *   entrypoint: 'cast_vote',
 *   publicArgs: { candidateId: 1 },
 *   privateArgs: { ballot: encryptedBallot }
 * });
 * ```
 */
export async function buildContractCall(
  params: ContractCallParams
): Promise<Uint8Array | string> {
  const { contractAddress, entrypoint, publicArgs, privateArgs } = params;

  // Validate contract address
  if (!validateContractAddress(contractAddress)) {
    throw ContractCallError.invalidAddress(contractAddress);
  }

  // Validate entrypoint
  if (!entrypoint || typeof entrypoint !== 'string') {
    throw ContractCallError.invalidEntrypoint(entrypoint);
  }

  try {
    const client = await getMidnightClient();

    // Build contract call transaction
    const tx = await client.buildContractCall({
      contractAddress,
      entrypoint,
      publicArgs,
      privateArgs,
    });

    // Serialize the transaction
    const serialized = await client.serializeTx(tx);
    return serialized;
  } catch (error) {
    if (error instanceof ContractCallError) {
      throw error;
    }
    if (error instanceof Error) {
      throw new ContractCallError(error.message, {
        contractAddress,
        entrypoint,
        originalError: error,
      });
    }
    throw ContractCallError.executionFailed('Unknown error during contract call building');
  }
}

/**
 * Submit a transaction through the connected wallet
 *
 * This function submits a serialized transaction to the Midnight network
 * via the connected wallet, with automatic retry logic.
 *
 * @param tx - Serialized transaction
 * @param retryAttempts - Number of retry attempts (default: MAX_RETRY_ATTEMPTS)
 * @returns Promise resolving to transaction hash
 * @throws {TransactionError} If submission fails after all retries
 *
 * @example
 * ```typescript
 * const tx = await buildContractCall(params);
 * const txHash = await submitTransaction(tx);
 * console.log('Transaction submitted:', txHash);
 * ```
 */
export async function submitTransaction(
  tx: Uint8Array | string,
  retryAttempts: number = MAX_RETRY_ATTEMPTS
): Promise<string> {
  const walletApi = getCachedWalletApi();

  if (!walletApi) {
    throw new TransactionError('Wallet not connected', { reason: 'NO_WALLET' });
  }

  let lastError: Error | null = null;

  for (let attempt = 0; attempt < retryAttempts; attempt++) {
    try {
      const txHash = await walletApi.submitTransaction(tx);
      return txHash;
    } catch (error) {
      lastError = error instanceof Error ? error : new Error('Unknown error');

      // Check for user rejection - don't retry
      if (lastError.message.includes('reject')) {
        throw TransactionError.userRejected();
      }

      // Check for insufficient funds - don't retry
      if (lastError.message.includes('insufficient')) {
        throw TransactionError.insufficientFunds();
      }

      // If not the last attempt, wait before retrying
      if (attempt < retryAttempts - 1) {
        await sleep(RETRY_DELAY_MS * (attempt + 1));
      }
    }
  }

  // All retries exhausted
  throw TransactionError.submitFailed(
    lastError ? lastError.message : 'All retry attempts exhausted'
  );
}

/**
 * Build and submit a vote transaction
 *
 * This is a convenience function that combines contract call building
 * and transaction submission for the voting use case.
 *
 * @param payload - Vote transaction payload
 * @returns Promise resolving to transaction hash
 * @throws {ContractCallError} If contract call building fails
 * @throws {TransactionError} If transaction submission fails
 *
 * @example
 * ```typescript
 * const txHash = await buildAndSubmitVoteTx({
 *   contractAddress: election.contractAddress,
 *   candidateId: 1,
 *   encryptedBallot: ballot
 * });
 * console.log('Vote submitted:', txHash);
 * ```
 */
export async function buildAndSubmitVoteTx(payload: VoteTxPayload): Promise<string> {
  const { contractAddress, candidateId, encryptedBallot } = payload;

  try {
    // Ensure wallet is connected
    await connectWallet();

    // Build the contract call transaction
    const tx = await buildContractCall({
      contractAddress,
      entrypoint: 'cast_vote',
      publicArgs: { candidateId },
      privateArgs: { ballot: encryptedBallot },
    });

    // Submit the transaction
    const txHash = await submitTransaction(tx);

    return txHash;
  } catch (error) {
    if (error instanceof TransactionError || error instanceof ContractCallError) {
      throw error;
    }
    if (error instanceof Error) {
      throw new TransactionError(error.message, { originalError: error });
    }
    throw new TransactionError('Failed to build and submit vote transaction');
  }
}

/**
 * Get transaction status
 *
 * Query the Midnight network for the current status of a transaction.
 *
 * @param txHash - Transaction hash to query
 * @returns Promise resolving to transaction info
 *
 * @example
 * ```typescript
 * const info = await getTransactionStatus(txHash);
 * console.log('Status:', info.status);
 * if (info.status === 'confirmed') {
 *   console.log('Block:', info.blockNumber);
 * }
 * ```
 */
export async function getTransactionStatus(txHash: string): Promise<TransactionInfo> {
  try {
    const { rpc } = await getServiceUris();

    // TODO: Replace with actual Midnight JS transaction status query
    // This is a placeholder implementation using RPC
    //
    // Example (pseudo-code):
    // const client = await getMidnightClient();
    // const txInfo = await client.getTransaction(txHash);
    // return {
    //   hash: txHash,
    //   status: txInfo.confirmed ? 'confirmed' : 'pending',
    //   blockNumber: txInfo.blockNumber,
    //   timestamp: txInfo.timestamp,
    // };

    const response = await fetch(rpc, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        jsonrpc: '2.0',
        method: 'midnight_getTransactionReceipt',
        params: [txHash],
        id: 1,
      }),
    });

    if (!response.ok) {
      throw new Error(`RPC request failed: ${response.statusText}`);
    }

    const result = await response.json();

    // Parse result and return status
    return {
      hash: txHash,
      status: result.result ? ('confirmed' as TransactionStatus) : ('pending' as TransactionStatus),
      blockNumber: result.result?.blockNumber,
      timestamp: result.result?.timestamp,
    };
  } catch (error) {
    if (error instanceof Error) {
      throw new TransactionError(`Failed to get transaction status: ${error.message}`, {
        txHash,
        originalError: error,
      });
    }
    throw new TransactionError('Failed to get transaction status', { txHash });
  }
}

/**
 * Wait for transaction confirmation
 *
 * Poll the network until the transaction is confirmed or times out.
 *
 * @param txHash - Transaction hash to wait for
 * @param timeout - Maximum time to wait in milliseconds (default: 60000)
 * @param pollInterval - Polling interval in milliseconds (default: 2000)
 * @returns Promise resolving to transaction info when confirmed
 * @throws {TransactionError} If transaction fails or times out
 *
 * @example
 * ```typescript
 * const txHash = await submitTransaction(tx);
 * const confirmedTx = await waitForConfirmation(txHash);
 * console.log('Transaction confirmed in block:', confirmedTx.blockNumber);
 * ```
 */
export async function waitForConfirmation(
  txHash: string,
  timeout: number = 60000,
  pollInterval: number = 2000
): Promise<TransactionInfo> {
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    const txInfo = await getTransactionStatus(txHash);

    if (txInfo.status === 'confirmed') {
      return txInfo;
    }

    if (txInfo.status === 'failed') {
      throw new TransactionError('Transaction failed on-chain', {
        txHash,
        error: txInfo.error,
      });
    }

    await sleep(pollInterval);
  }

  throw new TransactionError('Transaction confirmation timeout', {
    txHash,
    timeout,
  });
}

/**
 * Validate contract address format
 *
 * @param address - Address to validate
 * @returns True if address is valid
 *
 * @internal
 */
function validateContractAddress(address: string): boolean {
  // TODO: Implement actual Midnight address validation
  // This is a basic placeholder validation
  return typeof address === 'string' && address.length > 0;
}

/**
 * Sleep utility function
 *
 * @param ms - Milliseconds to sleep
 * @returns Promise that resolves after the specified delay
 *
 * @internal
 */
function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Clear cached client instance
 *
 * Useful for forcing a fresh client initialization.
 *
 * @example
 * ```typescript
 * clearClientCache();
 * const newClient = await getMidnightClient();
 * ```
 */
export function clearClientCache(): void {
  cachedClient = null;
  cachedRpcUrl = null;
}
