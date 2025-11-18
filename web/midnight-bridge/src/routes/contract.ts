import { Router, Request, Response } from 'express';
import { walletService } from '../services/WalletService.js';
import { contractService } from '../services/ContractService.js';
import { createHash } from 'crypto';

const router = Router();

function candidateIdToBytes(input: string): Uint8Array {
  const trimmed = input.trim();
  const hexRegex = /^[0-9a-fA-F]{64}$/;
  const withoutPrefix = trimmed.startsWith('0x') ? trimmed.slice(2) : trimmed;

  if (hexRegex.test(withoutPrefix)) {

    const bytes = new Uint8Array(32);
    for (let i = 0; i < 64; i += 2) {
      bytes[i / 2] = parseInt(withoutPrefix.slice(i, i + 2), 16);
    }
    return bytes;
  }
  const digest = createHash('sha256').update(trimmed).digest();
  return new Uint8Array(digest);
}

router.post('/call', async (req: Request, res: Response) => {
  try {
    if (!walletService.isReady()) {
      return res.status(503).json({
        status: 'error',
        message: 'Wallet not initialized',
      });
    }

    if (!contractService.isReady()) {
      return res.status(503).json({
        status: 'error',
        message: 'Contract service not initialized. Please ensure the contract is compiled and deployed.',
      });
    }

    const { action, candidate_id, ballot_data } = req.body;

    if (!action) {
      return res.status(400).json({
        status: 'error',
        message: 'Action is required',
      });
    }

    console.log(`Executing REAL contract action: ${action}`);

    let result: any;

    switch (action) {
      case 'open':
        result = await contractService.openElection();
        break;

      case 'close':
        result = await contractService.closeElection();
        break;

      case 'register':
        if (!candidate_id) {
          return res.status(400).json({
            status: 'error',
            message: 'candidate_id is required for register action',
          });
        }
        const candidateBytes = candidateIdToBytes(candidate_id);
        result = await contractService.registerCandidate(candidateBytes);
        break;

      case 'vote':
        return res.status(501).json({
          status: 'error',
          message: 'Voting requires credential data. Full voting implementation coming soon.',
          details: 'You need to provide: election_id, candidate_id, and a signed credential',
        });

      default:
        return res.status(400).json({
          status: 'error',
          message: `Unknown action: ${action}`,
        });
    }

    res.json({
      status: 'success',
      data: result,
    });
  } catch (error: any) {
    console.error('Contract call error:', error);
    res.status(500).json({
      status: 'error',
      message: error.message,
      details: error.stack,
    });
  }
});

export default router;
