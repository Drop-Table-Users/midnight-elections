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
import { fileURLToPath, pathToFileURL } from "url";
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
        throw new Error("Contract artifact not found. Run npm run compile:elections");
    }

    // Convert Windows path to file URL for ESM import
    const contractModuleUrl = pathToFileURL(contractModulePath).href;
    return import(contractModuleUrl);
};

const createProviders = async (walletProvider: any) => {
    const __filename = fileURLToPath(import.meta.url);
    const __dirname = path.dirname(__filename);
    const contractPath = path.join(__dirname, "..", "contracts");
    const zkConfigPath = path.join(
        contractPath,
        "managed",
        config.CONTRACT_CONFIG.CONTRACT_NAME
    ).replace(/\\/g, '/');

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

// Cache wallet globally to avoid rebuilding on every request
let cachedWallet: ManagedWallet | null = null;
let cachedWalletSeed: string | null = null;

const getOrBuildWallet = async (walletSeed: string): Promise<ManagedWallet> => {
    // Reuse existing wallet if seed matches
    if (cachedWallet && cachedWalletSeed === walletSeed) {
        console.log("Reusing cached wallet");
        return cachedWallet;
    }

    // Close old wallet if seed changed
    if (cachedWallet && cachedWalletSeed !== walletSeed) {
        console.log("Wallet seed changed, closing old wallet");
        await cachedWallet.close();
        cachedWallet = null;
    }

    // Build new wallet
    console.log("Building new wallet");
    cachedWallet = await buildWallet(walletSeed);
    cachedWalletSeed = walletSeed;
    return cachedWallet;
};

const performAction = async (
    action: "open" | "close" | "register_candidate" | "cast_vote",
    options: ActionOptions,
    actionParams?: any
) => {
    const walletSeed = options.walletSeed ?? config.WALLET_SEED;
    if (!walletSeed) {
        throw new Error("A wallet seed is required.");
    }

    const wallet = await getOrBuildWallet(walletSeed);
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
        get_ballot: ({ privateState }: any) => [privateState, actionParams?.ballot ?? createEmptyBallot()],
    };
    const contractInstance = new ElectionsModule.Contract(witnesses);

    const txInterface = createCircuitCallTxInterface(
        providers as any,
        contractInstance as any,
        contractAddress,
        config.CONTRACT_CONFIG.CONTRACT_STATE_ID
    ) as any;

    let result;
    switch (action) {
        case "open":
            result = await txInterface.open_election();
            break;
        case "close":
            result = await txInterface.close_election();
            break;
        case "register_candidate":
            result = await txInterface.register_candidate(actionParams.candidateId);
            break;
        case "cast_vote":
            result = await txInterface.cast_vote();
            break;
        default:
            throw new Error(`Unknown action: ${action}`);
    }

    const txHash = result.public.txHash;
    const blockHeight = result.public.blockHeight;
    const explorerUrl = `${config.MEXPLORER_URL}/transaction/0x${txHash}/${blockHeight}`;

    return {
        txHash,
        blockHeight,
        explorerUrl,
    };
};

export const openElection = (options: ActionOptions = {}): Promise<ActionResult> =>
    performAction("open", options);

export const closeElection = (options: ActionOptions = {}): Promise<ActionResult> =>
    performAction("close", options);

export interface RegisterCandidateOptions extends ActionOptions {
    candidateId: string;
}

export const registerCandidate = (options: RegisterCandidateOptions): Promise<ActionResult> => {
    const { candidateId, ...actionOptions } = options;

    // Convert hex string to Uint8Array
    const candidateIdHex = candidateId.startsWith("0x") ? candidateId.slice(2) : candidateId;
    if (candidateIdHex.length !== 64) {
        throw new Error("candidateId must be a 32-byte hex string (64 hex characters)");
    }

    const candidateIdBytes = new Uint8Array(
        candidateIdHex.match(/.{1,2}/g)!.map(byte => parseInt(byte, 16))
    );

    return performAction("register_candidate", actionOptions, { candidateId: candidateIdBytes });
};

