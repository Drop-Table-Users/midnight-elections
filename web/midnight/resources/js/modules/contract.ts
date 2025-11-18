/**
 * Contract Module
 *
 * Handles smart contract deployment and interaction
 */

import axios from 'axios';
import type { MidnightConfig, ContractDeployment, ContractCallResult } from '../types';

export class ContractModule {
    private config: MidnightConfig;

    constructor(config: MidnightConfig) {
        this.config = config;
    }

    /**
     * Deploy a smart contract
     */
    async deploy(bytecode: string): Promise<ContractDeployment> {
        try {
            const response = await axios.post(`${this.getApiUrl()}/contracts/deploy`, {
                bytecode
            });

            return {
                address: response.data.address,
                transactionHash: response.data.transactionHash
            };
        } catch (error) {
            console.error('Failed to deploy contract:', error);
            throw new Error('Failed to deploy smart contract');
        }
    }

    /**
     * Call a smart contract method
     */
    async call(address: string, method: string, params: any[]): Promise<ContractCallResult> {
        try {
            const response = await axios.post(`${this.getApiUrl()}/contracts/${address}/call`, {
                method,
                params
            });

            return {
                success: response.data.success,
                result: response.data.result,
                transactionHash: response.data.transactionHash
            };
        } catch (error) {
            console.error('Failed to call contract:', error);
            throw new Error('Failed to call smart contract method');
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
