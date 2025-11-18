import { createCircuitCallTxInterface } from '@midnight-ntwrk/midnight-js-contracts';
import { httpClientProofProvider } from '@midnight-ntwrk/midnight-js-http-client-proof-provider';
import { indexerPublicDataProvider } from '@midnight-ntwrk/midnight-js-indexer-public-data-provider';
import { NodeZkConfigProvider } from '@midnight-ntwrk/midnight-js-node-zk-config-provider';
import { levelPrivateStateProvider } from '@midnight-ntwrk/midnight-js-level-private-state-provider';
import { getZswapNetworkId, getLedgerNetworkId } from '@midnight-ntwrk/midnight-js-network-id';
import { createBalancedTx } from '@midnight-ntwrk/midnight-js-types';
import { Transaction, type ContractAddress } from '@midnight-ntwrk/ledger';
import { Transaction as ZswapTransaction } from '@midnight-ntwrk/zswap';
import * as Rx from 'rxjs';
import * as path from 'path';
import * as fs from 'fs';
import config from '../config/midnight.js';
import { walletService } from './WalletService.js';
export class ContractService {
  private contractModule: any = null;
  private contractInstance: any = null;
  private txInterface: any = null;
  private isInitialized = false;
  private ballotWitness: Ballot | undefined;

  async initialize(): Promise<void> {
    if (this.isInitialized) {
      console.log('Contract service already initialized');
      return;
    }

    try {
      console.log('Initializing contract service...');
      const contractPath = config.contractPath || path.join(process.cwd(), '..', '..', 'contract');
      const contractModulePath = path.join(
        contractPath,
        'contracts',
        'managed',
        'elections',
        'contract',
        'index.cjs'
      );

      console.log(`Loading contract from: ${contractModulePath}`);

      if (!fs.existsSync(contractModulePath)) {
        throw new Error(
          `Contract artifact not found at ${contractModulePath}. ` +
          'Please compile the elections contract first (npm run compile:elections in contract directory).'
        );
      }
      this.contractModule = await import(contractModulePath);
      const witnesses = {
        get_ballot: ({ privateState }: any) => {
          if (!this.ballotWitness) {
            throw new Error('Ballot witness requested but no ballot data was provided. Provide ballot data for vote action.');
          }
          const ballot = this.ballotWitness;
          this.ballotWitness = undefined;
          return [privateState, ballot];
        },
      };
      this.contractInstance = new this.contractModule.Contract(witnesses);
      const wallet = walletService.getWallet();
      const walletState = await Rx.firstValueFrom(wallet.state());
      const walletProvider = {
        coinPublicKey: walletState.coinPublicKey,
        encryptionPublicKey: walletState.encryptionPublicKey,
        balanceTx(tx: any, newCoins: any) {
          return wallet
            .balanceTransaction(
              ZswapTransaction.deserialize(
                tx.serialize(getLedgerNetworkId()),
                getZswapNetworkId()
              ),
              newCoins
            )
            .then((balancedTx) => wallet.proveTransaction(balancedTx))
            .then((zswapTx) =>
              Transaction.deserialize(
                zswapTx.serialize(getZswapNetworkId()),
                getLedgerNetworkId()
              )
            )
            .then(createBalancedTx);
        },
        submitTx(tx: any) {
          return wallet.submitTransaction(tx);
        },
      };
      const zkConfigPath = path.join(contractPath, 'contracts', 'managed', 'elections');

      const providers = {
        privateStateProvider: levelPrivateStateProvider({
          privateStateStoreName: config.contractStateStoreName || 'elections-private-state',
        }),
        publicDataProvider: indexerPublicDataProvider(
          config.networkConfig.indexer,
          config.networkConfig.indexerWs
        ),
        zkConfigProvider: new NodeZkConfigProvider(zkConfigPath),
        proofProvider: httpClientProofProvider(config.networkConfig.proofServer),
        walletProvider,
        midnightProvider: walletProvider,
      };
      const deploymentPath = path.join(contractPath, 'deployment.json');
      if (!fs.existsSync(deploymentPath)) {
        throw new Error(
          `deployment.json not found at ${deploymentPath}. ` +
          'Please deploy the elections contract first.'
        );
      }

      const deployment = JSON.parse(fs.readFileSync(deploymentPath, 'utf-8'));
      const contractAddress: ContractAddress = this.normalizeContractAddress(deployment.contractAddress);
      const contractState = await providers.publicDataProvider.queryContractState(contractAddress);
      if (!contractState) {
        throw new Error(
          `No contract state found at ${contractAddress}. ` +
          'Ensure deployment.json points to your elections contract and the deployment transaction has finalized.'
        );
      }
      this.txInterface = createCircuitCallTxInterface(
        providers as any,
        this.contractInstance as any,
        contractAddress,
        config.contractStateId || 'elections-state'
      ) as any;

      this.isInitialized = true;
      console.log('Contract service initialized successfully');
      console.log(`Contract address: ${contractAddress}`);
    } catch (error) {
      console.error('Failed to initialize contract service:', error);
      throw error;
    }
  }

