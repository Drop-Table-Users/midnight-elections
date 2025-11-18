/**
 * Type definitions for the Midnight Laravel package
 */

export interface MidnightConfig {
    apiUrl?: string;
    network?: 'mainnet' | 'testnet' | 'devnet';
    autoInit?: boolean;
    debug?: boolean;
}

export interface MidnightAPI {
    wallet: WalletModule;
    client: ClientModule;
    contract: ContractModule;
    proof: ProofModule;
    config: MidnightConfig;
    version: string;
}

export interface WalletModule {
    connect(): Promise<WalletConnection>;
    disconnect(): Promise<void>;
    getAddress(): Promise<string | null>;
    getBalance(): Promise<string>;
    destroy?(): void;
}

export interface ClientModule {
    getNetworkInfo(): Promise<NetworkInfo>;
    getBlockHeight(): Promise<number>;
    getTransaction(hash: string): Promise<Transaction>;
    destroy?(): void;
}

export interface ContractModule {
    deploy(bytecode: string): Promise<ContractDeployment>;
    call(address: string, method: string, params: any[]): Promise<ContractCallResult>;
    destroy?(): void;
}

export interface ProofModule {
    generate(request: ProofRequest): Promise<ProofResponse>;
    verify(proof: Proof): Promise<boolean>;
    destroy?(): void;
}

export interface WalletConnection {
    address: string;
    publicKey: string;
    connected: boolean;
}

export interface NetworkInfo {
    name: string;
    chainId: number;
    blockHeight: number;
}

export interface Transaction {
    hash: string;
    from: string;
    to: string;
    value: string;
    status: 'pending' | 'confirmed' | 'failed';
    blockNumber?: number;
}

export interface ContractDeployment {
    address: string;
    transactionHash: string;
}

export interface ContractCallResult {
    success: boolean;
    result: any;
    transactionHash?: string;
}

export interface ProofRequest {
    circuit: string;
    inputs: Record<string, any>;
}

export interface ProofResponse {
    proof: Proof;
    publicInputs: any[];
}

export interface Proof {
    pi_a: string[];
    pi_b: string[][];
    pi_c: string[];
    protocol: string;
}
