import { WalletBuilder } from "@midnight-ntwrk/wallet";
import { createCircuitCallTxInterface } from "@midnight-ntwrk/midnight-js-contracts";
import { httpClientProofProvider } from "@midnight-ntwrk/midnight-js-http-client-proof-provider";
import { indexerPublicDataProvider } from "@midnight-ntwrk/midnight-js-indexer-public-data-provider";
import { NodeZkConfigProvider } from "@midnight-ntwrk/midnight-js-node-zk-config-provider";
import { levelPrivateStateProvider } from "@midnight-ntwrk/midnight-js-level-private-state-provider";
import "./compactRuntimePatch.js";
import {
  setNetworkId,
  getZswapNetworkId,
  getLedgerNetworkId,
} from "@midnight-ntwrk/midnight-js-network-id";
import { createBalancedTx } from "@midnight-ntwrk/midnight-js-types";
import { nativeToken, Transaction, type ContractAddress } from "@midnight-ntwrk/ledger";
import { Transaction as ZswapTransaction } from "@midnight-ntwrk/zswap";
import { WebSocket } from "ws";
import * as fs from "fs";
import * as path from "path";
import * as readline from "readline/promises";
import * as Rx from "rxjs";
import { type Wallet, type WalletState } from "@midnight-ntwrk/wallet-api";
import { createHash } from "crypto";
import { TextEncoder } from "util";
import * as config from "./config.js";
import { fromHex, parseCoinPublicKeyToHex, toHex } from "@midnight-ntwrk/midnight-js-utils";

type CurvePoint = { x: bigint; y: bigint };

type CredentialSubject = {
  id: Uint8Array;
  first_name: Uint8Array;
  last_name: Uint8Array;
  national_identifier: Uint8Array;
  birth_timestamp: bigint;
};

type Signature = {
  pk: CurvePoint;
  R: CurvePoint;
  s: bigint;
};

type Ballot = {
  election_id: Uint8Array;
  candidate_id: Uint8Array;
  credential: {
    subject: CredentialSubject;
    signature: Signature;
  };
};

type ManagedWallet = Wallet & {
  start(): void;
  close(): Promise<void>;
};

// Fix WebSocket for Node.js environment
// @ts-ignore
globalThis.WebSocket = WebSocket;

// Configure for Midnight Testnet
setNetworkId(config.NETWORK);

const HEX_REGEX = /^[0-9a-fA-F]+$/;
const encoder = new TextEncoder();

const hexToBytes = (hex: string): Uint8Array => {
  const normalized = hex.trim().toLowerCase();
  const withoutPrefix = normalized.startsWith("0x")
    ? normalized.slice(2)
    : normalized;
  if (!HEX_REGEX.test(withoutPrefix) || withoutPrefix.length !== 64) {
    throw new Error("Hex input must be a 64-character string (32 bytes).");
  }
  const bytes = new Uint8Array(withoutPrefix.length / 2);
  for (let i = 0; i < withoutPrefix.length; i += 2) {
    bytes[i / 2] = parseInt(withoutPrefix.slice(i, i + 2), 16);
  }
  return bytes;
};

const isHex32 = (value: string) => {
  const normalized = value.trim().toLowerCase();
  const withoutPrefix = normalized.startsWith("0x")
    ? normalized.slice(2)
    : normalized;
  return HEX_REGEX.test(withoutPrefix) && withoutPrefix.length === 64;
};

const candidateIdToBytes = (input: string): Uint8Array => {
  if (!input.trim()) {
    throw new Error("Candidate identifier is required.");
  }
  if (isHex32(input)) {
    return hexToBytes(input);
  }
  const digest = createHash("sha256").update(input).digest();
  return new Uint8Array(digest);
};

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

async function loadElectionsContractModule() {
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
  if (!module.pureCircuits) {
    throw new Error("Identity pure circuits not available.");
  }

  return module.pureCircuits;
}

const normalizeContractAddress = (value: string): string => {
  const trimmed = value.trim();
  return trimmed.startsWith("0x") ? trimmed.slice(2) : trimmed;
};

async function getDeploymentAddress(): Promise<ContractAddress> {
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
}

