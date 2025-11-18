/**
 * Tests for errors.ts module
 */

import { describe, it, expect } from 'vitest';
import {
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
} from '@/midnight/errors';

describe('errors module', () => {
  describe('MidnightError', () => {
    it('should create error with message and code', () => {
      const error = new MidnightError('Test error', 'TEST_CODE');

      expect(error).toBeInstanceOf(Error);
      expect(error.name).toBe('MidnightError');
      expect(error.message).toBe('Test error');
      expect(error.code).toBe('TEST_CODE');
    });

    it('should use default code if not provided', () => {
      const error = new MidnightError('Test error');

      expect(error.code).toBe('MIDNIGHT_ERROR');
    });

    it('should include context', () => {
      const context = { detail: 'test detail', value: 42 };
      const error = new MidnightError('Test error', 'TEST_CODE', context);

      expect(error.context).toEqual(context);
    });

    it('should have stack trace', () => {
      const error = new MidnightError('Test error');

      expect(error.stack).toBeDefined();
    });

    it('should be catchable', () => {
      try {
        throw new MidnightError('Test error');
      } catch (error) {
        expect(error).toBeInstanceOf(MidnightError);
        expect((error as MidnightError).message).toBe('Test error');
      }
    });
  });

  describe('WalletNotFoundError', () => {
    it('should create error with default wallet name', () => {
      const error = new WalletNotFoundError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('WalletNotFoundError');
      expect(error.code).toBe('WALLET_NOT_FOUND');
      expect(error.message).toContain('mnLace');
      expect(error.message).toContain('not installed');
    });

    it('should create error with custom wallet name', () => {
      const error = new WalletNotFoundError('customWallet');

      expect(error.message).toContain('customWallet');
    });

    it('should include context', () => {
      const context = { suggestion: 'Install the wallet', availableWallets: ['wallet1'] };
      const error = new WalletNotFoundError('mnLace', context);

      expect(error.context).toEqual(context);
    });

    it('should suggest installing Lace Beta', () => {
      const error = new WalletNotFoundError();

      expect(error.message).toContain('Lace Beta');
    });
  });

  describe('WalletConnectionError', () => {
    it('should create error with default message', () => {
      const error = new WalletConnectionError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('WalletConnectionError');
      expect(error.code).toBe('WALLET_CONNECTION_ERROR');
      expect(error.message).toBe('Failed to connect to Midnight wallet');
    });

    it('should create error with custom message', () => {
      const error = new WalletConnectionError('Custom connection error');

      expect(error.message).toBe('Custom connection error');
    });

    it('should create user rejected error', () => {
      const error = WalletConnectionError.userRejected();

      expect(error.message).toContain('User rejected');
      expect(error.context?.reason).toBe('USER_REJECTED');
    });

    it('should create wallet locked error', () => {
      const error = WalletConnectionError.walletLocked();

      expect(error.message).toContain('locked');
      expect(error.message).toContain('unlock');
      expect(error.context?.reason).toBe('WALLET_LOCKED');
    });

    it('should include context', () => {
      const context = { originalError: new Error('Original') };
      const error = new WalletConnectionError('Test', context);

      expect(error.context).toEqual(context);
    });
  });

  describe('WalletStateError', () => {
    it('should create error with default message', () => {
      const error = new WalletStateError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('WalletStateError');
      expect(error.code).toBe('WALLET_STATE_ERROR');
      expect(error.message).toBe('Failed to retrieve wallet state');
    });

    it('should create error with custom message', () => {
      const error = new WalletStateError('Custom state error');

      expect(error.message).toBe('Custom state error');
    });

    it('should include context', () => {
      const context = { detail: 'State retrieval failed' };
      const error = new WalletStateError('Test', context);

      expect(error.context).toEqual(context);
    });
  });

  describe('ServiceUriError', () => {
    it('should create error with default message', () => {
      const error = new ServiceUriError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('ServiceUriError');
      expect(error.code).toBe('SERVICE_URI_ERROR');
      expect(error.message).toBe('Failed to retrieve service URI configuration');
    });

    it('should create missing RPC error', () => {
      const error = ServiceUriError.missingRpc();

      expect(error.message).toContain('RPC URI');
      expect(error.message).toContain('not provide');
      expect(error.context?.reason).toBe('MISSING_RPC');
    });

    it('should create not supported error', () => {
      const error = ServiceUriError.notSupported();

      expect(error.message).toContain('not support');
      expect(error.message).toContain('service URI');
      expect(error.context?.reason).toBe('NOT_SUPPORTED');
    });

    it('should include context', () => {
      const context = { detail: 'URI not found' };
      const error = new ServiceUriError('Test', context);

      expect(error.context).toEqual(context);
    });
  });

  describe('TransactionError', () => {
    it('should create error with default message', () => {
      const error = new TransactionError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('TransactionError');
      expect(error.code).toBe('TRANSACTION_ERROR');
      expect(error.message).toBe('Transaction failed');
    });

    it('should extract transaction hash from context', () => {
      const error = new TransactionError('Test', { txHash: '0x123' });

      expect(error.txHash).toBe('0x123');
    });

    it('should create build failed error', () => {
      const error = TransactionError.buildFailed('Invalid parameters');

      expect(error.message).toContain('Failed to build transaction');
      expect(error.message).toContain('Invalid parameters');
      expect(error.context?.reason).toBe('BUILD_FAILED');
    });

    it('should create build failed error without reason', () => {
      const error = TransactionError.buildFailed();

      expect(error.message).toBe('Failed to build transaction');
    });

    it('should create submit failed error', () => {
      const error = TransactionError.submitFailed('Network error');

      expect(error.message).toContain('Failed to submit transaction');
      expect(error.message).toContain('Network error');
      expect(error.context?.reason).toBe('SUBMIT_FAILED');
    });

    it('should create insufficient funds error', () => {
      const error = TransactionError.insufficientFunds();

      expect(error.message).toContain('Insufficient funds');
      expect(error.context?.reason).toBe('INSUFFICIENT_FUNDS');
    });

    it('should create user rejected error', () => {
      const error = TransactionError.userRejected();

      expect(error.message).toContain('User rejected');
      expect(error.context?.reason).toBe('USER_REJECTED');
    });

    it('should include custom context', () => {
      const context = { txHash: '0xabc', detail: 'test' };
      const error = new TransactionError('Test', context);

      expect(error.context).toEqual(context);
      expect(error.txHash).toBe('0xabc');
    });
  });

  describe('ContractCallError', () => {
    it('should create error with default message', () => {
      const error = new ContractCallError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('ContractCallError');
      expect(error.code).toBe('CONTRACT_CALL_ERROR');
      expect(error.message).toBe('Contract call failed');
    });

    it('should extract contract address from context', () => {
      const error = new ContractCallError('Test', { contractAddress: '0x123' });

      expect(error.contractAddress).toBe('0x123');
    });

    it('should extract entrypoint from context', () => {
      const error = new ContractCallError('Test', { entrypoint: 'cast_vote' });

      expect(error.entrypoint).toBe('cast_vote');
    });

    it('should create invalid address error', () => {
      const error = ContractCallError.invalidAddress('0xinvalid');

      expect(error.message).toContain('Invalid contract address');
      expect(error.message).toContain('0xinvalid');
      expect(error.context?.reason).toBe('INVALID_ADDRESS');
      expect(error.contractAddress).toBe('0xinvalid');
    });

    it('should create invalid entrypoint error', () => {
      const error = ContractCallError.invalidEntrypoint('bad_function');

      expect(error.message).toContain('Invalid contract entrypoint');
      expect(error.message).toContain('bad_function');
      expect(error.context?.reason).toBe('INVALID_ENTRYPOINT');
      expect(error.entrypoint).toBe('bad_function');
    });

    it('should create invalid arguments error', () => {
      const error = ContractCallError.invalidArguments('Missing required field');

      expect(error.message).toContain('Invalid contract call arguments');
      expect(error.message).toContain('Missing required field');
      expect(error.context?.reason).toBe('INVALID_ARGUMENTS');
    });

    it('should create invalid arguments error without reason', () => {
      const error = ContractCallError.invalidArguments();

      expect(error.message).toBe('Invalid contract call arguments');
    });

    it('should create execution failed error', () => {
      const error = ContractCallError.executionFailed('Reverted');

      expect(error.message).toContain('Contract execution failed');
      expect(error.message).toContain('Reverted');
      expect(error.context?.reason).toBe('EXECUTION_FAILED');
    });

    it('should include custom context', () => {
      const context = { contractAddress: '0xabc', entrypoint: 'test', detail: 'error' };
      const error = new ContractCallError('Test', context);

      expect(error.context).toEqual(context);
      expect(error.contractAddress).toBe('0xabc');
      expect(error.entrypoint).toBe('test');
    });
  });

  describe('ClientInitializationError', () => {
    it('should create error with default message', () => {
      const error = new ClientInitializationError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('ClientInitializationError');
      expect(error.code).toBe('CLIENT_INIT_ERROR');
      expect(error.message).toBe('Failed to initialize Midnight client');
    });

    it('should create invalid RPC URL error', () => {
      const error = ClientInitializationError.invalidRpcUrl('invalid-url');

      expect(error.message).toContain('Invalid RPC URL');
      expect(error.message).toContain('invalid-url');
      expect(error.context?.reason).toBe('INVALID_RPC_URL');
      expect(error.context?.rpcUrl).toBe('invalid-url');
    });

    it('should create connection failed error', () => {
      const error = ClientInitializationError.connectionFailed('https://rpc.midnight.network');

      expect(error.message).toContain('Failed to connect');
      expect(error.message).toContain('https://rpc.midnight.network');
      expect(error.context?.reason).toBe('CONNECTION_FAILED');
      expect(error.context?.rpcUrl).toBe('https://rpc.midnight.network');
    });

    it('should include custom context', () => {
      const context = { rpcUrl: 'test', detail: 'error' };
      const error = new ClientInitializationError('Test', context);

      expect(error.context).toEqual(context);
    });
  });

  describe('EncryptionError', () => {
    it('should create error with default message', () => {
      const error = new EncryptionError();

      expect(error).toBeInstanceOf(MidnightError);
      expect(error.name).toBe('EncryptionError');
      expect(error.code).toBe('ENCRYPTION_ERROR');
      expect(error.message).toBe('Encryption operation failed');
    });

    it('should create missing key error', () => {
      const error = EncryptionError.missingKey();

      expect(error.message).toContain('Encryption key');
      expect(error.message).toContain('not provided');
      expect(error.context?.reason).toBe('MISSING_KEY');
    });

    it('should create invalid params error', () => {
      const error = EncryptionError.invalidParams();

      expect(error.message).toContain('Invalid encryption parameters');
      expect(error.context?.reason).toBe('INVALID_PARAMS');
    });

    it('should include custom context', () => {
      const context = { detail: 'Encryption failed' };
      const error = new EncryptionError('Test', context);

      expect(error.context).toEqual(context);
    });
  });

  describe('isMidnightError', () => {
    it('should return true for MidnightError instances', () => {
      expect(isMidnightError(new MidnightError('Test'))).toBe(true);
      expect(isMidnightError(new WalletNotFoundError())).toBe(true);
      expect(isMidnightError(new WalletConnectionError())).toBe(true);
      expect(isMidnightError(new TransactionError())).toBe(true);
      expect(isMidnightError(new ContractCallError())).toBe(true);
    });

    it('should return false for regular errors', () => {
      expect(isMidnightError(new Error('Test'))).toBe(false);
    });

    it('should return false for non-error values', () => {
      expect(isMidnightError('error')).toBe(false);
      expect(isMidnightError(null)).toBe(false);
      expect(isMidnightError(undefined)).toBe(false);
      expect(isMidnightError({})).toBe(false);
      expect(isMidnightError(42)).toBe(false);
    });
  });

  describe('getUserFriendlyErrorMessage', () => {
    it('should return message from MidnightError', () => {
      const error = new WalletNotFoundError();
      const message = getUserFriendlyErrorMessage(error);

      expect(message).toBe(error.message);
    });

    it('should return message from regular Error', () => {
      const error = new Error('Regular error');
      const message = getUserFriendlyErrorMessage(error);

      expect(message).toBe('Regular error');
    });

    it('should return default message for non-error values', () => {
      expect(getUserFriendlyErrorMessage('error')).toBe('An unexpected error occurred');
      expect(getUserFriendlyErrorMessage(null)).toBe('An unexpected error occurred');
      expect(getUserFriendlyErrorMessage(undefined)).toBe('An unexpected error occurred');
      expect(getUserFriendlyErrorMessage({})).toBe('An unexpected error occurred');
      expect(getUserFriendlyErrorMessage(42)).toBe('An unexpected error occurred');
    });

    it('should handle various error types', () => {
      const errors = [
        new WalletNotFoundError(),
        WalletConnectionError.userRejected(),
        TransactionError.insufficientFunds(),
        ContractCallError.invalidAddress('0x123'),
        ServiceUriError.missingRpc(),
      ];

      errors.forEach((error) => {
        const message = getUserFriendlyErrorMessage(error);
        expect(message).toBeDefined();
        expect(message.length).toBeGreaterThan(0);
        expect(message).not.toBe('An unexpected error occurred');
      });
    });
  });

  describe('error inheritance', () => {
    it('should maintain prototype chain', () => {
      const error = new WalletConnectionError();

      expect(error).toBeInstanceOf(WalletConnectionError);
      expect(error).toBeInstanceOf(MidnightError);
      expect(error).toBeInstanceOf(Error);
    });

    it('should work with instanceof checks', () => {
      const errors = [
        new WalletNotFoundError(),
        new WalletConnectionError(),
        new WalletStateError(),
        new ServiceUriError(),
        new TransactionError(),
        new ContractCallError(),
        new ClientInitializationError(),
        new EncryptionError(),
      ];

      errors.forEach((error) => {
        expect(error instanceof MidnightError).toBe(true);
        expect(error instanceof Error).toBe(true);
      });
    });
  });

  describe('error serialization', () => {
    it('should serialize error properties', () => {
      const error = new TransactionError('Test error', {
        txHash: '0x123',
        detail: 'Some detail',
      });

      const serialized = {
        name: error.name,
        message: error.message,
        code: error.code,
        context: error.context,
        txHash: error.txHash,
      };

      expect(serialized).toEqual({
        name: 'TransactionError',
        message: 'Test error',
        code: 'TRANSACTION_ERROR',
        context: {
          txHash: '0x123',
          detail: 'Some detail',
        },
        txHash: '0x123',
      });
    });

    it('should handle errors with nested context', () => {
      const originalError = new Error('Original');
      const error = new WalletConnectionError('Wrapper error', {
        originalError,
        code: 500,
      });

      expect(error.context?.originalError).toBe(originalError);
      expect(error.context?.code).toBe(500);
    });
  });

  describe('error codes', () => {
    it('should have unique error codes', () => {
      const codes = [
        new MidnightError('Test').code,
        new WalletNotFoundError().code,
        new WalletConnectionError().code,
        new WalletStateError().code,
        new ServiceUriError().code,
        new TransactionError().code,
        new ContractCallError().code,
        new ClientInitializationError().code,
        new EncryptionError().code,
      ];

      const uniqueCodes = new Set(codes);
      expect(uniqueCodes.size).toBe(codes.length);
    });

    it('should have descriptive error codes', () => {
      expect(new WalletNotFoundError().code).toBe('WALLET_NOT_FOUND');
      expect(new WalletConnectionError().code).toBe('WALLET_CONNECTION_ERROR');
      expect(new TransactionError().code).toBe('TRANSACTION_ERROR');
      expect(new ContractCallError().code).toBe('CONTRACT_CALL_ERROR');
    });
  });
});
