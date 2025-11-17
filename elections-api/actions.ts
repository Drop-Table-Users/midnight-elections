import { WalletBuilder } from "@midnight-ntwrk/wallet";
import { createCircuitCallTxInterface } from "@midnight-ntwrk/midnight-js-contracts";
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
import {
  nativeToken,
  Transaction,
  type ContractAddress,
} from "@midnight-ntwrk/ledger";
import { Transaction as ZswapTransaction } from "@midnight-ntwrk/zswap";
import { WebSocket } from "ws";
import * as fs from "fs";
import * as path from "path";
import * as Rx from "rxjs";
import * as config from "./config.js";

type BuiltWallet = Awaited<
  ReturnType<typeof WalletBuilder.buildFromSeed>
>;

type ManagedWallet = BuiltWallet & {
  start(): void;
  close(): Promise<void>;
};

// Fix WebSocket for Node.js
// @ts-ignore
globalThis.WebSocket = WebSocket;

setNetworkId(config.NETWORK);

const HEX_REGEX = /^[0-9a-fA-F]+$/;

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

const normalizeContractAddress = (value: string): string => {
  const trimmed = value.trim();
  if (!trimmed) {
    throw new Error("Contract address cannot be empty.");
  }
  const withoutPrefix = trimmed.startsWith("0x") ? trimmed.slice(2) : trimmed;
  if (!HEX_REGEX.test(withoutPrefix)) {
    throw new Error("Contract address must be a hex string.");
  }
  return withoutPrefix;
};

const loadContractAddress = (override?: string): ContractAddress => {
  if (override) {
    return normalizeContractAddress(override);
  }

  const deploymentPath = path.join(process.cwd(), "deployment.json");
  if (!fs.existsSync(deploymentPath)) {
    throw new Error("deployment.json not found. Deploy the elections contract first.");
  }

  const deployment = JSON.parse(fs.readFileSync(deploymentPath, "utf-8"));
  const addr = deployment.contractAddress;
  if (!addr) {
    throw new Error("deployment.json missing contractAddress.");
  }

  if (typeof addr === "string") {
    return normalizeContractAddress(addr);
  }

  if (typeof addr === "object" && typeof addr.bytes === "string") {
    return normalizeContractAddress(addr.bytes);
  }

  throw new Error("Unsupported contractAddress format in deployment.json.");
};

const buildWallet = async (walletSeed: string): Promise<ManagedWallet> => {
  const wallet = (await WalletBuilder.buildFromSeed(
    config.NETWORK_CONFIG.INDEXER,
    config.NETWORK_CONFIG.INDEXER_WS,
    config.NETWORK_CONFIG.PROOF_SERVER,
    config.NETWORK_CONFIG.NODE,
    walletSeed,
    getZswapNetworkId(),
    "info"
  )) as ManagedWallet;

  wallet.start();
  const state = await Rx.firstValueFrom(wallet.state());
  console.log(`Wallet address: ${state.address}`);

  let balance = state.balances[nativeToken()] || 0n;
  if (balance === 0n) {
    console.log("Wallet has no funds. Waiting for faucet transfer...");
    balance = await waitForFunds(wallet);
  }
  console.log(`Balance: ${balance}`);

  return wallet;
};

const createWalletProvider = async (wallet: ManagedWallet) => {
  const walletState = await Rx.firstValueFrom(wallet.state());
  return {
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
};

const loadElectionsContractModule = async () => {
  const contractPath = path.join(process.cwd(), "contracts");
  const contractModulePath = path.join(
    contractPath,
    "managed",
    config.CONTRACT_CONFIG.CONTRACT_NAME,
    "contract",
    "index.cjs"
  );

  if (!fs.existsSync(contractModulePath)) {
    throw new Error("Contract artifact not found. Run npm run compile:elections");
  }

  return import(contractModulePath);
};

const createProviders = async (walletProvider: any) => {
  const contractPath = path.join(process.cwd(), "contracts");
  const zkConfigPath = path.join(
    contractPath,
    "managed",
    config.CONTRACT_CONFIG.CONTRACT_NAME
  );

  return {
    privateStateProvider: levelPrivateStateProvider({
      privateStateStoreName: config.CONTRACT_CONFIG.CONTRACT_STATE_NAME,
    }),
    publicDataProvider: indexerPublicDataProvider(
      config.NETWORK_CONFIG.INDEXER,
      config.NETWORK_CONFIG.INDEXER_WS
    ),
    zkConfigProvider: new NodeZkConfigProvider(zkConfigPath),
    proofProvider: httpClientProofProvider(config.NETWORK_CONFIG.PROOF_SERVER),
    walletProvider,
    midnightProvider: walletProvider,
  };
};

export interface ActionOptions {
  walletSeed?: string;
  contractAddress?: string;
}

export interface ActionResult {
  txHash: string;
  blockHeight: number;
  explorerUrl: string;
}

const performAction = async (action: "open" | "close", options: ActionOptions) => {
  const walletSeed = options.walletSeed ?? config.WALLET_SEED;
  if (!walletSeed) {
    throw new Error("A wallet seed is required.");
  }

  const wallet = await buildWallet(walletSeed);
  try {
    const walletProvider = await createWalletProvider(wallet);
    const providers = await createProviders(walletProvider);
    const contractAddress = loadContractAddress(options.contractAddress);

    const contractState =
      await providers.publicDataProvider.queryContractState(contractAddress);
    if (!contractState) {
      throw new Error(
        `No contract state found at ${contractAddress}. Ensure deployment.json is up-to-date and the transaction finalized.`
      );
    }

    const ElectionsModule = await loadElectionsContractModule();
    const witnesses = {
      get_ballot: ({ privateState }: any) => [privateState, createEmptyBallot()],
    };
    const contractInstance = new ElectionsModule.Contract(witnesses);

    const txInterface = createCircuitCallTxInterface(
      providers as any,
      contractInstance as any,
      contractAddress,
      config.CONTRACT_CONFIG.CONTRACT_STATE_ID
    ) as any;

    const result =
      action === "open"
        ? await txInterface.open_election()
        : await txInterface.close_election();

    const txHash = result.public.txHash;
    const blockHeight = result.public.blockHeight;
    const explorerUrl = `${config.MEXPLORER_URL}/transaction/0x${txHash}/${blockHeight}`;

    return {
      txHash,
      blockHeight,
      explorerUrl,
    };
  } finally {
    await wallet.close();
  }
};

export const openElection = (options: ActionOptions = {}): Promise<ActionResult> =>
  performAction("open", options);

export const closeElection = (options: ActionOptions = {}): Promise<ActionResult> =>
  performAction("close", options);