async function promptWalletSeed(rl: readline.Interface): Promise<string> {
  if (config.WALLET_SEED?.trim()) {
    const choice = await rl.question(
      "Use WALLET_SEED from config.ts for this interaction? (y/n): "
    );
    if (choice.trim().toLowerCase().startsWith("y")) {
      return config.WALLET_SEED.trim();
    }
  }

  const provided = await rl.question(
    "Paste a 32-byte wallet seed (64 hex chars, without 0x): "
  );
  if (!isHex32(provided)) {
    throw new Error("Wallet seed must be a 32-byte hex string.");
  }
  return provided.trim();
}

async function promptIssuerSeed(rl: readline.Interface): Promise<Uint8Array> {
  if (process.env.ISSUER_SEED && isHex32(process.env.ISSUER_SEED)) {
    return hexToBytes(process.env.ISSUER_SEED);
  }
  const seed = await rl.question(
    "Enter the trusted issuer seed (32-byte hex used to derive the trusted public key): "
  );
  if (!isHex32(seed)) {
    throw new Error("Issuer seed must be a 32-byte hex string.");
  }
  return hexToBytes(seed);
}

export function pad(s: string, n: number): Uint8Array {
  const encoder = new TextEncoder();
  const utf8Bytes = encoder.encode(s);
  if (n < utf8Bytes.length) {
    throw new Error(`The padded length n must be at least ${utf8Bytes.length}`);
  }
  const paddedArray = new Uint8Array(n);
  paddedArray.set(utf8Bytes);
  return paddedArray;
}

async function buildBallot(
  rl: readline.Interface,
  walletState: WalletState,
  issuerSeedBytes: Uint8Array,
  identityPureCircuits: any
): Promise<Ballot> {
  const candidateRaw = await rl.question(
    "Candidate identifier (32-byte hex or plain text that will be hashed): "
  );
  const firstName = await rl.question("Voter first name (<=32 ASCII chars): ");
  const lastName = await rl.question("Voter last name (<=32 ASCII chars): ");
  const nationalId = await rl.question(
    "National identifier (<=32 ASCII chars): "
  );
  const birthTimestampRaw = await rl.question(
    "Birth timestamp (Unix seconds, numbers only): "
  );
  const candidateId = candidateIdToBytes(candidateRaw);
  const walletPkHex = parseCoinPublicKeyToHex(
    walletState.coinPublicKey,
    getZswapNetworkId()
  );

  const subject: CredentialSubject = {
    id: new Uint8Array(fromHex(walletPkHex)),
    first_name: pad(firstName, 32),
    last_name: pad(lastName, 32),
    national_identifier: pad(nationalId, 32),
    birth_timestamp: BigInt(birthTimestampRaw),
  };

  const subjectHashBytes = identityPureCircuits.subject_hash(subject);
  const signature: Signature = identityPureCircuits.sign(
    subjectHashBytes,
    issuerSeedBytes
  );

  const trustedPk = config.TRUSTED_ISSUER_PUBLIC_KEY;
  if (
    signature.pk.x !== trustedPk.x ||
    signature.pk.y !== trustedPk.y
  ) {
    console.log(signature.pk.x)
    console.log(signature.pk.y)
    throw new Error(
      "Signature public key does not match TRUSTED_ISSUER_PUBLIC_KEY. Ensure you are using the same issuer seed that was used during deployment."
    );
  }

  return {
    election_id: hexToBytes(config.ELECTION_CONSTRUCTOR_ARGS._election_id),
    candidate_id: candidateId,
    credential: {
      subject,
      signature,
    },
  };
}

