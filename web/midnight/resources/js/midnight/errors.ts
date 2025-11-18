/**
 * Custom error classes for Midnight wallet integration
 *
 * This file defines specialized error classes for different failure scenarios
 * in wallet connection, transaction submission, and contract interactions.
 *
 * @module midnight/errors
 */

/**
 * Base error class for all Midnight-related errors
 */
export class MidnightError extends Error {
  /** Error code for programmatic identification */
  public readonly code: string;

  /** Additional error context */
  public readonly context?: Record<string, unknown>;

  /**
   * Create a new MidnightError
   * @param message - Human-readable error message
   * @param code - Error code
   * @param context - Additional context information
   */
  constructor(message: string, code: string = 'MIDNIGHT_ERROR', context?: Record<string, unknown>) {
    super(message);
    this.name = 'MidnightError';
    this.code = code;
    this.context = context;

    // Maintains proper stack trace for where error was thrown (only available on V8)
    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, this.constructor);
    }
  }
}

/**
 * Error thrown when Midnight wallet is not found in the browser
 */
export class WalletNotFoundError extends MidnightError {
  /**
   * Create a new WalletNotFoundError
   * @param walletName - Name of the wallet that was not found
   * @param context - Additional context
   */
  constructor(walletName: string = 'mnLace', context?: Record<string, unknown>) {
    super(
      `Midnight wallet "${walletName}" is not installed or not detected. ` +
      'Please install the Lace Beta wallet for Midnight from the browser extension store.',
      'WALLET_NOT_FOUND',
      context
    );
    this.name = 'WalletNotFoundError';
  }
}

/**
 * Error thrown when wallet connection fails
 */
export class WalletConnectionError extends MidnightError {
  /**
   * Create a new WalletConnectionError
   * @param message - Error message
   * @param context - Additional context
   */
  constructor(message: string = 'Failed to connect to Midnight wallet', context?: Record<string, unknown>) {
    super(message, 'WALLET_CONNECTION_ERROR', context);
    this.name = 'WalletConnectionError';
  }

  /**
   * Create error for when user rejects connection
   * @returns WalletConnectionError instance
   */
  static userRejected(): WalletConnectionError {
    return new WalletConnectionError(
      'User rejected wallet connection request',
      { reason: 'USER_REJECTED' }
    );
  }

  /**
   * Create error for when wallet is locked
   * @returns WalletConnectionError instance
   */
  static walletLocked(): WalletConnectionError {
    return new WalletConnectionError(
      'Wallet is locked. Please unlock your wallet and try again.',
      { reason: 'WALLET_LOCKED' }
    );
  }
}

/**
 * Error thrown when wallet state retrieval fails
 */
export class WalletStateError extends MidnightError {
  /**
   * Create a new WalletStateError
   * @param message - Error message
   * @param context - Additional context
   */
  constructor(message: string = 'Failed to retrieve wallet state', context?: Record<string, unknown>) {
    super(message, 'WALLET_STATE_ERROR', context);
    this.name = 'WalletStateError';
  }
}

/**
 * Error thrown when service URI configuration is invalid or unavailable
 */
export class ServiceUriError extends MidnightError {
  /**
   * Create a new ServiceUriError
   * @param message - Error message
   * @param context - Additional context
   */
  constructor(message: string = 'Failed to retrieve service URI configuration', context?: Record<string, unknown>) {
    super(message, 'SERVICE_URI_ERROR', context);
    this.name = 'ServiceUriError';
  }

  /**
   * Create error for missing RPC URI
   * @returns ServiceUriError instance
   */
  static missingRpc(): ServiceUriError {
    return new ServiceUriError(
      'Wallet did not provide RPC URI in service configuration',
      { reason: 'MISSING_RPC' }
    );
  }

  /**
   * Create error for unsupported service URI configuration
   * @returns ServiceUriError instance
   */
  static notSupported(): ServiceUriError {
    return new ServiceUriError(
      'Wallet does not support service URI configuration',
      { reason: 'NOT_SUPPORTED' }
    );
  }
}

/**
 * Error thrown when transaction building or submission fails
 */
export class TransactionError extends MidnightError {
  /** Transaction hash if available */
  public readonly txHash?: string;

  /**
   * Create a new TransactionError
   * @param message - Error message
   * @param context - Additional context (may include txHash)
   */
  constructor(message: string = 'Transaction failed', context?: Record<string, unknown>) {
    super(message, 'TRANSACTION_ERROR', context);
    this.name = 'TransactionError';
    this.txHash = context?.txHash as string | undefined;
  }

  /**
   * Create error for transaction build failure
   * @param reason - Failure reason
   * @returns TransactionError instance
   */
  static buildFailed(reason?: string): TransactionError {
    return new TransactionError(
      `Failed to build transaction${reason ? `: ${reason}` : ''}`,
      { reason: 'BUILD_FAILED', details: reason }
    );
  }

  /**
   * Create error for transaction submission failure
   * @param reason - Failure reason
   * @returns TransactionError instance
   */
  static submitFailed(reason?: string): TransactionError {
    return new TransactionError(
      `Failed to submit transaction${reason ? `: ${reason}` : ''}`,
      { reason: 'SUBMIT_FAILED', details: reason }
    );
  }

