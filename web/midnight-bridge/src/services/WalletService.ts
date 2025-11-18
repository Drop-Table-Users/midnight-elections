import { WalletBuilder } from '@midnight-ntwrk/wallet';
import { NetworkId, getZswapNetworkId, setNetworkId } from '@midnight-ntwrk/midnight-js-network-id';
import { nativeToken } from '@midnight-ntwrk/ledger';
import { type Wallet } from '@midnight-ntwrk/wallet-api';
import * as Rx from 'rxjs';
import { WebSocket } from 'ws';
import config from '../config/midnight.js';
type ManagedWallet = Wallet & {
  start(): void;
  close(): Promise<void>;
};
(globalThis as any).WebSocket = WebSocket;

export class WalletService {
  private wallet: ManagedWallet | null = null;
  private isInitialized = false;

  async initialize(): Promise<void> {
    if (this.isInitialized) {
      console.log('Wallet already initialized');
      return;
    }

    try {
      console.log('Initializing Midnight wallet...');
      const networkId = config.network === 'testnet' ? NetworkId.TestNet :
                        config.network === 'devnet' ? NetworkId.DevNet :
                        NetworkId.MainNet;
      setNetworkId(networkId);
      const builtWallet = await WalletBuilder.buildFromSeed(
        config.networkConfig.indexer,
        config.networkConfig.indexerWs,
        config.networkConfig.proofServer,
        config.networkConfig.node,
        config.walletSeed,
        getZswapNetworkId(),
        config.logLevel as any
      ) as ManagedWallet;

      this.wallet = builtWallet;
      builtWallet.start();
      const state = await Rx.firstValueFrom(builtWallet.state());

      console.log(`Wallet address: ${state.address}`);
      console.log(`Wallet balance: ${state.balances[nativeToken()] || 0n}`);

      this.isInitialized = true;
      console.log('Wallet initialized successfully');
    } catch (error) {
      console.error('Failed to initialize wallet:', error);
      throw error;
    }
  }

  async getAddress(): Promise<string> {
    if (!this.wallet) {
      throw new Error('Wallet not initialized');
    }

    const state = await Rx.firstValueFrom(this.wallet.state());
    return state.address;
  }

  async getBalance(): Promise<bigint> {
    if (!this.wallet) {
      throw new Error('Wallet not initialized');
    }

    const state = await Rx.firstValueFrom(this.wallet.state());
    return state.balances[nativeToken()] || 0n;
  }

  async getState(): Promise<any> {
    if (!this.wallet) {
      throw new Error('Wallet not initialized');
    }

    return await Rx.firstValueFrom(this.wallet.state());
  }

  getWallet(): ManagedWallet {
    if (!this.wallet) {
      throw new Error('Wallet not initialized');
    }
    return this.wallet;
  }

  async close(): Promise<void> {
    if (this.wallet) {

      await this.wallet.close();
      this.wallet = null;
      this.isInitialized = false;
      console.log('Wallet closed');
    }
  }

  isReady(): boolean {
    return this.isInitialized && this.wallet !== null;
  }
}
export const walletService = new WalletService();
