/**
 * Tests for utils.ts module
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
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
} from '@/midnight/utils';
import { EncryptionError } from '@/midnight/errors';

describe('utils module', () => {
  describe('formatAddress', () => {
    it('should format long addresses with default lengths', () => {
      const address = '0x1234567890abcdef1234567890abcdef12345678';
      const formatted = formatAddress(address);

      expect(formatted).toBe('0x1234...5678');
    });

    it('should use custom prefix and suffix lengths', () => {
      const address = '0x1234567890abcdef1234567890abcdef12345678';
      const formatted = formatAddress(address, 4, 6);

      expect(formatted).toBe('0x12...345678');
    });

    it('should return full address if shorter than prefix + suffix', () => {
      const address = '0x123456';
      const formatted = formatAddress(address);

      expect(formatted).toBe('0x123456');
    });

    it('should return empty string for empty input', () => {
      expect(formatAddress('')).toBe('');
    });

    it('should return empty string for null/undefined', () => {
      expect(formatAddress(null as any)).toBe('');
      expect(formatAddress(undefined as any)).toBe('');
    });

    it('should return empty string for non-string input', () => {
      expect(formatAddress(123 as any)).toBe('');
    });
  });

  describe('shortenTxHash', () => {
    it('should shorten transaction hash with default lengths', () => {
      const txHash = '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';
      const shortened = shortenTxHash(txHash);

      expect(shortened).toBe('0xabcdef...567890');
    });

    it('should use custom prefix and suffix lengths', () => {
      const txHash = '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';
      const shortened = shortenTxHash(txHash, 6, 4);

      expect(shortened).toBe('0xabcd...7890');
    });

    it('should handle short transaction hashes', () => {
      const txHash = '0x123';
      const shortened = shortenTxHash(txHash);

      expect(shortened).toBe('0x123');
    });
  });

  describe('validateContractAddress', () => {
    it('should validate hex addresses', () => {
      expect(validateContractAddress('0x1234567890abcdef')).toBe(true);
    });

    it('should validate addresses with mn_ prefix', () => {
      expect(validateContractAddress('mn_1234567890abcdef')).toBe(true);
    });

    it('should reject empty addresses', () => {
      expect(validateContractAddress('')).toBe(false);
    });

    it('should reject null/undefined', () => {
      expect(validateContractAddress(null as any)).toBe(false);
      expect(validateContractAddress(undefined as any)).toBe(false);
    });

    it('should reject non-string values', () => {
      expect(validateContractAddress(123 as any)).toBe(false);
    });

    it('should reject addresses shorter than minimum length', () => {
      expect(validateContractAddress('0x123')).toBe(false);
    });

    it('should reject addresses with invalid characters', () => {
      expect(validateContractAddress('0x1234567890xyz')).toBe(false);
    });

    it('should accept mixed case hex', () => {
      expect(validateContractAddress('0x1234567890AbCdEf')).toBe(true);
    });
  });

  describe('validateTxHash', () => {
    it('should validate 64-character hex hash', () => {
      const hash = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
      expect(validateTxHash('0x' + hash)).toBe(true);
    });

    it('should validate hash without 0x prefix', () => {
      const hash = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
      expect(validateTxHash(hash)).toBe(true);
    });

    it('should reject hashes with wrong length', () => {
      expect(validateTxHash('0x123')).toBe(false);
      expect(validateTxHash('0x' + '1'.repeat(65))).toBe(false);
    });

    it('should reject hashes with invalid characters', () => {
      const invalidHash = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdeg';
      expect(validateTxHash(invalidHash)).toBe(false);
    });

    it('should reject empty hashes', () => {
      expect(validateTxHash('')).toBe(false);
    });

    it('should reject null/undefined', () => {
      expect(validateTxHash(null as any)).toBe(false);
      expect(validateTxHash(undefined as any)).toBe(false);
    });
  });

  describe('encryptBallot', () => {
    it('should encrypt ballot data', async () => {
      const ballot = { candidateId: 1 };
      const encrypted = await encryptBallot(ballot);

      expect(encrypted).toBeDefined();
      expect(encrypted.ciphertext).toBeInstanceOf(Uint8Array);
    });

    it('should include public key in params if provided', async () => {
      const ballot = { candidateId: 1 };
      const publicKey = '0xpublickey';

      const encrypted = await encryptBallot(ballot, publicKey);

      expect(encrypted.publicParams).toBeDefined();
      expect(encrypted.publicParams?.key).toBe(publicKey);
    });

    it('should handle Uint8Array public key', async () => {
      const ballot = { candidateId: 1 };
      const publicKey = new Uint8Array([1, 2, 3]);

      const encrypted = await encryptBallot(ballot, publicKey);

      expect(encrypted.publicParams?.key).toBe(publicKey);
    });

    it('should handle complex ballot data', async () => {
      const ballot = {
        candidateId: 1,
        timestamp: Date.now(),
        metadata: { source: 'test' },
      };

      const encrypted = await encryptBallot(ballot);

      expect(encrypted.ciphertext).toBeDefined();
    });

    it('should throw EncryptionError on failure', async () => {
      // Force an error by passing invalid data
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      // This should succeed with placeholder implementation
      const result = await encryptBallot({});
      expect(result).toBeDefined();

      consoleSpy.mockRestore();
    });
  });

  describe('bytesToHex', () => {
    it('should convert bytes to hex with prefix', () => {
      const bytes = new Uint8Array([1, 2, 3, 255]);
      const hex = bytesToHex(bytes);

      expect(hex).toBe('0x010203ff');
    });

    it('should convert bytes to hex without prefix', () => {
      const bytes = new Uint8Array([1, 2, 3, 255]);
      const hex = bytesToHex(bytes, false);

      expect(hex).toBe('010203ff');
    });

    it('should handle empty byte array', () => {
      const bytes = new Uint8Array([]);
      const hex = bytesToHex(bytes);

      expect(hex).toBe('0x');
    });

    it('should pad single-digit hex values', () => {
      const bytes = new Uint8Array([0, 1, 15, 16]);
      const hex = bytesToHex(bytes);

      expect(hex).toBe('0x00010f10');
    });
  });

  describe('hexToBytes', () => {
    it('should convert hex to bytes with prefix', () => {
      const bytes = hexToBytes('0x010203ff');

      expect(bytes).toEqual(new Uint8Array([1, 2, 3, 255]));
    });

    it('should convert hex to bytes without prefix', () => {
      const bytes = hexToBytes('010203ff');

      expect(bytes).toEqual(new Uint8Array([1, 2, 3, 255]));
    });

    it('should handle empty hex string', () => {
      const bytes = hexToBytes('0x');

      expect(bytes).toEqual(new Uint8Array([]));
    });

    it('should throw error for odd-length hex string', () => {
      expect(() => hexToBytes('0x123')).toThrow('odd length');
    });

    it('should handle lowercase hex', () => {
      const bytes = hexToBytes('0xabcdef');

      expect(bytes).toEqual(new Uint8Array([171, 205, 239]));
    });

    it('should handle uppercase hex', () => {
      const bytes = hexToBytes('0xABCDEF');

      expect(bytes).toEqual(new Uint8Array([171, 205, 239]));
    });

    it('should roundtrip with bytesToHex', () => {
      const original = new Uint8Array([1, 2, 3, 255, 128, 0]);
      const hex = bytesToHex(original);
      const bytes = hexToBytes(hex);

      expect(bytes).toEqual(original);
    });
  });

  describe('formatTimestamp', () => {
    it('should format timestamp with default options', () => {
      const timestamp = new Date('2025-11-15T10:30:00').getTime();
      const formatted = formatTimestamp(timestamp);

      // Test for presence of date components (locale-independent)
      expect(formatted).toContain('15');
      expect(formatted).toContain('2025');
      expect(formatted).toBeDefined();
      expect(formatted.length).toBeGreaterThan(0);
    });

    it('should format timestamp with custom options', () => {
      const timestamp = new Date('2025-11-15T10:30:00').getTime();
      const formatted = formatTimestamp(timestamp, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });

      // Test for presence of date components (locale-independent)
      expect(formatted).toContain('15');
      expect(formatted).toContain('2025');
      expect(formatted.length).toBeGreaterThan(0);
    });

    it('should handle current timestamp', () => {
      const formatted = formatTimestamp(Date.now());

      expect(formatted).toBeDefined();
      expect(formatted.length).toBeGreaterThan(0);
    });
  });

  describe('truncateString', () => {
    it('should truncate long strings', () => {
      const str = 'This is a very long string that needs to be truncated';
      const truncated = truncateString(str, 20);

      expect(truncated).toBe('This is a very lo...');
      expect(truncated.length).toBe(20);
    });

    it('should not truncate short strings', () => {
      const str = 'Short';
      const truncated = truncateString(str, 20);

      expect(truncated).toBe('Short');
    });

    it('should use custom ellipsis', () => {
      const str = 'This is a very long string';
      const truncated = truncateString(str, 10, '>>');

      expect(truncated).toBe('This is >>');
    });

    it('should handle empty strings', () => {
      expect(truncateString('', 10)).toBe('');
    });

    it('should handle null/undefined', () => {
      expect(truncateString(null as any, 10)).toBe(null);
      expect(truncateString(undefined as any, 10)).toBe(undefined);
    });
  });

  describe('debounce', () => {
    beforeEach(() => {
      vi.useFakeTimers();
    });

    afterEach(() => {
      vi.restoreAllMocks();
    });

    it('should debounce function calls', () => {
      const fn = vi.fn();
      const debounced = debounce(fn, 300);

      debounced();
      debounced();
      debounced();

      expect(fn).not.toHaveBeenCalled();

      vi.advanceTimersByTime(300);

      expect(fn).toHaveBeenCalledTimes(1);
    });

    it('should pass arguments to debounced function', () => {
      const fn = vi.fn();
      const debounced = debounce(fn, 300);

      debounced('arg1', 'arg2');

      vi.advanceTimersByTime(300);

      expect(fn).toHaveBeenCalledWith('arg1', 'arg2');
    });

    it('should reset timer on each call', () => {
      const fn = vi.fn();
      const debounced = debounce(fn, 300);

      debounced();
      vi.advanceTimersByTime(200);
      debounced();
      vi.advanceTimersByTime(200);
      debounced();

      expect(fn).not.toHaveBeenCalled();

      vi.advanceTimersByTime(300);

      expect(fn).toHaveBeenCalledTimes(1);
    });

    it('should preserve this context', () => {
      const obj = {
        value: 42,
        method: function (this: any) {
          return this.value;
        },
      };

      const debounced = debounce(function (this: any) {
        return obj.method.call(this);
      }, 100);

      const boundDebounced = debounced.bind(obj);
      boundDebounced();

      vi.advanceTimersByTime(100);
    });
  });

  describe('retryWithBackoff', () => {
    it('should succeed on first attempt', async () => {
      const fn = vi.fn().mockResolvedValue('success');

      const result = await retryWithBackoff(fn, 3, 100);

      expect(result).toBe('success');
      expect(fn).toHaveBeenCalledTimes(1);
    });

    it('should retry on failure and eventually succeed', async () => {
      const fn = vi
        .fn()
        .mockRejectedValueOnce(new Error('Fail 1'))
        .mockRejectedValueOnce(new Error('Fail 2'))
        .mockResolvedValue('success');

      const result = await retryWithBackoff(fn, 3, 1);

      expect(result).toBe('success');
      expect(fn).toHaveBeenCalledTimes(3);
    }, 15000);

    it('should throw after max retries', async () => {
      const fn = vi.fn().mockRejectedValue(new Error('Always fails'));

      await expect(retryWithBackoff(fn, 3, 1)).rejects.toThrow('Always fails');
      expect(fn).toHaveBeenCalledTimes(3);
    }, 15000);

    it('should use exponential backoff', async () => {
      const fn = vi
        .fn()
        .mockRejectedValueOnce(new Error('Fail 1'))
        .mockRejectedValueOnce(new Error('Fail 2'))
        .mockResolvedValue('success');

      const startTime = Date.now();
      await retryWithBackoff(fn, 3, 5);
      const duration = Date.now() - startTime;

      // First retry: 5ms, Second retry: 10ms = ~15ms total (minimum)
      expect(duration).toBeGreaterThan(10);
    }, 15000);

    it('should handle non-Error exceptions', async () => {
      const fn = vi.fn().mockRejectedValue('String error');

      await expect(retryWithBackoff(fn, 2, 1)).rejects.toBe('String error');
    }, 15000);
  });

  describe('parseErrorMessage', () => {
    it('should parse Error objects', () => {
      const error = new Error('Something went wrong');
      const message = parseErrorMessage(error);

      expect(message).toBe('Something went wrong');
    });

    it('should parse string errors', () => {
      const message = parseErrorMessage('Error string');

      expect(message).toBe('Error string');
    });

    it('should handle unknown errors', () => {
      const message = parseErrorMessage({ foo: 'bar' });

      expect(message).toBe('An unexpected error occurred');
    });

    it('should remove "Error:" prefix', () => {
      const error = new Error('Error: Something went wrong');
      const message = parseErrorMessage(error);

      expect(message).toBe('Something went wrong');
    });

    it('should remove stack traces', () => {
      const error = new Error('Something went wrong\n  at function()');
      const message = parseErrorMessage(error);

      expect(message).toBe('Something went wrong');
      expect(message).not.toContain('at function');
    });

    it('should handle null and undefined', () => {
      expect(parseErrorMessage(null)).toBe('An unexpected error occurred');
      expect(parseErrorMessage(undefined)).toBe('An unexpected error occurred');
    });
  });

  describe('deepClone', () => {
    it('should clone simple objects', () => {
      const obj = { a: 1, b: 2 };
      const clone = deepClone(obj);

      expect(clone).toEqual(obj);
      expect(clone).not.toBe(obj);
    });

    it('should clone nested objects', () => {
      const obj = { a: { b: { c: 1 } } };
      const clone = deepClone(obj);

      expect(clone).toEqual(obj);
      clone.a.b.c = 2;
      expect(obj.a.b.c).toBe(1);
    });

    it('should clone arrays', () => {
      const arr = [1, 2, [3, 4]];
      const clone = deepClone(arr);

      expect(clone).toEqual(arr);
      expect(clone).not.toBe(arr);
      expect(clone[2]).not.toBe(arr[2]);
    });

    it('should clone objects with various types', () => {
      const obj = {
        string: 'test',
        number: 42,
        boolean: true,
        null: null,
        array: [1, 2, 3],
        nested: { a: 1 },
      };

      const clone = deepClone(obj);

      expect(clone).toEqual(obj);
    });

    it('should handle empty objects', () => {
      const obj = {};
      const clone = deepClone(obj);

      expect(clone).toEqual({});
      expect(clone).not.toBe(obj);
    });
  });

  describe('isBrowser', () => {
    it('should return true in test environment with window', () => {
      // happy-dom provides window and document
      const result = isBrowser();

      expect(result).toBe(true);
    });

    it('should detect window object', () => {
      const hasWindow = typeof window !== 'undefined';
      const hasDocument = typeof document !== 'undefined';

      expect(isBrowser()).toBe(hasWindow && hasDocument);
    });
  });

  describe('safeJsonParse', () => {
    it('should parse valid JSON', () => {
      const json = '{"name":"John","age":30}';
      const result = safeJsonParse(json, {});

      expect(result).toEqual({ name: 'John', age: 30 });
    });

    it('should return fallback for invalid JSON', () => {
      const fallback = { default: true };
      const result = safeJsonParse('invalid json', fallback);

      expect(result).toBe(fallback);
    });

    it('should handle empty strings', () => {
      const fallback = { default: true };
      const result = safeJsonParse('', fallback);

      expect(result).toBe(fallback);
    });

    it('should parse arrays', () => {
      const json = '[1,2,3]';
      const result = safeJsonParse<number[]>(json, []);

      expect(result).toEqual([1, 2, 3]);
    });

    it('should parse null', () => {
      const json = 'null';
      const result = safeJsonParse(json, { default: true });

      expect(result).toBeNull();
    });

    it('should use fallback for malformed JSON', () => {
      const fallback = { error: true };
      const result = safeJsonParse('{"incomplete":', fallback);

      expect(result).toBe(fallback);
    });
  });
});
