/**
 * Midnight Wallet Integration - Main Export
 *
 * This module provides a complete TypeScript integration for Midnight blockchain
 * wallet connection, transaction building, and contract interactions.
 *
 * @module midnight
 * @packageDocumentation
 */

// Export all types
export type {
  WalletState,
  ServiceUriConfig,
  TransactionResult,
  PublicArgs,
  PrivateArgs,
  ContractCallParams,
  VoteTxPayload,
  MidnightWalletApi,
  MidnightInjectedWallet,
  MidnightWindow,
  WalletConnectionState,
  WalletEventType,
  WalletEvent,
  WalletEventListener,
  TransactionStatus,
  TransactionInfo,
  MidnightClientConfig,
  ContractInfo,
  EncryptedBallot,
} from './types';

// Export wallet functions
export {
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
} from './wallet';

// Export client functions
export {
  createMidnightClient,
  getMidnightClient,
  buildContractCall,
  submitTransaction,
  buildAndSubmitVoteTx,
  getTransactionStatus,
  waitForConfirmation,
  clearClientCache,
} from './client';

// Export error classes
export {
  MidnightError,
  WalletNotFoundError,
  WalletConnectionError,
  WalletStateError,
  ServiceUriError,
  TransactionError,
  ContractCallError,
  ClientInitializationError,
  EncryptionError,
  isMidnightError,
  getUserFriendlyErrorMessage,
} from './errors';

// Export utility functions
export {
  formatAddress,
  shortenTxHash,
  validateContractAddress,
  validateTxHash,
  encryptBallot,
  bytesToHex,
  hexToBytes,
  formatTimestamp,
  truncateString,
  debounce,
  retryWithBackoff,
  parseErrorMessage,
  deepClone,
  isBrowser,
  safeJsonParse,
} from './utils';
