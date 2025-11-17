import { NetworkId } from "@midnight-ntwrk/midnight-js-network-id";

const CONTRACT_NAME = "registry";
const CONTRACT_STATE_NAME = `${CONTRACT_NAME}-state`;
const CONTRACT_STATE_ID = `${CONTRACT_NAME}State`;

export const CONTRACT_CONFIG = {
  CONTRACT_NAME,
  CONTRACT_STATE_NAME,
  CONTRACT_STATE_ID,
};

export const MEXPLORER_URL = "https://nightly.mexplorer.io";

export const WALLET_SEED: string =
  "f468965bfa3aa8056e7232a6de1067d32b89f5d451d4fde61666a66cfaf4ce2f";

export const NETWORK = NetworkId.TestNet;
export const INDEXER: string =
  "https://indexer.testnet-02.midnight.network/api/v1/graphql";
export const INDEXER_WS: string =
  "wss://indexer.testnet-02.midnight.network/api/v1/graphql/ws";
export const NODE: string = "https://rpc.testnet-02.midnight.network";
export const PROOF_SERVER: string = "http://127.0.0.1:6300";

export const NETWORK_CONFIG = {
  INDEXER,
  INDEXER_WS,
  NODE,
  PROOF_SERVER,
};
