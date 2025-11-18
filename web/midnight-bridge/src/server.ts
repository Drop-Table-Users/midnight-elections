import express, { Application, Request, Response, NextFunction } from 'express';
import cors from 'cors';
import bodyParser from 'body-parser';
import config from './config/midnight.js';
import { walletService } from './services/WalletService.js';
import healthRouter from './routes/health.js';
import contractRouter from './routes/contract.js';

const app: Application = express();
app.use(cors({
  origin: (origin, callback) => {
    if (!origin) return callback(null, true);
    if (config.cors.allowedOrigins.includes(origin) || config.cors.allowedOrigins.includes('*')) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  },
  credentials: true,
}));

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use((req: Request, res: Response, next: NextFunction) => {
  console.log(`${new Date().toISOString()} - ${req.method} ${req.path}`);
  next();
});
app.use('/', healthRouter);
app.use('/contract', contractRouter);
app.get('/', (req: Request, res: Response) => {
  res.json({
    service: 'Midnight Bridge Service',
    version: '1.0.0',
    network: config.network,
    status: 'running',
    documentation: '/health',
  });
});
app.get('/wallet/info', async (req: Request, res: Response) => {
  try {
    if (!walletService.isReady()) {
      return res.status(503).json({
        status: 'error',
        message: 'Wallet not initialized',
      });
    }

    const address = await walletService.getAddress();
    const balance = await walletService.getBalance();
    const state = await walletService.getState();

    res.json({
      status: 'success',
      data: {
        address,
        balance: balance.toString(),
        coinPublicKey: state.coinPublicKey,
        encryptionPublicKey: state.encryptionPublicKey,
        syncProgress: state.syncProgress,
      },
    });
  } catch (error: any) {
    res.status(500).json({
      status: 'error',
      message: error.message,
    });
  }
});
app.use((err: Error, req: Request, res: Response, next: NextFunction) => {
  console.error('Error:', err.message);
  res.status(500).json({
    status: 'error',
    message: err.message,
  });
});
async function startServer() {
  try {
    console.log('=== Midnight Bridge Service ===');
    console.log(`Network: ${config.network}`);
    console.log(`Indexer: ${config.networkConfig.indexer}`);
    console.log();
    // Initialize wallet service
    console.log('Initializing wallet service...');
    await walletService.initialize();

    // Initialize contract service (optional - will warn if contract not deployed)
    try {
      console.log('Initializing contract service...');
      const { contractService } = await import('./services/ContractService.js');
      await contractService.initialize();
      console.log();
    } catch (error: any) {
      console.warn('Contract service initialization failed (this is optional):', error.message);
      console.warn('Contract actions will not be available until the contract is deployed.');
    }

    // Start Express server
    app.listen(config.port, () => {
      console.log(`✓ Bridge service running on port ${config.port}`);
      console.log(`✓ Health check: http://localhost:${config.port}/health`);
      console.log(`✓ Wallet info: http://localhost:${config.port}/wallet/info`);
      console.log();
      console.log('Press Ctrl+C to stop the server');
    });
  } catch (error) {
    console.error('Failed to start server:', error);
    process.exit(1);
  }
}
process.on('SIGINT', async () => {
  console.log('\nShutting down gracefully...');
  await walletService.close();
  process.exit(0);
});

process.on('SIGTERM', async () => {
  console.log('\nShutting down gracefully...');
  await walletService.close();
  process.exit(0);
});
startServer();
