/**
 * Midnight Laravel Package - Main Entry Point
 *
 * This is the primary entry point for the Midnight blockchain integration
 * JavaScript/TypeScript library. It exports the public API and handles
 * auto-initialization when included in a Laravel application.
 */

// Import core modules
import { WalletModule } from './modules/wallet';
import { ClientModule } from './modules/client';
import { ContractModule } from './modules/contract';
import { ProofModule } from './modules/proof';

// Import styles
import '@css/midnight.css';

// Import types
import type { MidnightConfig, MidnightAPI } from './types';

/**
 * Global Midnight API instance
 */
let midnightInstance: MidnightAPI | null = null;

/**
 * Initialize the Midnight API
 *
 * @param config - Configuration options
 * @returns The Midnight API instance
 */
export function initializeMidnight(config: MidnightConfig = {}): MidnightAPI {
    if (midnightInstance) {
        console.warn('Midnight API already initialized. Returning existing instance.');
        return midnightInstance;
    }

    // Create API instance
    midnightInstance = {
        wallet: new WalletModule(config),
        client: new ClientModule(config),
        contract: new ContractModule(config),
        proof: new ProofModule(config),
        config,
        version: '1.0.0'
    };

    // Expose to window for global access
    if (typeof window !== 'undefined') {
        (window as any).Midnight = midnightInstance;
    }

    return midnightInstance;
}

/**
 * Get the current Midnight API instance
 *
 * @returns The Midnight API instance or null if not initialized
 */
export function getMidnight(): MidnightAPI | null {
    return midnightInstance;
}

/**
 * Destroy the Midnight API instance
 */
export function destroyMidnight(): void {
    if (midnightInstance) {
        // Cleanup resources
        midnightInstance.wallet.destroy?.();
        midnightInstance.client.destroy?.();
        midnightInstance.contract.destroy?.();
        midnightInstance.proof.destroy?.();

        midnightInstance = null;

        if (typeof window !== 'undefined') {
            delete (window as any).Midnight;
        }
    }
}

// Export modules for tree-shaking
export { WalletModule } from './modules/wallet';
export { ClientModule } from './modules/client';
export { ContractModule } from './modules/contract';
export { ProofModule } from './modules/proof';

// Export types
export type * from './types';

// Export utilities
export * from './utils';

// Auto-initialize if config is present on window
if (typeof window !== 'undefined') {
    const config = (window as any).midnightConfig;

    if (config?.autoInit !== false) {
        // Auto-initialize on DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initializeMidnight(config);
            });
        } else {
            initializeMidnight(config);
        }
    }
}

// Default export
export default {
    initialize: initializeMidnight,
    get: getMidnight,
    destroy: destroyMidnight,
    WalletModule,
    ClientModule,
    ContractModule,
    ProofModule
};