export interface Ballot {
    election_id: Uint8Array;
    candidate_id: Uint8Array;
    credential: {
        subject: {
            id: Uint8Array;
            first_name: Uint8Array;
            last_name: Uint8Array;
            national_identifier: Uint8Array;
            birth_timestamp: bigint;
        };
        signature: {
            pk: { x: bigint; y: bigint };
            R: { x: bigint; y: bigint };
            s: bigint;
        };
    };
}

export interface VoteOptions extends ActionOptions {
    ballot: Ballot;
}

export const vote = (options: VoteOptions): Promise<ActionResult> => {
    const { ballot, ...actionOptions } = options;
    return performAction("cast_vote", actionOptions, { ballot });
};

export interface WalletStatus {
    initialized: boolean;
    synced?: boolean;
    address?: string;
    balance?: string;
    syncProgress?: {
        sourceGap: number;
        applyGap: number;
    };
}

export const getWalletStatus = async (): Promise<WalletStatus> => {
    if (!cachedWallet) {
        return { initialized: false };
    }

    try {
        const state = await Rx.firstValueFrom(cachedWallet.state());
        return {
            initialized: true,
            synced: state.syncProgress?.synced ?? false,
            address: state.address,
            balance: (state.balances[nativeToken()] ?? 0n).toString(),
            syncProgress: state.syncProgress?.lag ? {
                sourceGap: Number(state.syncProgress.lag.sourceGap),
                applyGap: Number(state.syncProgress.lag.applyGap),
            } : undefined,
        };
    } catch (error) {
        return {
            initialized: true,
            synced: false,
        };
    }
};

export interface RegisterVoterOptions {
    walletAddress: string;
    fullName: string;
    nationalId: string;
    dateOfBirth: string; // ISO date string
}

export interface RegisterVoterResult {
    credentialHash: string;
    message: string;
}

/**
 * Register a voter by creating a credential hash from their KYC data.
 * This credential hash is used to prevent double voting while maintaining privacy.
 */
export const registerVoter = async (options: RegisterVoterOptions): Promise<RegisterVoterResult> => {
    // Import crypto module for hashing
    const crypto = await import("crypto");

    // Parse the date of birth to Unix timestamp
    const birthDate = new Date(options.dateOfBirth);
    const birthTimestamp = BigInt(Math.floor(birthDate.getTime() / 1000));

    // Split full name into first and last name (simple split on first space)
    const nameParts = options.fullName.trim().split(/\s+/);
    const firstName = nameParts[0] || "";
    const lastName = nameParts.slice(1).join(" ") || "";

    // Convert strings to 32-byte arrays (hash and pad/truncate to 32 bytes)
    const stringToBytes32 = (str: string): Uint8Array => {
        const hash = crypto.createHash('sha256').update(str, 'utf8').digest();
        return new Uint8Array(hash);
    };

    // Create credential subject structure
    const credentialSubject = {
        id: stringToBytes32(options.walletAddress),
        first_name: stringToBytes32(firstName),
        last_name: stringToBytes32(lastName),
        national_identifier: stringToBytes32(options.nationalId),
        birth_timestamp: birthTimestamp,
    };

    // Create a deterministic hash of the credential subject
    // This hash serves as the voter's nullifier to prevent double voting
    const credentialData = JSON.stringify({
        id: Array.from(credentialSubject.id),
        first_name: Array.from(credentialSubject.first_name),
        last_name: Array.from(credentialSubject.last_name),
        national_identifier: Array.from(credentialSubject.national_identifier),
        birth_timestamp: credentialSubject.birth_timestamp.toString(),
    });

    const credentialHash = crypto.createHash('sha256')
        .update(credentialData, 'utf8')
        .digest('hex');

    return {
        credentialHash: `0x${credentialHash}`,
        message: "Voter registered successfully. The credential hash will prevent double voting.",
    };
};

export interface ElectionResultsOptions {
    contractAddress?: string;
}

export interface CandidateResult {
    candidateId: string;
    votes: string;
}

export interface ElectionResultsResponse {
    results: CandidateResult[];
    totalVotes: string;
    contractAddress: string;
}

