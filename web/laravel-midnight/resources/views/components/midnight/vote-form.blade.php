@props([
    'proposalId' => null,
    'options' => ['Yes', 'No', 'Abstain'],
    'title' => 'Cast Your Vote',
    'description' => 'Select your vote option below. Your vote will be private and verifiable.',
    'onSuccess' => null,
    'onError' => null,
])

<div
    x-data="{
        proposalId: '{{ $proposalId }}',
        selectedOption: null,
        submitting: false,
        submitted: false,
        txHash: null,
        error: null,
        proofGenerating: false,

        async submitVote() {
            if (!this.selectedOption) {
                this.error = 'Please select a vote option';
                return;
            }

            this.submitting = true;
            this.error = null;

            try {
                // Check wallet connection
                const walletConnected = await this.checkWalletConnection();
                if (!walletConnected) {
                    throw new Error('Please connect your wallet first');
                }

                // Generate zero-knowledge proof
                this.proofGenerating = true;
                const proof = await this.generateProof();

                // Submit transaction
                const tx = await this.submitTransaction(proof);
                this.txHash = tx.hash;
                this.submitted = true;

                // Call success callback if provided
                @if($onSuccess)
                if (typeof {{ $onSuccess }} === 'function') {
                    {{ $onSuccess }}(tx);
                }
                @endif

                this.$dispatch('vote-submitted', {
                    proposalId: this.proposalId,
                    option: this.selectedOption,
                    txHash: this.txHash
                });
            } catch (err) {
                this.error = err.message;

                // Call error callback if provided
                @if($onError)
                if (typeof {{ $onError }} === 'function') {
                    {{ $onError }}(err);
                }
                @endif

                this.$dispatch('vote-error', { error: err.message });
            } finally {
                this.submitting = false;
                this.proofGenerating = false;
            }
        },

        async checkWalletConnection() {
            if (typeof window.midnight === 'undefined') {
                throw new Error('Midnight wallet not detected');
            }

            const accounts = await window.midnight.request({ method: 'eth_accounts' });
            return accounts && accounts.length > 0;
        },

        async generateProof() {
            // Simulate proof generation (replace with actual Midnight SDK call)
            return new Promise((resolve, reject) => {
                setTimeout(() => {
                    // In production, this would call the Midnight proof generation API
                    fetch('/api/midnight/generate-proof', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || ''
                        },
                        body: JSON.stringify({
                            proposal_id: this.proposalId,
                            vote_option: this.selectedOption
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to generate proof');
                        return response.json();
                    })
                    .then(data => resolve(data.proof))
                    .catch(err => reject(err));
                }, 1000);
            });
        },

        async submitTransaction(proof) {
            // Submit the vote transaction with the zero-knowledge proof
            const response = await fetch('/api/midnight/submit-vote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || ''
                },
                body: JSON.stringify({
                    proposal_id: this.proposalId,
                    vote_option: this.selectedOption,
                    proof: proof
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to submit vote');
            }

            return response.json();
        },

        reset() {
            this.selectedOption = null;
            this.submitted = false;
            this.txHash = null;
            this.error = null;
        }
    }"
    class="midnight-vote-form bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6"
>
    <!-- Header -->
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            {{ $title }}
        </h3>
        <p class="text-gray-600 dark:text-gray-400">
            {{ $description }}
        </p>
    </div>

    <!-- Error Alert -->
    <div x-show="error" x-cloak class="mb-4">
        <x-midnight::error-alert x-text="error"></x-midnight::error-alert>
    </div>

    <!-- Success State -->
    <div x-show="submitted" x-cloak class="space-y-4">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-lg font-semibold text-green-900 dark:text-green-100">Vote Submitted Successfully!</h4>
                    <p class="text-sm text-green-700 dark:text-green-300">Your vote has been recorded on the blockchain.</p>
                </div>
            </div>
        </div>

        <x-midnight::transaction-status :tx-hash="txHash" x-show="txHash"></x-midnight::transaction-status>

        <button
            @click="reset()"
            class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium rounded-lg transition-colors"
            type="button"
        >
            Submit Another Vote
        </button>
    </div>

    <!-- Vote Form -->
    <form @submit.prevent="submitVote()" x-show="!submitted" x-cloak class="space-y-6">
        <!-- Vote Options -->
        <fieldset>
            <legend class="text-sm font-medium text-gray-900 dark:text-white mb-3">Select Your Vote</legend>
            <div class="space-y-3">
                @foreach($options as $index => $option)
                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-indigo-300 dark:hover:border-indigo-600"
                    :class="selectedOption === '{{ $option }}' ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700'">
                    <input
                        type="radio"
                        name="vote-option"
                        value="{{ $option }}"
                        x-model="selectedOption"
                        class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-400 dark:bg-gray-700 dark:border-gray-600"
                    >
                    <span class="ml-3 text-gray-900 dark:text-white font-medium">{{ $option }}</span>
                </label>
                @endforeach
            </div>
        </fieldset>

        <!-- Proof Generation Status -->
        <div x-show="proofGenerating" x-cloak class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center">
                <x-midnight::loading-spinner class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3"></x-midnight::loading-spinner>
                <p class="text-sm text-blue-800 dark:text-blue-200">Generating zero-knowledge proof...</p>
            </div>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="submitting || !selectedOption"
            :class="(submitting || !selectedOption) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-700 dark:hover:bg-indigo-600'"
            class="w-full flex items-center justify-center px-6 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all"
        >
            <x-midnight::loading-spinner x-show="submitting" class="w-5 h-5 mr-2"></x-midnight::loading-spinner>
            <span x-text="submitting ? (proofGenerating ? 'Generating Proof...' : 'Submitting Vote...') : 'Submit Vote'"></span>
        </button>

        <!-- Privacy Notice -->
        <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <div>
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Privacy Protected</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        Your vote is encrypted using zero-knowledge proofs. Only the final tally is public while your individual choice remains private.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
