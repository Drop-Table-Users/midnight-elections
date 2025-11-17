import { WalletBuilder } from "@midnight-ntwrk/wallet";
import { deployContract } from "@midnight-ntwrk/midnight-js-contracts";
import { httpClientProofProvider } from "@midnight-ntwrk/midnight-js-http-client-proof-provider";
import { indexerPublicDataProvider } from "@midnight-ntwrk/midnight-js-indexer-public-data-provider";
import { NodeZkConfigProvider } from "@midnight-ntwrk/midnight-js-node-zk-config-provider";
import { levelPrivateStateProvider } from "@midnight-ntwrk/midnight-js-level-private-state-provider";
import {
  setNetworkId,
  getZswapNetworkId,
  getLedgerNetworkId,
} from "@midnight-ntwrk/midnight-js-network-id";
import { createBalancedTx } from "@midnight-ntwrk/midnight-js-types";
import { nativeToken, Transaction } from "@midnight-ntwrk/ledger";
import { Transaction as ZswapTransaction } from "@midnight-ntwrk/zswap";
import { WebSocket } from "ws";
import * as fs from "fs";
import * as path from "path";
import * as Rx from "rxjs";
import * as config from "./config.js";

const HEX_REGEX = /^[0-9a-fA-F]+$/;

type BuiltWallet = Awaited<
  ReturnType<typeof WalletBuilder.buildFromSeed>
>;

const hexToBytes = (hex: string): Uint8Array => {
  const normalized = hex.trim().toLowerCase();
  const withoutPrefix = normalized.startsWith("0x")
    ? normalized.slice(2)
    : normalized;
  if (!HEX_REGEX.test(withoutPrefix) || withoutPrefix.length !== 64) {
    throw new Error(
      "ELECTION_ID must be a 64-character hex string (32 bytes)."
    );
  }
  const bytes = new Uint8Array(withoutPrefix.length / 2);
  for (let i = 0; i < withoutPrefix.length; i += 2) {
    bytes[i / 2] = parseInt(withoutPrefix.slice(i, i + 2), 16);
  }
  return bytes;
};

const createEmptyBallot = () => ({
  election_id: new Uint8Array(32),
  candidate_id: new Uint8Array(32),
  credential: {
    subject: {
      id: new Uint8Array(32),
      first_name: new Uint8Array(32),
      last_name: new Uint8Array(32),
      national_identifier: new Uint8Array(32),
      birth_timestamp: 0n,
    },
    signature: {
      pk: { x: 0n, y: 0n },
      R: { x: 0n, y: 0n },
      s: 0n,
    },
  },
});

// Fix WebSocket for Node.js environment
// @ts-ignore
globalThis.WebSocket = WebSocket;

// Configure for Midnight Testnet
setNetworkId(config.NETWORK);

const waitForFunds = (wallet: BuiltWallet) =>
  Rx.firstValueFrom(
    wallet.state().pipe(
      Rx.tap((state) => {
        if (state.syncProgress) {
          console.log(
            `Sync progress: synced=${state.syncProgress.synced}, sourceGap=${state.syncProgress.lag.sourceGap}, applyGap=${state.syncProgress.lag.applyGap}`
          );
        }
      }),
      Rx.filter((state) => state.syncProgress?.synced === true),
      Rx.map((s) => s.balances[nativeToken()] ?? 0n),
      Rx.filter((balance) => balance > 0n),
      Rx.tap((balance) => console.log(`Wallet funded with balance: ${balance}`))
    )
  );

export interface DeployOptions {
  walletSeed?: string;
  electionId?: string;
}

export interface DeployResult {
  contractAddress: string;
  blockHeight: number;
  transactionHash: string;
  explorerUrl: string;
  walletAddress: string;
}

