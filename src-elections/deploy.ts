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
import { fileURLToPath } from "url";
import * as readline from "readline/promises";
import * as Rx from "rxjs";
import { type Wallet } from "@midnight-ntwrk/wallet-api";
import { exit } from "process";
import * as config from "./config.js";

const HEX_REGEX = /^[0-9a-fA-F]+$/;

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

// Generate new wallet seed
function generateSeed() {
  const bytes = new Uint8Array(32);
  // @ts-ignore
  crypto.getRandomValues(bytes);
  const walletSeed = Array.from(bytes, (b) =>
    b.toString(16).padStart(2, "0")
  ).join("");
  console.log(`\nGenerated Seed: ${walletSeed}\n`);
}

const waitForFunds = (wallet: Wallet) =>
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

async function main() {
  console.log("Midnight Elections Deployment\n");

  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
  });

  try {
    // Ask user if they have an existing wallet seed
    const choice = await rl.question("Do you have a wallet seed? (y/n): ");

    let walletSeed: string;
    if (choice.toLowerCase() === "y" || choice.toLowerCase() === "yes") {
      // Use seed from config.ts
      walletSeed = config.WALLET_SEED;
    } else {
      generateSeed();
      exit(0);
    }

    // Build wallet from seed
    console.log("Building wallet...");
    const wallet = await WalletBuilder.buildFromSeed(
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

    console.log(`Your wallet address is: ${state.address}`);

    let balance = state.balances[nativeToken()] || 0n;

    if (balance === 0n) {
      console.log(`Your wallet balance is: 0`);
      console.log(
        "Visit: https://midnight.network/test-faucet to get some funds."
      );
      console.log(`Waiting to receive tokens...`);
      balance = await waitForFunds(wallet);
    }

    console.log(`Balance: ${balance}`);

    // Load compiled contract files
    console.log("Loading contract...");
    // Use import.meta.url to get a proper file:// URL and construct relative path
    const __filename = fileURLToPath(import.meta.url);
    const __dirname = path.dirname(__filename);
    const contractPath = path.join(__dirname, "..", "contracts");
    const contractModulePath = path.join(
      contractPath,
      "managed",
      config.CONTRACT_CONFIG.CONTRACT_NAME,
      "contract",
      "index.cjs"
    );

    if (!fs.existsSync(contractModulePath)) {
      console.error("Contract not found! Run: npm run compile");
      process.exit(1);
    }

    // Convert to file:// URL for import (works in both Windows and WSL)
    const contractModuleUrl = new URL(`file://${contractModulePath.replace(/\\/g, '/')}`);
    const ElectionsModule = await import(contractModuleUrl.href);
    const witnesses = {
      get_ballot: ({ privateState }: any) => [
        privateState,
        createEmptyBallot(),
      ],
    };
    const contractInstance = new ElectionsModule.Contract(witnesses);
    const constructorArgs = [
      config.ELECTION_CONSTRUCTOR_ARGS._trusted_issuer_public_key,
      hexToBytes(config.ELECTION_CONSTRUCTOR_ARGS._election_id),
    ];

    // Create wallet provider for transactions
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
          .then((tx) => wallet.proveTransaction(tx))
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

    // Configure all required providers
    console.log("Setting up providers...");
    const zkConfigPath = path.join(
      contractPath,
      "managed",
      config.CONTRACT_CONFIG.CONTRACT_NAME
    ).replace(/\\/g, '/');
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
      walletProvider: walletProvider,
      midnightProvider: walletProvider,
    };

    // Deploy contract to blockchain
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

    // Save deployment information
    console.log("\nDEPLOYED!");
    console.log(`Contract: ${contractAddress}\n`);
    console.log(
      `Inspect the contract at: ${config.MEXPLORER_URL}/transaction/0x${transactionHash}/${blockHeight}\n`
    );

    const info = {
      contractAddress,
      blockHeight,
      transactionHash,
      deployedAt: new Date().toISOString(),
      blockNumber: new Date().toISOString(),
    };

    fs.writeFileSync("deployment.json", JSON.stringify(info, null, 2));
    console.log("Saved to deployment.json");

    // Close wallet connection
    await wallet.close();
  } catch (error) {
    console.error("Failed:", error);
  } finally {
    rl.close();
  }
}

main().catch(console.error);