  private normalizeContractAddress(addr: any): string {
    if (typeof addr === 'string') {
      const trimmed = addr.trim();
      return trimmed.startsWith('0x') ? trimmed.slice(2) : trimmed;
    }
    if (typeof addr === 'object' && typeof addr.bytes === 'string') {
      const trimmed = addr.bytes.trim();
      return trimmed.startsWith('0x') ? trimmed.slice(2) : trimmed;
    }
    throw new Error('Unsupported contractAddress format in deployment.json.');
  }

  async openElection(): Promise<any> {
    if (!this.isInitialized || !this.txInterface) {
      throw new Error('Contract service not initialized');
    }

    console.log('Opening election...');
    const result = await this.txInterface.open_election();
    console.log(`Election opened. TxHash: 0x${result.public.txHash}, Block: ${result.public.blockHeight}`);

    return {
      action: 'open',
      txHash: result.public.txHash,
      blockHeight: result.public.blockHeight,
      timestamp: new Date().toISOString(),
      walletAddress: await walletService.getAddress(),
    };
  }

  async closeElection(): Promise<any> {
    if (!this.isInitialized || !this.txInterface) {
      throw new Error('Contract service not initialized');
    }

    console.log('Closing election...');
    const result = await this.txInterface.close_election();
    console.log(`Election closed. TxHash: 0x${result.public.txHash}, Block: ${result.public.blockHeight}`);

    return {
      action: 'close',
      txHash: result.public.txHash,
      blockHeight: result.public.blockHeight,
      timestamp: new Date().toISOString(),
      walletAddress: await walletService.getAddress(),
    };
  }

  async registerCandidate(candidateId: Uint8Array): Promise<any> {
    if (!this.isInitialized || !this.txInterface) {
      throw new Error('Contract service not initialized');
    }

    console.log('Registering candidate...');
    const result = await this.txInterface.register_candidate(candidateId);
    console.log(`Candidate registered. TxHash: 0x${result.public.txHash}, Block: ${result.public.blockHeight}`);

    return {
      action: 'register',
      candidateId: Buffer.from(candidateId).toString('hex'),
      txHash: result.public.txHash,
      blockHeight: result.public.blockHeight,
      timestamp: new Date().toISOString(),
      walletAddress: await walletService.getAddress(),
    };
  }

  async castVote(ballot: Ballot): Promise<any> {
    if (!this.isInitialized || !this.txInterface) {
      throw new Error('Contract service not initialized');
    }
    this.ballotWitness = ballot;

    console.log('Casting vote...');
    const result = await this.txInterface.cast_vote();
    console.log(`Vote cast. TxHash: 0x${result.public.txHash}, Block: ${result.public.blockHeight}`);

    return {
      action: 'vote',
      txHash: result.public.txHash,
      blockHeight: result.public.blockHeight,
      timestamp: new Date().toISOString(),
      walletAddress: await walletService.getAddress(),
    };
  }

  isReady(): boolean {
    return this.isInitialized;
  }
}
export const contractService = new ContractService();