export const deployElectionsContract = async (
  options: DeployOptions = {}
): Promise<DeployResult> => {
  const walletSeed = options.walletSeed ?? config.WALLET_SEED;
  const electionId =
    options.electionId ?? config.ELECTION_CONSTRUCTOR_ARGS._election_id;

  if (!walletSeed) {
    throw new Error("A wallet seed is required to deploy the contract.");
  }

  const contractPath = path.join(process.cwd(), "contracts");
  const contractModulePath = path.join(
    contractPath,
    "managed",
    config.CONTRACT_CONFIG.CONTRACT_NAME,
    "contract",
    "index.cjs"
  );

  if (!fs.existsSync(contractModulePath)) {
    throw new Error("Contract not found! Run: npm run compile");
  }

  const ElectionsModule = await import(contractModulePath);
  const witnesses = {
    get_ballot: ({ privateState }: any) => [privateState, createEmptyBallot()],
  };
  const contractInstance = new ElectionsModule.Contract(witnesses);
  const constructorArgs = [
    config.ELECTION_CONSTRUCTOR_ARGS._trusted_issuer_public_key,
    hexToBytes(electionId),
  ];

  let wallet: BuiltWallet | null = null;
  try {
    console.log("Building wallet...");
    wallet = await WalletBuilder.buildFromSeed(
      config.NETWORK_CONFIG.INDEXER,
      config.NETWORK_CONFIG.INDEXER_WS,
      config.NETWORK_CONFIG.PROOF_SERVER,
      config.NETWORK_CONFIG.NODE,
      walletSeed,
      getZswapNetworkId(),
      "info"
    );

    wallet.start();
    const state = await Rx.firstValueFrom(wallet.state());

    console.log(`Wallet address: ${state.address}`);

    let balance = state.balances[nativeToken()] || 0n;

    if (balance === 0n) {
      console.log("Wallet has no funds. Waiting for faucet transfer...");
      balance = await waitForFunds(wallet);
    }

    console.log(`Balance: ${balance}`);

    const walletState = await Rx.firstValueFrom(wallet.state());

    const walletProvider = {
      coinPublicKey: walletState.coinPublicKey,
      encryptionPublicKey: walletState.encryptionPublicKey,
      balanceTx(tx: any, newCoins: any) {
        return wallet!
          .balanceTransaction(
            ZswapTransaction.deserialize(
              tx.serialize(getLedgerNetworkId()),
              getZswapNetworkId()
            ),
            newCoins
          )
          .then((tx) => wallet!.proveTransaction(tx))
          .then((zswapTx) =>
            Transaction.deserialize(
              zswapTx.serialize(getZswapNetworkId()),
              getLedgerNetworkId()
            )
          )
          .then(createBalancedTx);
      },
      submitTx(tx: any) {
        return wallet!.submitTransaction(tx);
      },
    };

    const zkConfigPath = path.join(
      contractPath,
      "managed",
      config.CONTRACT_CONFIG.CONTRACT_NAME
    );
    const providers = {
      privateStateProvider: levelPrivateStateProvider({
        privateStateStoreName: config.CONTRACT_CONFIG.CONTRACT_STATE_NAME,
      }),
      publicDataProvider: indexerPublicDataProvider(
        config.NETWORK_CONFIG.INDEXER,
        config.NETWORK_CONFIG.INDEXER_WS
      ),
      zkConfigProvider: new NodeZkConfigProvider(zkConfigPath),
      proofProvider: httpClientProofProvider(
        config.NETWORK_CONFIG.PROOF_SERVER
      ),
      walletProvider,
      midnightProvider: walletProvider,
    };

    console.log("Deploying contract (30-60 seconds)...");

    const deployed = await deployContract(providers as any, {
      contract: contractInstance,
      privateStateId: config.CONTRACT_CONFIG.CONTRACT_STATE_ID,
      initialPrivateState: {},
      args: constructorArgs,
    } as any);

    const contractAddress = deployed.deployTxData.public.contractAddress;
    const blockHeight = deployed.deployTxData.public.blockHeight;
    const transactionHash = deployed.deployTxData.public.txHash;

    console.log("Deployment completed.");
    console.log(`Contract: ${contractAddress}`);

    const explorerUrl = `${config.MEXPLORER_URL}/transaction/0x${transactionHash}/${blockHeight}`;
    const info = {
      contractAddress,
      blockHeight,
      transactionHash,
      explorerUrl,
      walletAddress: state.address,
      deployedAt: new Date().toISOString(),
      blockNumber: new Date().toISOString(),
    };

    fs.writeFileSync("deployment.json", JSON.stringify(info, null, 2));

    return {
      contractAddress,
      blockHeight,
      transactionHash,
      explorerUrl,
      walletAddress: state.address,
    };
  } catch (error) {
    console.error("Deployment failed", error);
    throw error;
  } finally {
    if (wallet) {
      await wallet.close();
    }
  }
};
