<template>
  <div class="vote-form">
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Title -->
      <div v-if="title" class="form-title">
        <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
        <p v-if="description" class="text-gray-600 mt-2">{{ description }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="flex items-center justify-center py-12">
          <svg
            class="animate-spin h-8 w-8 text-blue-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span class="ml-3 text-gray-600">{{ loadingMessage }}</span>
        </div>
      </div>

      <!-- Candidate Selection -->
      <div v-else-if="!hasVoted && !txHash" class="candidate-selection">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Select a candidate
          <span class="text-red-500" aria-label="required">*</span>
        </label>

        <div class="candidates-grid" :class="gridClass">
          <div
            v-for="candidate in candidates"
            :key="candidate.id"
            class="candidate-option"
            :class="{ 'selected': selectedCandidateId === candidate.id }"
          >
            <label class="candidate-label">
              <input
                type="radio"
                :name="radioGroupName"
                :value="candidate.id"
                v-model="selectedCandidateId"
                :disabled="isSubmitting"
                class="candidate-radio"
                :aria-label="`Select ${candidate.name}`"
              />
              <div class="candidate-content">
                <div class="candidate-name">{{ candidate.name }}</div>
                <div v-if="candidate.description" class="candidate-description">
                  {{ candidate.description }}
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="error-message" role="alert">
          <svg
            class="w-5 h-5 inline-block mr-2"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd"
            />
          </svg>
          {{ error }}
        </div>

        <!-- Submit Button -->
        <div class="form-actions">
          <button
            type="submit"
            :disabled="!selectedCandidateId || isSubmitting"
            class="btn btn-primary btn-lg w-full"
            :class="{ 'opacity-50 cursor-not-allowed': !selectedCandidateId || isSubmitting }"
            :aria-busy="isSubmitting"
          >
            <span v-if="isSubmitting" class="inline-flex items-center">
              <svg
                class="animate-spin -ml-1 mr-3 h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ submittingText }}
            </span>
            <span v-else>{{ submitButtonText }}</span>
          </button>
        </div>
      </div>

      <!-- Success State -->
      <div v-else class="success-state">
        <div class="success-message">
          <svg
            class="w-16 h-16 text-green-500 mx-auto mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ successTitle }}</h3>
          <p class="text-gray-600">{{ successMessage }}</p>

          <!-- Transaction Hash -->
          <div v-if="txHash && showTransactionHash" class="transaction-hash">
            <span class="label">Transaction:</span>
            <code class="hash-value">{{ displayTxHash }}</code>
            <button
              v-if="showCopyButton"
              type="button"
              @click="copyTxHash"
              class="btn btn-sm btn-ghost ml-2"
              :aria-label="`Copy transaction hash ${txHash}`"
              title="Copy transaction hash"
            >
              <svg
                v-if="!copiedTxHash"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                />
              </svg>
              <svg
                v-else
                class="w-4 h-4 text-green-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </button>
          </div>

          <!-- Reset Button -->
          <button
            v-if="allowReset"
            type="button"
            @click="handleReset"
            class="btn btn-secondary mt-4"
          >
            {{ resetButtonText }}
          </button>
        </div>
      </div>
    </form>

    <!-- Slot for additional content -->
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useVoting } from '../composables/useVoting';
import type { VoteTxPayload } from '../midnight/types';

export interface Candidate {
  id: string | number;
  name: string;
  description?: string;
}

export interface VoteFormProps {
  /** Contract address for the election */
  contractAddress: string;
  /** List of candidates */
  candidates: Candidate[];
  /** Form title */
  title?: string;
  /** Form description */
  description?: string;
  /** Text for submit button */
  submitButtonText?: string;
  /** Text while submitting */
  submittingText?: string;
  /** Text for reset button */
  resetButtonText?: string;
  /** Success message title */
  successTitle?: string;
  /** Success message */
  successMessage?: string;
  /** Loading message */
  loadingMessage?: string;
  /** Show transaction hash in success state */
  showTransactionHash?: boolean;
  /** Show copy button for transaction hash */
  showCopyButton?: boolean;
  /** Allow resetting the form after submission */
  allowReset?: boolean;
  /** Loading state */
  loading?: boolean;
  /** Grid layout class for candidates */
  gridClass?: string;
  /** Radio group name */
  radioGroupName?: string;
}

