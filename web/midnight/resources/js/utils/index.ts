/**
 * Utility functions for the Midnight Laravel package
 */

/**
 * Format a blockchain address for display
 */
export function formatAddress(address: string, length: number = 8): string {
    if (address.length <= length * 2) {
        return address;
    }

    const start = address.slice(0, length);
    const end = address.slice(-length);
    return `${start}...${end}`;
}

/**
 * Format a transaction hash for display
 */
export function formatTxHash(hash: string, length: number = 8): string {
    return formatAddress(hash, length);
}

/**
 * Format a balance value
 */
export function formatBalance(balance: string | number, decimals: number = 6): string {
    const num = typeof balance === 'string' ? parseFloat(balance) : balance;
    return num.toFixed(decimals);
}

/**
 * Convert wei to ether (or equivalent)
 */
export function weiToEther(wei: string | number): string {
    const weiNum = typeof wei === 'string' ? BigInt(wei) : BigInt(wei);
    const etherNum = Number(weiNum) / 1e18;
    return etherNum.toFixed(6);
}

/**
 * Convert ether to wei (or equivalent)
 */
export function etherToWei(ether: string | number): string {
    const etherNum = typeof ether === 'string' ? parseFloat(ether) : ether;
    const weiNum = BigInt(Math.floor(etherNum * 1e18));
    return weiNum.toString();
}

/**
 * Validate a blockchain address
 */
export function isValidAddress(address: string): boolean {
    // Basic validation - can be enhanced based on Midnight's address format
    return /^0x[a-fA-F0-9]{40}$/.test(address) || /^[a-zA-Z0-9]{32,64}$/.test(address);
}

/**
 * Validate a transaction hash
 */
export function isValidTxHash(hash: string): boolean {
    return /^0x[a-fA-F0-9]{64}$/.test(hash) || /^[a-fA-F0-9]{64}$/.test(hash);
}

/**
 * Sleep utility for async operations
 */
export function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Debounce function
 */
export function debounce<T extends (...args: any[]) => any>(
    func: T,
    wait: number
): (...args: Parameters<T>) => void {
    let timeout: NodeJS.Timeout | null = null;

    return function executedFunction(...args: Parameters<T>) {
        const later = () => {
            timeout = null;
            func(...args);
        };

        if (timeout) {
            clearTimeout(timeout);
        }
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
export function throttle<T extends (...args: any[]) => any>(
    func: T,
    limit: number
): (...args: Parameters<T>) => void {
    let inThrottle: boolean;

    return function executedFunction(...args: Parameters<T>) {
        if (!inThrottle) {
            func(...args);
            inThrottle = true;
            setTimeout(() => (inThrottle = false), limit);
        }
    };
}

/**
 * Copy text to clipboard
 */
export async function copyToClipboard(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch (err) {
        console.error('Failed to copy to clipboard:', err);
        return false;
    }
}

/**
 * Generate a random ID
 */
export function generateId(prefix: string = 'mid'): string {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}