/**
 * Get election results by querying the vote_counts map from the blockchain.
 * This queries the public ledger data to get vote counts for each candidate.
 */
export const getElectionResults = async (options: ElectionResultsOptions = {}): Promise<ElectionResultsResponse> => {
    const contractAddress = loadContractAddress(options.contractAddress);

    // Create public data provider to query blockchain
    const publicDataProvider = indexerPublicDataProvider(
        config.NETWORK_CONFIG.INDEXER,
        config.NETWORK_CONFIG.INDEXER_WS
    );

    // Query contract state from blockchain
    const contractState = await publicDataProvider.queryContractState(contractAddress);
    if (!contractState) {
        throw new Error(`No contract state found at ${contractAddress}`);
    }

    // Load the contract module to access ledger state
    const ElectionsModule = await loadElectionsContractModule();

    // Parse the contract state to get vote_counts map
    // The vote_counts is a Map<Bytes<32>, Uint<64>> where key is candidate_id and value is vote count
    const results: CandidateResult[] = [];
    let totalVotes = 0n;

    // Access the ledger state - vote_counts is a public map
    if (contractState.data && contractState.data.vote_counts) {
        const voteCounts = contractState.data.vote_counts;

        // Iterate through all entries in the vote_counts map
        for (const [candidateIdBytes, voteCount] of Object.entries(voteCounts)) {
            // Convert Uint8Array candidate ID to hex string
            const candidateId = `0x${Buffer.from(candidateIdBytes as any).toString('hex')}`;
            const votes = BigInt(voteCount as any);

            results.push({
                candidateId,
                votes: votes.toString(),
            });

            totalVotes += votes;
        }
    }

    return {
        results: results.sort((a, b) => Number(BigInt(b.votes) - BigInt(a.votes))), // Sort by votes descending
        totalVotes: totalVotes.toString(),
        contractAddress,
    };
};

export interface VerifyVoteOptions {
    credentialHash: string;
    contractAddress?: string;
}

export interface VerifyVoteResponse {
    voted: boolean;
    credentialHash: string;
    message: string;
    contractAddress: string;
}

/**
 * Verify if a vote was cast by checking the voter_nullifiers map on the blockchain.
 * This allows voters to confirm their vote was counted without revealing their choice.
 */
export const verifyVote = async (options: VerifyVoteOptions): Promise<VerifyVoteResponse> => {
    const contractAddress = loadContractAddress(options.contractAddress);
    const credentialHash = options.credentialHash.startsWith("0x")
        ? options.credentialHash.slice(2)
        : options.credentialHash;

    if (credentialHash.length !== 64) {
        throw new Error("Credential hash must be a 32-byte hex string (64 hex characters)");
    }

    // Create public data provider to query blockchain
    const publicDataProvider = indexerPublicDataProvider(
        config.NETWORK_CONFIG.INDEXER,
        config.NETWORK_CONFIG.INDEXER_WS
    );

    // Query contract state from blockchain
    const contractState = await publicDataProvider.queryContractState(contractAddress);
    if (!contractState) {
        throw new Error(`No contract state found at ${contractAddress}`);
    }

    // Check if the credential hash exists in voter_nullifiers map
    // voter_nullifiers is a Map<Bytes<32>, Uint<1>> where presence indicates vote was cast
    let voted = false;

    if (contractState.data && contractState.data.voter_nullifiers) {
        const voterNullifiers = contractState.data.voter_nullifiers;

        // Check if this credential hash is in the nullifiers map
        // The key is the credential hash as bytes
        const nullifierKey = credentialHash.toLowerCase();

        // Check if the key exists in the map
        if (voterNullifiers[nullifierKey] !== undefined) {
            voted = true;
        }
    }

    const message = voted
        ? "Your vote was successfully recorded on the blockchain. Thank you for participating!"
        : "No vote found for this credential hash. Please ensure you have voted and the transaction was confirmed.";

    return {
        voted,
        credentialHash: `0x${credentialHash}`,
        message,
        contractAddress,
    };
};
