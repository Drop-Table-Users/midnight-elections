import { buildAndSubmitVoteTx } from '../midnight/client';

export default function voteForm(config = {}) {
  return {
    contractAddress: config.contractAddress || '',
    candidates: config.candidates || [],
    submitButtonText: config.submitButtonText || 'Submit Vote',
    submittingText: config.submittingText || 'Submitting...',
    successMessage: config.successMessage || 'Your vote has been submitted successfully!',
    selectedCandidateId: null,
    isSubmitting: false,
    hasVoted: false,
    txHash: null,
    error: null,
    loading: config.loading || false,

    get isValid() {
      return this.selectedCandidateId !== null && !this.isSubmitting;
    },

    get selectedCandidate() {
      if (!this.selectedCandidateId) return null;
      return this.candidates.find(c => c.id === this.selectedCandidateId);
    },

    get displayTxHash() {
      if (!this.txHash) return '';
      return `${this.txHash.slice(0, 10)}...${this.txHash.slice(-10)}`;
    },

    get submitButtonLabel() {
      return this.isSubmitting ? this.submittingText : this.submitButtonText;
    },

    async init() {
      if (!this.contractAddress) {
        console.warn('voteForm: contractAddress is required');
      }

      if (!Array.isArray(this.candidates) || this.candidates.length === 0) {
        console.warn('voteForm: candidates array is required and must not be empty');
      }

      if (window.addEventListener) {
        window.addEventListener('wallet-disconnected', () => {
          this.reset();
        });
      }
    },

    async submitVote() {
      if (!this.isValid) {
        return;
      }

      if (!this.contractAddress) {
        this.error = 'Contract address is not configured';
        return;
      }

      this.error = null;
      this.isSubmitting = true;

      try {
        const payload = {
          contractAddress: this.contractAddress,
          candidateId: this.selectedCandidateId,
          encryptedBallot: '',
        };

        this.$dispatch('vote-submitting', { payload });

        const hash = await buildAndSubmitVoteTx(payload);

        this.txHash = hash;
        this.hasVoted = true;

        this.$dispatch('vote-submitted', {
          txHash: hash,
          candidateId: this.selectedCandidateId
        });

        if (config.apiEndpoint) {
          await this.recordVoteOnBackend(hash, this.selectedCandidateId);
        }

      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Vote submission failed';
        this.error = errorMessage;
        this.$dispatch('vote-error', { error: errorMessage });
      } finally {
        this.isSubmitting = false;
      }
    },

    async recordVoteOnBackend(txHash, candidateId) {
      try {
        const response = await fetch(config.apiEndpoint || '/api/votes', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            txHash,
            candidateId,
          }),
        });

        if (!response.ok) {
          throw new Error(`Backend recording failed: ${response.statusText}`);
        }

        const data = await response.json();
        this.$dispatch('vote-recorded', { data });

      } catch (err) {
        console.error('Failed to record vote on backend:', err);
      }
    },

    reset() {
      this.selectedCandidateId = null;
      this.isSubmitting = false;
      this.hasVoted = false;
      this.txHash = null;
      this.error = null;
      this.$dispatch('vote-reset');
    },

    selectCandidate(candidateId) {
      if (this.isSubmitting || this.hasVoted) return;
      this.selectedCandidateId = candidateId;
    },

    async copyTxHash() {
      if (!this.txHash) return;

      try {
        await navigator.clipboard.writeText(this.txHash);
      } catch (err) {
        console.error('Failed to copy transaction hash:', err);
      }
    },

    handleCandidateKeyboard(event, candidateId) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.selectCandidate(candidateId);
      }
    },
  };
}

if (typeof window !== 'undefined' && window.Alpine) {
  window.Alpine.data('voteForm', voteForm);
}
