import type { EncryptedBallot } from './types';
import { EncryptionError } from './errors';

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

export function shortenTxHash(
  txHash: string,
  prefixLength: number = 8,
  suffixLength: number = 6
): string {
  return formatAddress(txHash, prefixLength, suffixLength);
}

export function validateContractAddress(address: string): boolean {
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

export function validateTxHash(txHash: string): boolean {
  if (!txHash || typeof txHash !== 'string') {
    return false;
  }

  const cleanHash = txHash.replace(/^0x/, '');
  const hexPattern = /^[0-9a-fA-F]{64}$/;

  return hexPattern.test(cleanHash);
}


export function bytesToHex(bytes: Uint8Array, prefix: boolean = true): string {
  const hex = Array.from(bytes)
    .map((byte) => byte.toString(16).padStart(2, '0'))
    .join('');

  return prefix ? `0x${hex}` : hex;
}

export function hexToBytes(hex: string): Uint8Array {
  const cleanHex = hex.replace(/^0x/, '');

  if (cleanHex.length % 2 !== 0) {
    throw new Error('Invalid hex string: odd length');
  }

  const bytes = new Uint8Array(cleanHex.length / 2);
  for (let i = 0; i < cleanHex.length; i += 2) {
    bytes[i / 2] = parseInt(cleanHex.slice(i, i + 2), 16);
  }

  return bytes;
}

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

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

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

export function deepClone<T>(obj: T): T {
  if (typeof structuredClone === 'function') {
    return structuredClone(obj);
  }
  return JSON.parse(JSON.stringify(obj));
}

export function isBrowser(): boolean {
  return typeof window !== 'undefined' && typeof document !== 'undefined';
}

export function safeJsonParse<T>(json: string, fallback: T): T {
  try {
    return JSON.parse(json) as T;
  } catch {
    return fallback;
  }
}
