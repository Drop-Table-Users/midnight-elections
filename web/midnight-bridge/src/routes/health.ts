import { Router, Request, Response } from 'express';
import { walletService } from '../services/WalletService.js';
import config from '../config/midnight.js';

const router = Router();

router.get('/health', async (req: Request, res: Response) => {
  try {
    const isWalletReady = walletService.isReady();

    let walletInfo = {};
    if (isWalletReady) {
      try {
        const address = await walletService.getAddress();
        const balance = await walletService.getBalance();
        walletInfo = {
          address,
          balance: balance.toString(),
        };
      } catch (error) {
        walletInfo = { error: 'Failed to get wallet info' };
      }
    }

    res.json({
      status: 'ok',
      service: 'midnight-bridge',
      version: '1.0.0',
      network: config.network,
      wallet: {
        ready: isWalletReady,
        ...walletInfo,
      },
      endpoints: {
        indexer: config.networkConfig.indexer,
        proofServer: config.networkConfig.proofServer,
        node: config.networkConfig.node,
      },
      timestamp: new Date().toISOString(),
    });
  } catch (error: any) {
    res.status(500).json({
      status: 'error',
      message: error.message,
    });
  }
});

export default router;
