/**
 * Helper utilities module
 *
 * This module provides utility functions for formatting addresses, transaction hashes,
 * validation, and ballot encryption helpers.
 *
 * @module midnight/utils
 */

import type { EncryptedBallot } from './types';
import { EncryptionError } from './errors';

/**
 * Format a Midnight address for display
 *
 * Shortens long addresses to a readable format (e.g., "0x1234...5678")
 *
 * @param address - Full address string
 * @param prefixLength - Number of characters to show at start (default: 6)
 * @param suffixLength - Number of characters to show at end (default: 4)
 * @returns Formatted address string
 *
 * @example
 * ```typescript
 * const formatted = formatAddress('0x1234567890abcdef1234567890abcdef');
 * console.log(formatted); // "0x1234...cdef"
 * ```
 */
export function formatAddress(
  address: string,
  prefixLength: number = 6,
  suffixLength: number = 4
): string {
  if (!address || typeof address !== 'string') {
    return '';
  }

  if (address.length <= prefixLength + suffixLength) {
    return address;
  }

  const prefix = address.slice(0, prefixLength);
  const suffix = address.slice(-suffixLength);

  return `${prefix}...${suffix}`;
}

/**
 * Shorten a transaction hash for display
 *
 * @param txHash - Full transaction hash
 * @param prefixLength - Number of characters to show at start (default: 8)
 * @param suffixLength - Number of characters to show at end (default: 6)
 * @returns Shortened transaction hash
 *
 * @example
 * ```typescript
 * const short = shortenTxHash('0xabcdef1234567890abcdef1234567890');
 * console.log(short); // "0xabcdef...567890"
 * ```
 */
export function shortenTxHash(
  txHash: string,
  prefixLength: number = 8,
  suffixLength: number = 6
): string {
  return formatAddress(txHash, prefixLength, suffixLength);
}

/**
 * Validate Midnight contract address format
 *
 * Checks if an address string matches the expected Midnight address format.
 *
 * @param address - Address to validate
 * @returns True if address is valid
 *
 * @example
 * ```typescript
 * if (validateContractAddress(address)) {
 *   // Proceed with contract call
 * } else {
 *   console.error('Invalid contract address');
 * }
 * ```
 */
export function validateContractAddress(address: string): boolean {
  if (!address || typeof address !== 'string') {
    return false;
  }

  // TODO: Implement actual Midnight address validation rules
  // This is a placeholder implementation
  // Midnight addresses may have specific format requirements:
  // - Specific prefix (e.g., "mn_" or "0x")
  // - Specific length
  // - Checksum validation
  // - Character set validation (hex, base58, etc.)

  // Basic validation: non-empty string with minimum length
  const minLength = 10;
  if (address.length < minLength) {
    return false;
  }

  // Check if it contains only valid characters (assuming hex for now)
  // Remove common prefixes for validation
  const cleanAddress = address.replace(/^(0x|mn_)/, '');
  const hexPattern = /^[0-9a-fA-F]+$/;

  return hexPattern.test(cleanAddress);
}

/**
 * Validate transaction hash format
 *
 * @param txHash - Transaction hash to validate
 * @returns True if transaction hash is valid
 *
 * @example
 * ```typescript
 * if (validateTxHash(hash)) {
 *   await waitForConfirmation(hash);
 * }
 * ```
 */
export function validateTxHash(txHash: string): boolean {
  if (!txHash || typeof txHash !== 'string') {
    return false;
  }

  // TODO: Implement actual Midnight transaction hash validation
  // This is a placeholder implementation
  // Transaction hashes typically:
  // - Have a specific length (e.g., 64 characters for SHA-256)
  // - May have a prefix (e.g., "0x")
  // - Contain only hex characters

  const cleanHash = txHash.replace(/^0x/, '');
  const hexPattern = /^[0-9a-fA-F]{64}$/; // Assuming 256-bit hash

  return hexPattern.test(cleanHash);
}

/**
 * Encrypt a ballot for submission
 *
 * This is a stub/placeholder for ballot encryption functionality.
 * The actual encryption should use Midnight's zero-knowledge proof system
 * and may be handled by the Midnight JS SDK or wallet.
 *
 * @param ballot - Ballot data to encrypt (e.g., candidate ID)
 * @param publicKey - Public encryption key (optional, may come from contract)
 * @returns Promise resolving to encrypted ballot
 *
 * @example
 * ```typescript
 * const encrypted = await encryptBallot({ candidateId: 1 }, publicKey);
 * const txHash = await buildAndSubmitVoteTx({
 *   contractAddress,
 *   candidateId: 1,
 *   encryptedBallot: encrypted.ciphertext
 * });
 * ```
 */
