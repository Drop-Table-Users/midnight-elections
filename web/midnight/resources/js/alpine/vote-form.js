// resources/js/alpine/vote-form.js
import { buildAndSubmitVoteTx } from '../midnight/client';

/**
 * Alpine.js component for voting form
 *
 * @example
 * ```html
 * <div x-data="voteForm({
 *   contractAddress: '0x...',
 *   candidates: [
 *     { id: 1, name: 'Candidate A', description: 'Description A' },
 *     { id: 2, name: 'Candidate B', description: 'Description B' }
 *   ]
 * })">
 *   <form @submit.prevent="submitVote">
 *     <template x-for="candidate in candidates" :key="candidate.id">
 *       <label>
 *         <input type="radio" :value="candidate.id" x-model="selectedCandidateId">
 *         <span x-text="candidate.name"></span>
 *         <p x-text="candidate.description"></p>
 *       </label>
 *     </template>
 *
 *     <div x-show="error" x-text="error"></div>
 *
 *     <button type="submit" :disabled="!selectedCandidateId || isSubmitting">
 *       <span x-show="isSubmitting">Submitting...</span>
 *       <span x-show="!isSubmitting">Submit Vote</span>
 *     </button>
 *   </form>
 *
 *   <div x-show="hasVoted">
 *     <p>Vote submitted! Transaction: <code x-text="txHash"></code></p>
 *   </div>
 * </div>
 * ```
 */
export default function voteForm(config = {}) {
  return {
    // Configuration
    contractAddress: config.contractAddress || '',
    candidates: config.candidates || [],
    submitButtonText: config.submitButtonText || 'Submit Vote',
    submittingText: config.submittingText || 'Submitting...',
    successMessage: config.successMessage || 'Your vote has been submitted successfully!',

    // State
    selectedCandidateId: null,
    isSubmitting: false,
    hasVoted: false,
    txHash: null,
    error: null,
    loading: config.loading || false,

    // Computed
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

    // Methods
    async init() {
      // Validation
      if (!this.contractAddress) {
        console.warn('voteForm: contractAddress is required');
      }

      if (!Array.isArray(this.candidates) || this.candidates.length === 0) {
        console.warn('voteForm: candidates array is required and must not be empty');
      }

      // Listen for wallet changes
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
          encryptedBallot: '', // TODO: In a real implementation, this should be encrypted
        };

        // Dispatch event before submission
        this.$dispatch('vote-submitting', { payload });

        const hash = await buildAndSubmitVoteTx(payload);

        this.txHash = hash;
        this.hasVoted = true;

        // Dispatch success event
        this.$dispatch('vote-submitted', {
          txHash: hash,
          candidateId: this.selectedCandidateId
        });

        // Optional: Send to backend
        if (config.apiEndpoint) {
          await this.recordVoteOnBackend(hash, this.selectedCandidateId);
        }

      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Vote submission failed';
        this.error = errorMessage;

        // Dispatch error event
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
            // Include CSRF token if using Laravel
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

        // Dispatch backend success event
        this.$dispatch('vote-recorded', { data });

      } catch (err) {
        console.error('Failed to record vote on backend:', err);
        // Note: We don't set this.error here as the blockchain submission was successful
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
        // You could set a temporary "copied" flag here if needed
      } catch (err) {
        console.error('Failed to copy transaction hash:', err);
      }
    },

    // Helper for keyboard navigation
    handleCandidateKeyboard(event, candidateId) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.selectCandidate(candidateId);
      }
    },
  };
}

// Auto-register with Alpine if available
if (typeof window !== 'undefined' && window.Alpine) {
  window.Alpine.data('voteForm', voteForm);
}
