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

let cachedClient: any = null;
let cachedRpcUrl: string | null = null;
const MAX_RETRY_ATTEMPTS = 3;
const RETRY_DELAY_MS = 1000;

export async function createMidnightClient(config: MidnightClientConfig): Promise<any> {
  const { rpcUrl, proofServerUrl, network, timeout = 30000 } = config;

  if (!rpcUrl || typeof rpcUrl !== 'string') {
    throw ClientInitializationError.invalidRpcUrl(rpcUrl);
  }

  try {
    const client = {
      rpcUrl,
      proofServerUrl,
      network,
      timeout,
      initialized: true,
      buildContractCall: async (params: ContractCallParams) => {
        return {
          contractAddress: params.contractAddress,
          entrypoint: params.entrypoint,
          args: { ...params.publicArgs, ...params.privateArgs },
        };
      },
      serializeTx: async (tx: any) => {
        return JSON.stringify(tx);
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

export async function getMidnightClient(forceNew: boolean = false): Promise<any> {
  const { rpc, proof } = await getServiceUris();

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

export async function buildContractCall(
  params: ContractCallParams
): Promise<Uint8Array | string> {
  const { contractAddress, entrypoint, publicArgs, privateArgs } = params;

  if (!validateContractAddress(contractAddress)) {
    throw ContractCallError.invalidAddress(contractAddress);
  }

  if (!entrypoint || typeof entrypoint !== 'string') {
    throw ContractCallError.invalidEntrypoint(entrypoint);
  }

  try {
    const client = await getMidnightClient();
    const tx = await client.buildContractCall({
      contractAddress,
      entrypoint,
      publicArgs,
      privateArgs,
    });
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

      if (lastError.message.includes('reject')) {
        throw TransactionError.userRejected();
      }

      if (lastError.message.includes('insufficient')) {
        throw TransactionError.insufficientFunds();
      }

      if (attempt < retryAttempts - 1) {
        await sleep(RETRY_DELAY_MS * (attempt + 1));
      }
    }
  }

  throw TransactionError.submitFailed(
    lastError ? lastError.message : 'All retry attempts exhausted'
  );
}

export async function buildAndSubmitVoteTx(payload: VoteTxPayload): Promise<string> {
  const { contractAddress, candidateId, encryptedBallot } = payload;

  try {
    await connectWallet();

    const tx = await buildContractCall({
      contractAddress,
      entrypoint: 'cast_vote',
      publicArgs: { candidateId },
      privateArgs: { ballot: encryptedBallot },
    });

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

export async function getTransactionStatus(txHash: string): Promise<TransactionInfo> {
  try {
    const { rpc } = await getServiceUris();

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

function validateContractAddress(address: string): boolean {
  if (!address || typeof address !== 'string') {
    return false;
  }
  const minLength = 10;
  if (address.length < minLength) {
    return false;
  }
  const cleanAddress = address.replace(/^(0x|mn_)/, '');
  const hexPattern = /^[0-9a-fA-F]+$/;
  return hexPattern.test(cleanAddress);
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export function clearClientCache(): void {
  cachedClient = null;
  cachedRpcUrl = null;
}
