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
import { nativeToken, Transaction } from "@midnight-ntwrk/ledger";
import { Transaction as ZswapTransaction } from "@midnight-ntwrk/zswap";
import { fromHex } from "@midnight-ntwrk/midnight-js-utils";
import { WebSocket } from "ws";
import * as path from "path";
import * as fs from "fs";
import * as readline from "readline/promises";
import * as Rx from "rxjs";
import { type Wallet } from "@midnight-ntwrk/wallet-api";
import { exit } from "process";
import * as crypto from "crypto";
import * as config from "./config.js";

// Fix WebSocket for Node.js environment
// @ts-ignore
globalThis.WebSocket = WebSocket;

setNetworkId(config.NETWORK);

const HEX_REGEX = /^[0-9a-fA-F]+$/;

const to32Bytes = (walletSeed: string): Uint8Array => {
  const normalized = walletSeed.trim();
  const withoutPrefix = normalized.startsWith("0x")
    ? normalized.slice(2)
    : normalized;

  if (HEX_REGEX.test(withoutPrefix) && withoutPrefix.length === 64) {
    return fromHex(withoutPrefix);
  }

  const rawBytes = HEX_REGEX.test(withoutPrefix)
    ? fromHex(withoutPrefix)
    : new TextEncoder().encode(normalized);
  const digest = crypto.createHash("sha256").update(rawBytes).digest();
  return new Uint8Array(digest);
};

const deriveTrustedIssuerPublicKeyFromSeed = (
  pureCircuits: any,
  walletSeed: string
) => {
  if (!walletSeed.trim()) {
    throw new Error("Wallet seed is required to derive a signing key.");
  }

  const skBytes = to32Bytes(walletSeed);
  return pureCircuits.derive_pk(skBytes);
};

const waitForFunds = (wallet: Wallet) =>
  Rx.firstValueFrom(
    wallet.state().pipe(
      Rx.filter((state) => state.syncProgress?.synced === true),
      Rx.map((s) => s.balances[nativeToken()] ?? 0n),
      Rx.filter((balance) => balance > 0n),
      Rx.tap((balance) => console.log(`Wallet funded with balance: ${balance}`))
    )
  );

async function loadContractModule() {
  const contractPath = path.join(process.cwd(), "contracts");
  const contractModulePath = path.join(
    contractPath,
    "managed",
    config.CONTRACT_CONFIG.CONTRACT_NAME,
    "contract",
    "index.cjs"
  );

  if (!fs.existsSync(contractModulePath)) {
    throw new Error("Contract artifact not found. Run npm run compile:registry");
  }

  return import(contractModulePath);
}

async function loadIdentityPureCircuits() {
  const identityModulePath = path.join(
    process.cwd(),
    "contracts",
    "managed",
    "identity",
    "contract",
    "index.cjs"
  );

  if (!fs.existsSync(identityModulePath)) {
    throw new Error("Identity contract artifact not found. Run npm run compile:identity");
  }

  const module = await import(identityModulePath);
  if (!module.pureCircuits || !module.pureCircuits.derive_pk) {
    throw new Error(
      "Identity contract pure circuits not available. Ensure identity.compact exports derive_pk."
    );
  }
  return module.pureCircuits;
}
async function getContractAddress() {
  const deploymentPath = path.join(process.cwd(), "registry-deployment.json");
  if (!fs.existsSync(deploymentPath)) {
    throw new Error(
      "registry-deployment.json not found. Deploy the registry contract first."
    );
  }

  const deployment = JSON.parse(fs.readFileSync(deploymentPath, "utf-8"));
  const addr = deployment.contractAddress;
  if (!addr) {
    throw new Error("registry-deployment.json missing contractAddress.");
  }
  if (typeof addr === "string") {
    return { bytes: addr };
  }
  if (addr.bytes) {
    return addr;
  }
  throw new Error(
    "Unsupported contractAddress format in registry-deployment.json."
  );
}

