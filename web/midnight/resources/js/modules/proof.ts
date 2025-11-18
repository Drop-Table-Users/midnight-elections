/**
 * Proof Module
 *
 * Handles zero-knowledge proof generation and verification
 */

import axios from 'axios';
import type { MidnightConfig, ProofRequest, ProofResponse, Proof } from '../types';

export class ProofModule {
    private config: MidnightConfig;

    constructor(config: MidnightConfig) {
        this.config = config;
    }

    /**
     * Generate a zero-knowledge proof
     */
    async generate(request: ProofRequest): Promise<ProofResponse> {
        try {
            const response = await axios.post(`${this.getApiUrl()}/proofs/generate`, {
                circuit: request.circuit,
                inputs: request.inputs
            });

            return {
                proof: response.data.proof,
                publicInputs: response.data.publicInputs
            };
        } catch (error) {
            console.error('Failed to generate proof:', error);
            throw new Error('Failed to generate zero-knowledge proof');
        }
    }

    /**
     * Verify a zero-knowledge proof
     */
    async verify(proof: Proof): Promise<boolean> {
        try {
            const response = await axios.post(`${this.getApiUrl()}/proofs/verify`, {
                proof
            });

            return response.data.valid;
        } catch (error) {
            console.error('Failed to verify proof:', error);
            throw new Error('Failed to verify zero-knowledge proof');
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