export async function encryptBallot(
  ballot: Record<string, unknown>,
  publicKey?: string | Uint8Array
): Promise<EncryptedBallot> {
  try {
    // TODO: Implement actual ballot encryption using Midnight's ZK proof system
    // This is a placeholder implementation
    //
    // Real implementation might:
    // 1. Use Midnight SDK's encryption functions
    // 2. Generate zero-knowledge proof of correct encryption
    // 3. Include necessary public parameters
    //
    // Example (pseudo-code):
    // import { encryptWithZK } from '@midnight-ntwrk/midnight-js';
    // const { ciphertext, proof } = await encryptWithZK(ballot, publicKey);
    // return { ciphertext, proof };

    console.warn(
      'encryptBallot is a placeholder stub. ' +
      'Replace with actual Midnight ZK encryption when SDK is available.'
    );

    // Placeholder: just stringify the ballot
    const ballotString = JSON.stringify(ballot);
    const encoder = new TextEncoder();
    const ballotBytes = encoder.encode(ballotString);

    return {
      ciphertext: ballotBytes,
      publicParams: publicKey ? { key: publicKey } : undefined,
      proof: undefined, // ZK proof would go here
    };
  } catch (error) {
    if (error instanceof Error) {
      throw new EncryptionError(`Ballot encryption failed: ${error.message}`);
    }
    throw new EncryptionError('Ballot encryption failed');
  }
}

/**
 * Convert Uint8Array to hex string
 *
 * @param bytes - Byte array to convert
 * @param prefix - Whether to add "0x" prefix (default: true)
 * @returns Hex string representation
 *
 * @example
 * ```typescript
 * const bytes = new Uint8Array([1, 2, 3, 255]);
 * const hex = bytesToHex(bytes);
 * console.log(hex); // "0x010203ff"
 * ```
 */
export function bytesToHex(bytes: Uint8Array, prefix: boolean = true): string {
  const hex = Array.from(bytes)
    .map((byte) => byte.toString(16).padStart(2, '0'))
    .join('');

  return prefix ? `0x${hex}` : hex;
}

/**
 * Convert hex string to Uint8Array
 *
 * @param hex - Hex string to convert (with or without "0x" prefix)
 * @returns Byte array
 *
 * @example
 * ```typescript
 * const bytes = hexToBytes('0x010203ff');
 * console.log(bytes); // Uint8Array [1, 2, 3, 255]
 * ```
 */
export function hexToBytes(hex: string): Uint8Array {
  // Remove "0x" prefix if present
  const cleanHex = hex.replace(/^0x/, '');

  // Ensure even length
  if (cleanHex.length % 2 !== 0) {
    throw new Error('Invalid hex string: odd length');
  }

  const bytes = new Uint8Array(cleanHex.length / 2);
  for (let i = 0; i < cleanHex.length; i += 2) {
    bytes[i / 2] = parseInt(cleanHex.slice(i, i + 2), 16);
  }

  return bytes;
}

/**
 * Format timestamp to human-readable date string
 *
 * @param timestamp - Unix timestamp in milliseconds
 * @param options - Intl.DateTimeFormat options
 * @returns Formatted date string
 *
 * @example
 * ```typescript
 * const formatted = formatTimestamp(Date.now());
 * console.log(formatted); // "Nov 15, 2025, 10:30 PM"
 * ```
 */
export function formatTimestamp(
  timestamp: number,
  options?: Intl.DateTimeFormatOptions
): string {
  const defaultOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    ...options,
  };

  return new Date(timestamp).toLocaleString(undefined, defaultOptions);
}

/**
 * Truncate string to maximum length with ellipsis
 *
 * @param str - String to truncate
 * @param maxLength - Maximum length
 * @param ellipsis - Ellipsis string (default: "...")
 * @returns Truncated string
 *
 * @example
 * ```typescript
 * const truncated = truncateString('This is a very long string', 10);
 * console.log(truncated); // "This is..."
 * ```
 */