async function main() {
  console.log("Registering signing key with the registry contract\n");

  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
  });

  try {
    const choice = await rl.question("Do you have a wallet seed? (y/n): ");

    let walletSeed: string;
    if (choice.toLowerCase() === "y" || choice.toLowerCase() === "yes") {
      walletSeed = config.WALLET_SEED;
    } else {
      console.error(
        "This script expects WALLET_SEED in config.ts to be set to the issuer wallet seed."
      );
      exit(1);
    }

    const identityPureCircuits = await loadIdentityPureCircuits();
    const derivedPk = deriveTrustedIssuerPublicKeyFromSeed(
      identityPureCircuits,
      walletSeed
    );
    console.log("Derived trusted issuer public key:", {
      x: derivedPk.x.toString(),
      y: derivedPk.y.toString(),
    });

    // const wallet = await WalletBuilder.buildFromSeed(
    //   config.NETWORK_CONFIG.INDEXER,
    //   config.NETWORK_CONFIG.INDEXER_WS,
    //   config.NETWORK_CONFIG.PROOF_SERVER,
    //   config.NETWORK_CONFIG.NODE,
    //   walletSeed,
    //   getZswapNetworkId(),
    //   "info"
    // );

    // wallet.start();

    // const state = await Rx.firstValueFrom(wallet.state());
    // console.log(`Wallet address: ${state.address}`);

    // let balance = state.balances[nativeToken()] || 0n;
    // if (balance === 0n) {
    //   console.log("Waiting for wallet to be funded...");
    //   balance = await waitForFunds(wallet);
    // }

    // const contractAddress = await getContractAddress();
    // const RegistryModule = await loadContractModule();
    // const contractInstance = new RegistryModule.Contract({});

    // const walletState = await Rx.firstValueFrom(wallet.state());

    // const walletProvider = {
    //   coinPublicKey: walletState.coinPublicKey,
    //   encryptionPublicKey: walletState.encryptionPublicKey,
    //   balanceTx(tx: any, newCoins: any) {
    //     return wallet
    //       .balanceTransaction(
    //         ZswapTransaction.deserialize(
    //           tx.serialize(getLedgerNetworkId()),
    //           getZswapNetworkId()
    //         ),
    //         newCoins
    //       )
    //       .then((tx) => wallet.proveTransaction(tx))
    //       .then((zswapTx) =>
    //         Transaction.deserialize(
    //           zswapTx.serialize(getZswapNetworkId()),
    //           getLedgerNetworkId()
    //         )
    //       )
    //       .then(createBalancedTx);
    //   },
    //   submitTx(tx: any) {
    //     return wallet.submitTransaction(tx);
    //   },
    // };

    // const zkConfigPath = path.join(
    //   process.cwd(),
    //   "contracts",
    //   "managed",
    //   config.CONTRACT_CONFIG.CONTRACT_NAME
    // );
    // const providers = {
    //   privateStateProvider: levelPrivateStateProvider({
    //     privateStateStoreName: config.CONTRACT_CONFIG.CONTRACT_STATE_NAME,
    //   }),
    //   publicDataProvider: indexerPublicDataProvider(
    //     config.NETWORK_CONFIG.INDEXER,
    //     config.NETWORK_CONFIG.INDEXER_WS
    //   ),
    //   zkConfigProvider: new NodeZkConfigProvider(zkConfigPath),
    //   proofProvider: httpClientProofProvider(
    //     config.NETWORK_CONFIG.PROOF_SERVER
    //   ),
    //   walletProvider,
    //   midnightProvider: walletProvider,
    // };

    // const circuitInterface = createCircuitCallTxInterface(
    //   providers,
    //   contractInstance,
    //   contractAddress,
    //   undefined
    // );

    // console.log("Submitting register transaction...");
    // const registerCircuit = circuitInterface.register as (
    //   arg: { x: string; y: string }
    // ) => Promise<any>;
    // const result = await registerCircuit({
    //   x: derivedPk.x.toString(),
    //   y: derivedPk.y.toString(),
    // });

    // console.log("Register transaction submitted.");
    // console.log(
    //   `Public transaction hash: ${result.public.txHash ?? "unknown (check explorer)"}`
    // );
    // await wallet.close();
  } catch (error) {
    console.error("Failed to register signing key:", error);
  } finally {
    rl.close();
  }
}

main().catch(console.error);
