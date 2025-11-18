/**
 * Wallet Module
 *
 * Handles wallet connection, disconnection, and balance queries
 */

import axios from 'axios';
import type { MidnightConfig, WalletConnection } from '../types';

export class WalletModule {
    private config: MidnightConfig;
    private connection: WalletConnection | null = null;

    constructor(config: MidnightConfig) {
        this.config = config;
    }

    /**
     * Connect to a Midnight wallet
     */
    async connect(): Promise<WalletConnection> {
        try {
            const response = await axios.post(`${this.getApiUrl()}/wallet/connect`, {
                network: this.config.network || 'testnet'
            });

            this.connection = {
                address: response.data.address,
                publicKey: response.data.publicKey,
                connected: true
            };

            return this.connection;
        } catch (error) {
            console.error('Failed to connect wallet:', error);
            throw new Error('Failed to connect to Midnight wallet');
        }
    }

    /**
     * Disconnect from the wallet
     */
    async disconnect(): Promise<void> {
        try {
            if (this.connection) {
                await axios.post(`${this.getApiUrl()}/wallet/disconnect`);
                this.connection = null;
            }
        } catch (error) {
            console.error('Failed to disconnect wallet:', error);
            throw new Error('Failed to disconnect from Midnight wallet');
        }
    }

    /**
     * Get the current wallet address
     */
    async getAddress(): Promise<string | null> {
        return this.connection?.address || null;
    }

    /**
     * Get the wallet balance
     */
    async getBalance(): Promise<string> {
        try {
            if (!this.connection) {
                throw new Error('Wallet not connected');
            }

            const response = await axios.get(
                `${this.getApiUrl()}/wallet/${this.connection.address}/balance`
            );

            return response.data.balance;
        } catch (error) {
            console.error('Failed to get balance:', error);
            throw new Error('Failed to retrieve wallet balance');
        }
    }

    /**
     * Cleanup resources
     */
    destroy(): void {
        this.connection = null;
    }

    /**
     * Get the API URL from config
     */
    private getApiUrl(): string {
        return this.config.apiUrl || '/api/midnight';
    }
}