export function truncateString(
  str: string,
  maxLength: number,
  ellipsis: string = '...'
): string {
  if (!str || str.length <= maxLength) {
    return str;
  }

  return str.slice(0, maxLength - ellipsis.length) + ellipsis;
}

/**
 * Debounce function calls
 *
 * Creates a debounced version of the provided function that delays
 * its execution until after the specified delay has elapsed since
 * the last time it was invoked.
 *
 * @param fn - Function to debounce
 * @param delay - Delay in milliseconds
 * @returns Debounced function
 *
 * @example
 * ```typescript
 * const debouncedSearch = debounce(async (query: string) => {
 *   const results = await searchTransactions(query);
 *   displayResults(results);
 * }, 300);
 *
 * // Call multiple times, only last call executes after 300ms
 * debouncedSearch('tx1');
 * debouncedSearch('tx12');
 * debouncedSearch('tx123');
 * ```
 */
export function debounce<T extends (...args: any[]) => any>(
  fn: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout> | null = null;

  return function (this: any, ...args: Parameters<T>) {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }

    timeoutId = setTimeout(() => {
      fn.apply(this, args);
    }, delay);
  };
}

/**
 * Retry async function with exponential backoff
 *
 * @param fn - Async function to retry
 * @param maxRetries - Maximum number of retry attempts
 * @param baseDelay - Base delay in milliseconds (doubles each retry)
 * @returns Promise resolving to function result
 *
 * @example
 * ```typescript
 * const result = await retryWithBackoff(
 *   () => getTransactionStatus(txHash),
 *   3,
 *   1000
 * );
 * ```
 */
export async function retryWithBackoff<T>(
  fn: () => Promise<T>,
  maxRetries: number = 3,
  baseDelay: number = 1000
): Promise<T> {
  let lastError: Error | null = null;

  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      return await fn();
    } catch (error) {
      lastError = error instanceof Error ? error : new Error('Unknown error');

      if (attempt < maxRetries - 1) {
        const delay = baseDelay * Math.pow(2, attempt);
        await sleep(delay);
      }
    }
  }

  throw lastError || new Error('All retry attempts failed');
}

/**
 * Sleep utility
 *
 * @param ms - Milliseconds to sleep
 * @returns Promise that resolves after delay
 *
 * @internal
 */
function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Parse error message to user-friendly string
 *
 * @param error - Error object or message
 * @returns User-friendly error message
 *
 * @example
 * ```typescript
 * try {
 *   await submitTransaction(tx);
 * } catch (error) {
 *   const message = parseErrorMessage(error);
 *   showNotification(message, 'error');
 * }
 * ```
 */
export function parseErrorMessage(error: unknown): string {
  if (typeof error === 'string') {
    return error;
  }

  if (error instanceof Error) {
    // Clean up common error patterns
    let message = error.message;

    // Remove stack traces
    message = message.split('\n')[0];

    // Remove technical prefixes
    message = message.replace(/^Error:\s*/i, '');

    return message;
  }

  return 'An unexpected error occurred';
}

/**
 * Deep clone an object using structured clone
 *
 * @param obj - Object to clone
 * @returns Cloned object
 *
 * @example
 * ```typescript
 * const original = { votes: [1, 2, 3], metadata: { time: Date.now() } };
 * const copy = deepClone(original);
 * copy.votes.push(4); // Doesn't affect original
 * ```
 */
export function deepClone<T>(obj: T): T {
  // Use structured clone if available (modern browsers)
  if (typeof structuredClone === 'function') {
    return structuredClone(obj);
  }

  // Fallback to JSON parse/stringify (less robust but widely supported)
  return JSON.parse(JSON.stringify(obj));
}

/**
 * Check if code is running in browser environment
 *
 * @returns True if running in browser
 *
 * @example
 * ```typescript
 * if (isBrowser()) {
 *   // Access window, document, etc.
 * }
 * ```
 */
export function isBrowser(): boolean {
  return typeof window !== 'undefined' && typeof document !== 'undefined';
}

/**
 * Safe JSON parse with fallback
 *
 * @param json - JSON string to parse
 * @param fallback - Fallback value if parse fails
 * @returns Parsed object or fallback
 *
 * @example
 * ```typescript
 * const data = safeJsonParse<UserData>(storedData, { name: 'Unknown' });
 * ```
 */
export function safeJsonParse<T>(json: string, fallback: T): T {
  try {
    return JSON.parse(json) as T;
  } catch {
    return fallback;
  }
}
