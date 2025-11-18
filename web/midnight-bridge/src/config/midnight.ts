import dotenv from 'dotenv';

dotenv.config();

export const config = {
  port: parseInt(process.env.PORT || '4100'),
  nodeEnv: process.env.NODE_ENV || 'development',

  network: process.env.MIDNIGHT_NETWORK || 'testnet',

  networkConfig: {
    indexer: process.env.MIDNIGHT_INDEXER || 'https://indexer.testnet.midnight.network',
    indexerWs: process.env.MIDNIGHT_INDEXER_WS || 'wss://indexer.testnet.midnight.network',
    proofServer: process.env.MIDNIGHT_PROOF_SERVER || 'https://proofserver-testnet-02.midnight.network',
    node: process.env.MIDNIGHT_NODE || 'https://rpc.testnet.midnight.network',
  },

  walletSeed: process.env.WALLET_SEED || '',

  contractAddress: process.env.CONTRACT_ADDRESS || '',
  blockHeight: process.env.BLOCK_HEIGHT ? parseInt(process.env.BLOCK_HEIGHT) : undefined,
  contractPath: process.env.CONTRACT_PATH || '',
  contractStateStoreName: process.env.CONTRACT_STATE_STORE_NAME || 'elections-private-state',
  contractStateId: process.env.CONTRACT_STATE_ID || 'elections-state',

  cors: {
    allowedOrigins: (process.env.ALLOWED_ORIGINS || '').split(',').filter(Boolean),
  },

  logLevel: process.env.LOG_LEVEL || 'info',
};
if (!config.walletSeed) {
  console.warn('Warning: WALLET_SEED is not set in .env file');
}

export default config;
