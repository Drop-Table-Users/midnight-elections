/**
 * Client Module
 *
 * Handles network queries and transaction lookups
 */

import axios from 'axios';
import type { MidnightConfig, NetworkInfo, Transaction } from '../types';

export class ClientModule {
    private config: MidnightConfig;

    constructor(config: MidnightConfig) {
        this.config = config;
    }

    /**
     * Get network information
     */
    async getNetworkInfo(): Promise<NetworkInfo> {
        try {
            const response = await axios.get(`${this.getApiUrl()}/network/info`);

            return {
                name: response.data.name,
                chainId: response.data.chainId,
                blockHeight: response.data.blockHeight
            };
        } catch (error) {
            console.error('Failed to get network info:', error);
            throw new Error('Failed to retrieve network information');
        }
    }

    /**
     * Get current block height
     */
    async getBlockHeight(): Promise<number> {
        try {
            const response = await axios.get(`${this.getApiUrl()}/network/block-height`);
            return response.data.blockHeight;
        } catch (error) {
            console.error('Failed to get block height:', error);
            throw new Error('Failed to retrieve block height');
        }
    }

    /**
     * Get transaction by hash
     */
    async getTransaction(hash: string): Promise<Transaction> {
        try {
            const response = await axios.get(`${this.getApiUrl()}/transactions/${hash}`);

            return {
                hash: response.data.hash,
                from: response.data.from,
                to: response.data.to,
                value: response.data.value,
                status: response.data.status,
                blockNumber: response.data.blockNumber
            };
        } catch (error) {
            console.error('Failed to get transaction:', error);
            throw new Error('Failed to retrieve transaction');
        }
    }

    /**
     * Cleanup resources
     */
    destroy(): void {
        // No cleanup needed for this module
    }

    /**
     * Get the API URL from config
     */
    private getApiUrl(): string {
        return this.config.apiUrl || '/api/midnight';
    }
}