async function main() {
  console.log("Midnight Elections Interaction\n");

  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
  });

  let wallet: ManagedWallet | null = null;

  try {
    const walletSeed = await promptWalletSeed(rl);

    console.log("Building wallet...");
    const builtWallet = (await WalletBuilder.buildFromSeed(
      config.NETWORK_CONFIG.INDEXER,
      config.NETWORK_CONFIG.INDEXER_WS,
      config.NETWORK_CONFIG.PROOF_SERVER,
      config.NETWORK_CONFIG.NODE,
      walletSeed,
      getZswapNetworkId(),
      "info"
    )) as ManagedWallet;
    wallet = builtWallet;

    builtWallet.start();
    const state = await Rx.firstValueFrom(builtWallet.state());
    console.log(`Your wallet address is: ${state.address}`);

    let balance = state.balances[nativeToken()] || 0n;
    if (balance === 0n) {
      console.log("Wallet balance is 0. Waiting for funds...");
      balance = await waitForFunds(builtWallet);
    }
    console.log(`Balance: ${balance}`);

    const ElectionsModule = await loadElectionsContractModule();
    const identityPureCircuits = await loadIdentityPureCircuits();
    const contractAddress = await getDeploymentAddress();

    let ballotWitness: Ballot | undefined;
    const witnesses = {
      get_ballot: ({ privateState }: any) => {
        if (!ballotWitness) {
          throw new Error(
            "Ballot witness requested but no ballot data was provided. Choose the vote action first."
          );
        }
        const ballot = ballotWitness;
        ballotWitness = undefined;
        return [privateState, ballot];
      },
    };

    const contractInstance = new ElectionsModule.Contract(witnesses);
    const walletState = await Rx.firstValueFrom(builtWallet.state());

    const walletProvider = {
      coinPublicKey: walletState.coinPublicKey,
      encryptionPublicKey: walletState.encryptionPublicKey,
      balanceTx(tx: any, newCoins: any) {
        return builtWallet
          .balanceTransaction(
            ZswapTransaction.deserialize(
              tx.serialize(getLedgerNetworkId()),
              getZswapNetworkId()
            ),
            newCoins
          )
          .then((balancedTx) => builtWallet.proveTransaction(balancedTx))
          .then((zswapTx) =>
            Transaction.deserialize(
              zswapTx.serialize(getZswapNetworkId()),
              getLedgerNetworkId()
            )
          )
          .then(createBalancedTx);
      },
      submitTx(tx: any) {
        return builtWallet.submitTransaction(tx);
      },
    };

    const contractPath = path.join(process.cwd(), "contracts");
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

    // Ensure the indexer has the contract.
    const contractState =
      await providers.publicDataProvider.queryContractState(contractAddress);
    if (!contractState) {
      throw new Error(
        `No contract state found at ${contractAddress}. Ensure deployment.json points to your elections contract and the deployment transaction has finalized.`
      );
    }

    const txInterface = createCircuitCallTxInterface(
      providers as any,
      contractInstance as any,
      contractAddress,
      config.CONTRACT_CONFIG.CONTRACT_STATE_ID
    ) as any;

    const actionRaw = await rl.question(
      "Choose action: open / close / register / vote (default vote): "
    );
    const normalizedAction = actionRaw.trim().toLowerCase() || "vote";
    const validActions = ["open", "close", "register", "vote"];
    const action = validActions.includes(normalizedAction)
      ? normalizedAction
      : "vote";

    switch (action) {
      case "open": {
        console.log("Opening election...");
        const result = await txInterface.open_election();
        console.log(
          `open_election submitted. Tx hash: ${result.public.txHash ?? "pending"}`
        );
        break;
      }
      case "close": {
        console.log("Closing election...");
        const result = await txInterface.close_election();
        console.log(
          `close_election submitted. Tx hash: ${result.public.txHash ?? "pending"}`
        );
        break;
      }
      case "register": {
        const candidateRaw = await rl.question(
          "Candidate identifier (32-byte hex or plain text to hash): "
        );
        const candidateId = candidateIdToBytes(candidateRaw);
        console.log("Registering candidate...");
        const result = await txInterface.register_candidate(candidateId);
        console.log(
          `register_candidate submitted. Tx hash: ${result.public.txHash ?? "pending"}`
        );
        break;
      }
      case "vote":
      default: {
        const issuerSeed = await promptIssuerSeed(rl);
        ballotWitness = await buildBallot(
          rl,
          walletState,
          issuerSeed,
          identityPureCircuits
        );
        console.log("Casting vote...");
        const result = await txInterface.cast_vote();
        console.log(
          `cast_vote submitted. Tx hash: ${result.public.txHash ?? "pending"}`
        );
        break;
      }
    }

  } catch (error) {
    console.error("Failed to interact with the elections contract:", error);
  } finally {
    if (wallet) {
      try {
        await wallet.close();
      } catch (closeError) {
        console.error("Failed to close wallet cleanly:", closeError);
      }
    }
    rl.close();
  }
}

main().catch((error) => {
  console.error(error);
});
