/**
 * TypeScript type definitions for Midnight wallet integration
 *
 * This file contains all interface definitions for the Midnight blockchain
 * wallet connection, transaction building, and contract interactions.
 *
 * @module midnight/types
 */

/**
 * Wallet state information returned by the Midnight wallet
 */
export interface WalletState {
  /** List of wallet addresses */
  addresses?: string[];
  /** Public keys associated with the wallet */
  publicKeys?: string[];
  /** Network identifier */
  network?: string;
  /** Balance information */
  balance?: string | number;
  /** Additional wallet-specific metadata */
  [key: string]: unknown;
}

/**
 * Service URI configuration from the wallet
 */
export interface ServiceUriConfig {
  /** RPC endpoint URL */
  rpc: string;
  /** Proof server URL (optional) */
  proof?: string;
  /** Indexer URL (optional) */
  indexer?: string;
  /** Additional service URIs */
  [key: string]: unknown;
}

/**
 * Transaction submission result
 */
export interface TransactionResult {
  /** Transaction hash */
  txHash: string;
  /** Submission timestamp */
  timestamp?: number;
  /** Additional metadata */
  [key: string]: unknown;
}

/**
 * Public arguments for a contract call
 */
export interface PublicArgs {
  /** Candidate ID or other public parameter */
  candidateId?: string | number;
  /** Additional public arguments */
  [key: string]: unknown;
}

/**
 * Private arguments for a contract call (ZK proof inputs)
 */
export interface PrivateArgs {
  /** Encrypted ballot data */
  ballot?: string | Uint8Array;
  /** Additional private arguments */
  [key: string]: unknown;
}

/**
 * Contract call parameters
 */
export interface ContractCallParams {
  /** Contract address on Midnight blockchain */
  contractAddress: string;
  /** Entrypoint/function name to call */
  entrypoint: string;
  /** Public arguments visible on-chain */
  publicArgs?: PublicArgs;
  /** Private arguments (ZK proof inputs) */
  privateArgs?: PrivateArgs;
}

/**
 * Vote transaction payload
 */
export interface VoteTxPayload {
  /** Contract address for the election */
  contractAddress: string;
  /** Candidate identifier */
  candidateId: string | number;
  /** Encrypted ballot data */
  encryptedBallot: string | Uint8Array;
}

/**
 * Midnight wallet API interface
 *
 * Represents the API returned after wallet connection is established
 */
export interface MidnightWalletApi {
  /**
   * Get current wallet state (addresses, public keys, etc.)
   * @returns Promise resolving to wallet state
   */
  state(): Promise<WalletState>;

  /**
   * Submit a transaction through the wallet
   * @param tx - Serialized transaction (Uint8Array or hex string)
   * @returns Promise resolving to transaction hash
   */
  submitTransaction(tx: Uint8Array | string): Promise<string>;

  /**
   * Sign a message with the wallet
   * @param message - Message to sign
   * @returns Promise resolving to signature
   */
  signMessage?(message: string | Uint8Array): Promise<string>;

  /**
   * Get wallet balance
   * @returns Promise resolving to balance
   */
  getBalance?(): Promise<string | number>;
}

/**
 * Midnight injected wallet interface
 *
 * Represents the wallet object injected into the browser window
 * (e.g., window.midnight.mnLace)
 */
export interface MidnightInjectedWallet {
  /**
   * Request permission and enable wallet connection
   * @returns Promise resolving to wallet API instance
   */
  enable(): Promise<MidnightWalletApi>;

  /**
   * Get service URI configuration (RPC, proof server, etc.)
   * @returns Promise resolving to service URIs
   */
  serviceUriConfig?(): Promise<ServiceUriConfig>;

  /**
   * Check if wallet is already enabled/connected
   * @returns Promise resolving to connection status
   */
  isEnabled?(): Promise<boolean>;

  /**
   * Get wallet name/identifier
   */
  name?: string;

  /**
   * Get wallet version
   */
  version?: string;
}

/**
 * Extended Window interface with Midnight wallet injection
 */
export interface MidnightWindow extends Window {
  /** Midnight wallet injection namespace */
  midnight?: {
    /** Lace wallet for Midnight */
    mnLace?: MidnightInjectedWallet;
    /** Other wallet implementations can be added here */
    [walletName: string]: MidnightInjectedWallet | undefined;
  };
}

/**
 * Wallet connection state
 */
export type WalletConnectionState =
  | 'disconnected'
  | 'connecting'
  | 'connected'
  | 'error';

/**
 * Wallet event types
 */
export type WalletEventType =
  | 'connect'
  | 'disconnect'
  | 'accountChange'
  | 'networkChange'
  | 'error';

/**
 * Wallet event data
 */
export interface WalletEvent {
  /** Event type */
  type: WalletEventType;
  /** Event payload */
  data?: unknown;
  /** Timestamp */
  timestamp: number;
}

/**
 * Wallet event listener callback
 */
export type WalletEventListener = (event: WalletEvent) => void;

/**
 * Transaction status
 */
export type TransactionStatus =
  | 'pending'
  | 'submitted'
  | 'confirmed'
  | 'failed'
  | 'unknown';

/**
 * Transaction info
 */
export interface TransactionInfo {
  /** Transaction hash */
  hash: string;
  /** Transaction status */
  status: TransactionStatus;
  /** Block number (if confirmed) */
  blockNumber?: number;
  /** Timestamp */
  timestamp?: number;
  /** Error message (if failed) */
  error?: string;
}

/**
 * Midnight client configuration
 */
export interface MidnightClientConfig {
  /** RPC endpoint URL */
  rpcUrl: string;
  /** Proof server URL (optional) */
  proofServerUrl?: string;
  /** Network identifier */
  network?: string;
  /** Timeout in milliseconds */
  timeout?: number;
}

/**
 * Contract deployment info
 */
export interface ContractInfo {
  /** Contract address */
  address: string;
  /** Contract name */
  name?: string;
  /** Deployment transaction hash */
  deploymentTxHash?: string;
  /** ABI or interface definition */
  abi?: unknown;
}

/**
 * Encrypted ballot data structure
 */
export interface EncryptedBallot {
  /** Encrypted ballot content */
  ciphertext: string | Uint8Array;
  /** Public encryption parameters */
  publicParams?: unknown;
  /** Proof of correct encryption */
  proof?: string | Uint8Array;
}
