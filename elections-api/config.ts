import { CurvePoint } from "@midnight-ntwrk/compact-runtime";
import { NetworkId } from "@midnight-ntwrk/midnight-js-network-id";

// CONTRACT CONFIGURATION
const CONTRACT_NAME = "elections";
const CONTRACT_STATE_NAME = `${CONTRACT_NAME}-state`;
const CONTRACT_STATE_ID = `${CONTRACT_NAME}State`;

export const CONTRACT_CONFIG = {
  CONTRACT_NAME,
  CONTRACT_STATE_NAME,
  CONTRACT_STATE_ID,
};

// EXPLORER CONFIGURATION
export const MEXPLORER_URL = "https://nightly.mexplorer.io";

// WALLET CONFIGURATION
export const WALLET_SEED: string =
  "f468965bfa3aa8056e7232a6de1067d32b89f5d451d4fde61666a66cfaf4ce2f";

// CurvePoint derived for the WALLET_SEED
export const TRUSTED_ISSUER_PUBLIC_KEY: CurvePoint = {
  x: 3052036769499901191687985463742618230928741190947049489312153286386690819482n,
  y: 37672086510912437922943219454133120584770500982524941169526065157170942068209n,
};

// An unique identifier of the election, which represents the ongoing vote.
export const ELECTION_ID =
  "0000000000000000000000000000000000000000000000000000000000000000";

export const ELECTION_CONSTRUCTOR_ARGS = {
  _trusted_issuer_public_key: TRUSTED_ISSUER_PUBLIC_KEY,
  _election_id: ELECTION_ID,
};

// NETWORK CONFIGURATION
export const NETWORK = NetworkId.TestNet;
export const INDEXER: string =
  process.env.MIDNIGHT_INDEXER || "https://indexer.testnet-02.midnight.network/api/v1/graphql";
export const INDEXER_WS: string =
  process.env.MIDNIGHT_INDEXER_WS || "wss://indexer.testnet-02.midnight.network/api/v1/graphql/ws";
export const NODE: string =
  process.env.MIDNIGHT_NODE || "https://rpc.testnet-02.midnight.network";
export const PROOF_SERVER: string =
  process.env.PROOF_SERVER || "http://127.0.0.1:6300";

export const NETWORK_CONFIG = {
  INDEXER,
  INDEXER_WS,
  NODE,
  PROOF_SERVER,
};
