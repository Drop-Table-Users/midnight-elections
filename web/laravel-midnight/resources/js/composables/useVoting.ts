// resources/js/composables/useVoting.ts
import { ref, computed } from 'vue';
import type { Ref } from 'vue';
import { buildAndSubmitVoteTx, queryTransactionStatus, waitForTransactionConfirmation } from '../midnight/client';
import type { VoteTxPayload, TransactionInfo, TransactionStatus } from '../midnight/types';

export interface UseVotingOptions {
  onVoteSubmitted?: (txHash: string) => void;
  onVoteConfirmed?: (txInfo: TransactionInfo) => void;
  onVoteFailed?: (error: Error) => void;
}

export interface UseVotingReturn {
  // State
  isSubmitting: Ref<boolean>;
  isWaitingConfirmation: Ref<boolean>;
  txHash: Ref<string | null>;
  txStatus: Ref<TransactionStatus>;
  txInfo: Ref<TransactionInfo | null>;
  error: Ref<string | null>;
  hasVoted: Ref<boolean>;

  // Computed
  isProcessing: Ref<boolean>;

  // Methods
  submitVote: (payload: VoteTxPayload) => Promise<string>;
  checkStatus: (txHash: string) => Promise<TransactionInfo>;
  waitForConfirmation: (txHash: string, options?: { maxAttempts?: number; pollInterval?: number }) => Promise<TransactionInfo>;
  reset: () => void;
}

/**
 * Vue composable for voting functionality
 *
 * Provides reactive state and methods for submitting votes,
 * tracking transaction status, and handling confirmations.
 *
 * @example
 * ```ts
 * const {
 *   isSubmitting,
 *   txHash,
 *   submitVote,
 *   waitForConfirmation
 * } = useVoting({
 *   onVoteConfirmed: (txInfo) => {
 *     console.log('Vote confirmed!', txInfo);
 *   }
 * });
 *
 * // Submit a vote
 * const hash = await submitVote({
 *   contractAddress: '0x...',
 *   candidateId: 'candidate-1',
 *   encryptedBallot: 'encrypted-data'
 * });
 *
 * // Wait for confirmation
 * await waitForConfirmation(hash);
 * ```
 */
export function useVoting(options: UseVotingOptions = {}): UseVotingReturn {
  // Reactive state
  const isSubmitting = ref<boolean>(false);
  const isWaitingConfirmation = ref<boolean>(false);
  const txHash = ref<string | null>(null);
  const txStatus = ref<TransactionStatus>('unknown');
  const txInfo = ref<TransactionInfo | null>(null);
  const error = ref<string | null>(null);
  const hasVoted = ref<boolean>(false);

  // Computed
  const isProcessing = computed(() => isSubmitting.value || isWaitingConfirmation.value);

  /**
   * Submit a vote transaction
   */
  const submitVote = async (payload: VoteTxPayload): Promise<string> => {
    if (isSubmitting.value) {
      throw new Error('Vote submission already in progress');
    }

    error.value = null;
    isSubmitting.value = true;
    txStatus.value = 'pending';

    try {
      const hash = await buildAndSubmitVoteTx(payload);

      txHash.value = hash;
      txStatus.value = 'submitted';
      hasVoted.value = true;

      // Call callback if provided
      if (options.onVoteSubmitted) {
        options.onVoteSubmitted(hash);
      }

      return hash;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Vote submission failed';
      error.value = errorMessage;
      txStatus.value = 'failed';

      if (options.onVoteFailed) {
        options.onVoteFailed(err instanceof Error ? err : new Error(errorMessage));
      }

      throw err;
    } finally {
      isSubmitting.value = false;
    }
  };

  /**
   * Check transaction status
   */
  const checkStatus = async (hash: string): Promise<TransactionInfo> => {
    try {
      error.value = null;

      const info = await queryTransactionStatus(hash);

      txInfo.value = info;
      txStatus.value = info.status;

      return info;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to check transaction status';
      error.value = errorMessage;
      throw err;
    }
  };

  /**
   * Wait for transaction confirmation
   */
  const waitForConfirmation = async (
    hash: string,
    waitOptions: { maxAttempts?: number; pollInterval?: number } = {}
  ): Promise<TransactionInfo> => {
    if (isWaitingConfirmation.value) {
      throw new Error('Already waiting for confirmation');
    }

    error.value = null;
    isWaitingConfirmation.value = true;

    try {
      const info = await waitForTransactionConfirmation(hash, waitOptions);

      txInfo.value = info;
      txStatus.value = info.status;

      if (info.status === 'confirmed') {
        if (options.onVoteConfirmed) {
          options.onVoteConfirmed(info);
        }
      } else if (info.status === 'failed') {
        const err = new Error(info.error || 'Transaction failed');
        if (options.onVoteFailed) {
          options.onVoteFailed(err);
        }
      }

      return info;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Confirmation wait failed';
      error.value = errorMessage;

      if (options.onVoteFailed) {
        options.onVoteFailed(err instanceof Error ? err : new Error(errorMessage));
      }

      throw err;
    } finally {
      isWaitingConfirmation.value = false;
    }
  };

  /**
   * Reset voting state
   */
  const reset = () => {
    isSubmitting.value = false;
    isWaitingConfirmation.value = false;
    txHash.value = null;
    txStatus.value = 'unknown';
    txInfo.value = null;
    error.value = null;
    hasVoted.value = false;
  };

  return {
    // State
    isSubmitting,
    isWaitingConfirmation,
    txHash,
    txStatus,
    txInfo,
    error,
    hasVoted,

    // Computed
    isProcessing,

    // Methods
    submitVote,
    checkStatus,
    waitForConfirmation,
    reset,
  };
}