const props = withDefaults(defineProps<VoteFormProps>(), {
  title: 'Cast Your Vote',
  description: 'Select your preferred candidate below',
  submitButtonText: 'Submit Vote',
  submittingText: 'Submitting...',
  resetButtonText: 'Vote Again',
  successTitle: 'Vote Submitted Successfully!',
  successMessage: 'Your vote has been recorded on the blockchain.',
  loadingMessage: 'Loading...',
  showTransactionHash: true,
  showCopyButton: true,
  allowReset: false,
  loading: false,
  gridClass: 'grid-cols-1 md:grid-cols-2 gap-4',
  radioGroupName: 'candidate-selection',
});

export interface VoteFormEmits {
  (e: 'submit', payload: VoteTxPayload): void;
  (e: 'success', txHash: string): void;
  (e: 'error', error: string): void;
  (e: 'reset'): void;
}

const emit = defineEmits<VoteFormEmits>();

// Use voting composable
const {
  isSubmitting,
  txHash,
  error,
  hasVoted,
  submitVote,
  reset: resetVoting,
} = useVoting({
  onVoteSubmitted: (hash) => {
    emit('success', hash);
  },
  onVoteFailed: (err) => {
    emit('error', err.message);
  },
});

// Local state
const selectedCandidateId = ref<string | number | null>(null);
const copiedTxHash = ref(false);

// Computed
const displayTxHash = computed(() => {
  if (!txHash.value) return '';
  return `${txHash.value.slice(0, 10)}...${txHash.value.slice(-10)}`;
});

// Methods
const handleSubmit = async () => {
  if (!selectedCandidateId.value) {
    return;
  }

  const payload: VoteTxPayload = {
    contractAddress: props.contractAddress,
    candidateId: selectedCandidateId.value,
    encryptedBallot: '', // This should be generated/encrypted in a real implementation
  };

  emit('submit', payload);

  try {
    await submitVote(payload);
  } catch (err) {
    // Error is already handled by composable callbacks
    console.error('Vote submission failed:', err);
  }
};

const handleReset = () => {
  selectedCandidateId.value = null;
  resetVoting();
  emit('reset');
};

const copyTxHash = async () => {
  if (!txHash.value) return;

  try {
    await navigator.clipboard.writeText(txHash.value);
    copiedTxHash.value = true;
    setTimeout(() => {
      copiedTxHash.value = false;
    }, 2000);
  } catch (err) {
    console.error('Failed to copy transaction hash:', err);
  }
};

// Watch for candidates changes
watch(() => props.candidates, () => {
  // Reset selection if candidates change
  if (selectedCandidateId.value) {
    const stillExists = props.candidates.some(c => c.id === selectedCandidateId.value);
    if (!stillExists) {
      selectedCandidateId.value = null;
    }
  }
});
</script>

<style scoped>
.vote-form {
  @apply w-full max-w-2xl mx-auto;
}

.form-title {
  @apply text-center mb-6;
}

.candidates-grid {
  @apply grid gap-4;
}

.candidate-option {
  @apply border-2 border-gray-200 rounded-lg transition-all duration-200 hover:border-blue-300 cursor-pointer;
}

.candidate-option.selected {
  @apply border-blue-500 bg-blue-50;
}

.candidate-label {
  @apply flex items-start p-4 cursor-pointer;
}

.candidate-radio {
  @apply mt-1 mr-3 h-5 w-5 text-blue-600 focus:ring-blue-500 cursor-pointer;
}

.candidate-content {
  @apply flex-1;
}

.candidate-name {
  @apply text-lg font-semibold text-gray-900;
}

.candidate-description {
  @apply text-sm text-gray-600 mt-1;
}

.error-message {
  @apply mt-4 px-4 py-3 bg-red-50 text-red-800 border border-red-200 rounded-lg;
}

.form-actions {
  @apply mt-6;
}

.btn {
  @apply px-4 py-2 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-500;
}

.btn-ghost {
  @apply bg-transparent hover:bg-gray-100 text-gray-600;
}

.btn-lg {
  @apply px-6 py-3 text-lg;
}

.btn-sm {
  @apply px-3 py-1 text-sm;
}

.success-state {
  @apply text-center py-8;
}

.success-message {
  @apply bg-green-50 border border-green-200 rounded-lg p-6;
}

.transaction-hash {
  @apply mt-4 flex items-center justify-center;
}

.label {
  @apply text-sm font-medium text-gray-700 mr-2;
}

.hash-value {
  @apply bg-white px-3 py-1 rounded border border-gray-300 text-sm font-mono;
}

.loading-state {
  @apply py-8;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