  /**
   * Create error for insufficient funds
   * @returns TransactionError instance
   */
  static insufficientFunds(): TransactionError {
    return new TransactionError(
      'Insufficient funds to complete transaction',
      { reason: 'INSUFFICIENT_FUNDS' }
    );
  }

  /**
   * Create error for user rejection
   * @returns TransactionError instance
   */
  static userRejected(): TransactionError {
    return new TransactionError(
      'User rejected transaction in wallet',
      { reason: 'USER_REJECTED' }
    );
  }
}

/**
 * Error thrown when contract call fails
 */
export class ContractCallError extends MidnightError {
  /** Contract address that failed */
  public readonly contractAddress?: string;

  /** Entrypoint that failed */
  public readonly entrypoint?: string;

  /**
   * Create a new ContractCallError
   * @param message - Error message
   * @param context - Additional context
   */
  constructor(message: string = 'Contract call failed', context?: Record<string, unknown>) {
    super(message, 'CONTRACT_CALL_ERROR', context);
    this.name = 'ContractCallError';
    this.contractAddress = context?.contractAddress as string | undefined;
    this.entrypoint = context?.entrypoint as string | undefined;
  }

  /**
   * Create error for invalid contract address
   * @param address - Invalid address
   * @returns ContractCallError instance
   */
  static invalidAddress(address: string): ContractCallError {
    return new ContractCallError(
      `Invalid contract address: ${address}`,
      { reason: 'INVALID_ADDRESS', contractAddress: address }
    );
  }

  /**
   * Create error for invalid entrypoint
   * @param entrypoint - Invalid entrypoint
   * @returns ContractCallError instance
   */
  static invalidEntrypoint(entrypoint: string): ContractCallError {
    return new ContractCallError(
      `Invalid contract entrypoint: ${entrypoint}`,
      { reason: 'INVALID_ENTRYPOINT', entrypoint }
    );
  }

  /**
   * Create error for invalid arguments
   * @param reason - Reason for invalid arguments
   * @returns ContractCallError instance
   */
  static invalidArguments(reason?: string): ContractCallError {
    return new ContractCallError(
      `Invalid contract call arguments${reason ? `: ${reason}` : ''}`,
      { reason: 'INVALID_ARGUMENTS', details: reason }
    );
  }

  /**
   * Create error for contract execution failure
   * @param reason - Execution failure reason
   * @returns ContractCallError instance
   */
  static executionFailed(reason?: string): ContractCallError {
    return new ContractCallError(
      `Contract execution failed${reason ? `: ${reason}` : ''}`,
      { reason: 'EXECUTION_FAILED', details: reason }
    );
  }
}

/**
 * Error thrown when client initialization fails
 */
export class ClientInitializationError extends MidnightError {
  /**
   * Create a new ClientInitializationError
   * @param message - Error message
   * @param context - Additional context
   */
  constructor(message: string = 'Failed to initialize Midnight client', context?: Record<string, unknown>) {
    super(message, 'CLIENT_INIT_ERROR', context);
    this.name = 'ClientInitializationError';
  }

  /**
   * Create error for invalid RPC URL
   * @param rpcUrl - Invalid RPC URL
   * @returns ClientInitializationError instance
   */
  static invalidRpcUrl(rpcUrl: string): ClientInitializationError {
    return new ClientInitializationError(
      `Invalid RPC URL: ${rpcUrl}`,
      { reason: 'INVALID_RPC_URL', rpcUrl }
    );
  }

  /**
   * Create error for network connection failure
   * @param rpcUrl - RPC URL that failed
   * @returns ClientInitializationError instance
   */
  static connectionFailed(rpcUrl: string): ClientInitializationError {
    return new ClientInitializationError(
      `Failed to connect to Midnight network at ${rpcUrl}`,
      { reason: 'CONNECTION_FAILED', rpcUrl }
    );
  }
}

/**
 * Error thrown when encryption/decryption fails
 */
export class EncryptionError extends MidnightError {
  /**
   * Create a new EncryptionError
   * @param message - Error message
   * @param context - Additional context
   */
  constructor(message: string = 'Encryption operation failed', context?: Record<string, unknown>) {
    super(message, 'ENCRYPTION_ERROR', context);
    this.name = 'EncryptionError';
  }

  /**
   * Create error for missing encryption key
   * @returns EncryptionError instance
   */
  static missingKey(): EncryptionError {
    return new EncryptionError(
      'Encryption key not provided or not found',
      { reason: 'MISSING_KEY' }
    );
  }

  /**
   * Create error for invalid encryption parameters
   * @returns EncryptionError instance
   */
  static invalidParams(): EncryptionError {
    return new EncryptionError(
      'Invalid encryption parameters',
      { reason: 'INVALID_PARAMS' }
    );
  }
}

/**
 * Check if an error is a Midnight error
 * @param error - Error to check
 * @returns True if error is a MidnightError
 */
export function isMidnightError(error: unknown): error is MidnightError {
  return error instanceof MidnightError;
}

/**
 * Get user-friendly error message from any error
 * @param error - Error to format
 * @returns User-friendly error message
 */
export function getUserFriendlyErrorMessage(error: unknown): string {
  if (isMidnightError(error)) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return 'An unexpected error occurred';
}
