@extends('layout.app')

@section('title', $election->getTitle())

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('home') }}" style="color: var(--sk-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
        <span>&larr;</span>
        <span>{{ __('elections.election.back_to_elections') }}</span>
    </a>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: start; gap: 2rem; flex-wrap: wrap; margin-bottom: 2rem;">
        <div style="flex: 1;">
            <h1 style="margin-bottom: 0.5rem;">{{ $election->getTitle() }}</h1>

            @if($election->getDescription())
                <p style="color: var(--sk-gray-dark); font-size: 1.1rem; margin-bottom: 1.5rem;">
                    {{ $election->getDescription() }}
                </p>
            @endif

            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <strong style="color: var(--sk-blue);">
                        {{ __('elections.election.start') }}
                    </strong>
                    <span>{{ $election->start_date->format('d.m.Y H:i') }}</span>
                </div>
                <div>
                    <strong style="color: var(--sk-blue);">
                        {{ __('elections.election.end') }}
                    </strong>
                    <span>{{ $election->end_date->format('d.m.Y H:i') }}</span>
                </div>
            </div>
        </div>

        <div>
            @if($election->isOpen() && $election->start_date <= now() && $election->end_date >= now())
                <span style="display: inline-block; padding: 0.5rem 1rem; background-color: #28a745; color: white; border-radius: 4px; font-weight: 600;">
                    {{ __('elections.election.open') }}
                </span>
            @elseif($election->start_date > now())
                <span style="display: inline-block; padding: 0.5rem 1rem; background-color: var(--sk-gray); color: #333; border-radius: 4px; font-weight: 600;">
                    {{ __('elections.election.coming_soon') }}
                </span>
            @else
                <span style="display: inline-block; padding: 0.5rem 1rem; background-color: var(--sk-gray-dark); color: white; border-radius: 4px; font-weight: 600;">
                    {{ __('elections.election.closed') }}
                </span>
            @endif
        </div>
    </div>

    @if($election->contract_address)
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem;">
            <a href="{{ route('election.results', $election->id) }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background-color: var(--sk-blue); color: white; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background-color 0.2s;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                View Results
            </a>
            <a href="{{ route('election.verify', $election->id) }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background-color: #28a745; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background-color 0.2s;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Verify Your Vote
            </a>
        </div>
    @endif

    <div id="alert-container"></div>
    <div id="wallet-status-alert" style="display: none;" class="alert alert-info">
        {{ __('elections.election.connecting_wallet') }}
    </div>
</div>

@if($election->isOpen() && $election->start_date <= now() && $election->end_date >= now())
    <div class="card" style="margin-top: 2rem; background-color: #e7f3ff; border: 2px solid var(--sk-blue);">
        <h3 style="color: var(--sk-blue); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <path d="M9 12l2 2 4-4"></path>
            </svg>
            KYC Verification Required
        </h3>

        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; margin-bottom: 1.5rem; border-left: 4px solid var(--sk-blue);">
            <p style="color: #333; margin-bottom: 1rem; font-size: 1.05rem; line-height: 1.6;">
                <strong>You must complete KYC verification before voting.</strong> This ensures election integrity and prevents fraud.
            </p>
            <ol style="color: #555; margin-left: 1.5rem; line-height: 1.8; margin-bottom: 1rem;">
                <li>Complete the KYC verification process (if not already done)</li>
                <li>Wait for admin approval of your KYC submission</li>
                <li>Once approved, copy your <strong>Credential Hash</strong> from the admin</li>
                <li>Paste your credential hash below to prove your eligibility to vote</li>
            </ol>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="credential-hash" style="display: block; font-weight: 600; color: #333; margin-bottom: 0.5rem;">
                Credential Hash <span style="color: #dc3545;">*</span>
            </label>
            <input
                type="text"
                id="credential-hash"
                placeholder="Enter your credential hash (e.g., 0x1234...)"
                style="width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 0.95rem;"
                autocomplete="off"
            />
            <small style="color: var(--sk-gray-dark); display: block; margin-top: 0.5rem;">
                Your credential hash proves you are a verified voter without revealing your identity.
            </small>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('kyc') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Complete KYC Verification
            </a>
            <span style="color: var(--sk-gray-dark);">
                Don't have a credential hash yet? Start your KYC verification now.
            </span>
        </div>
    </div>
@endif

<div style="margin-top: 2rem;">
    <h2>{{ __('elections.election.candidates') }}</h2>

    @if($election->candidates->isEmpty())
        <div class="card" style="text-align: center; padding: 2rem;">
            <p style="color: var(--sk-gray-dark);">
                {{ __('elections.election.no_candidates') }}
            </p>
        </div>
    @else
        <div style="display: grid; gap: 1rem;">
            @foreach($election->candidates as $candidate)
                <div class="card candidate-card" style="transition: transform 0.2s, box-shadow 0.2s;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 2rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <h3 style="margin-bottom: 0.5rem; color: #333;">{{ $candidate->getName() }}</h3>

                            @if($candidate->getDescription())
                                <p style="color: var(--sk-gray-dark); margin-top: 0.5rem;">
                                    {{ $candidate->getDescription() }}
                                </p>
                            @endif
                        </div>

                        <div>
                            @if($election->isOpen() && $election->start_date <= now() && $election->end_date >= now())
                                <button
                                    onclick="voteForCandidate({{ $election->id }}, {{ $candidate->id }}, '{{ $candidate->blockchain_candidate_id }}', '{{ addslashes($candidate->getName()) }}')"
                                    class="btn btn-primary vote-btn"
                                    data-candidate-id="{{ $candidate->id }}"
                                    style="min-width: 120px;">
                                    {{ __('elections.election.vote') }}
                                </button>
                            @else
                                <button class="btn btn-secondary" disabled style="min-width: 120px;">
                                    {{ __('elections.election.unavailable') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if($election->isOpen() && $election->start_date <= now() && $election->end_date >= now())
    <div class="card" style="margin-top: 2rem; background-color: #fff3cd; border: 1px solid #ffc107;">
        <h3 style="color: #856404; margin-bottom: 0.5rem;">
            {{ __('elections.important_info.title') }}
        </h3>
        <ul style="color: #856404; margin-left: 1.5rem; line-height: 1.8;">
            <li>{{ __('elections.important_info.wallet_required') }}</li>
            <li>{{ __('elections.important_info.transaction_processing') }}</li>
            <li>{{ __('elections.important_info.anonymous_vote') }}</li>
        </ul>
        <div style="margin-top: 1rem;">
            <a href="{{ route('how-to-vote') }}" style="color: var(--sk-blue); font-weight: 600;">
                {{ __('elections.important_info.how_to_connect') }}
            </a>
        </div>
    </div>
@endif

<div id="wallet-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background-color: white; border-radius: 8px; padding: 2rem; max-width: 500px; margin: 1rem;">
        <h3 style="margin-bottom: 1rem;">{{ __('elections.wallet_modal.title') }}</h3>
        <p style="margin-bottom: 1.5rem; color: var(--sk-gray-dark);">
            {{ __('elections.wallet_modal.description') }}
        </p>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="closeWalletModal()" class="btn btn-secondary">
                {{ __('elections.wallet_modal.close') }}
            </button>
            <a href="{{ route('how-to-vote') }}" class="btn btn-primary">
                {{ __('elections.wallet_modal.how_to_connect') }}
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .candidate-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .vote-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vote-btn.loading {
        opacity: 0.7;
        cursor: wait;
    }

    #credential-hash {
        transition: border-color 0.3s ease;
    }

    #credential-hash:focus {
        outline: none;
        border-color: var(--sk-blue);
        box-shadow: 0 0 0 3px rgba(0, 101, 189, 0.1);
    }

    #credential-hash::placeholder {
        color: #999;
    }
</style>
@endpush

@push('scripts')
<script>
    const locale = '{{ app()->getLocale() }}';
    const csrfToken = '{{ csrf_token() }}';

    const translations = {
        sk: {
            voting: '{{ __('elections.js.voting') }}',
            votingFor: '{{ __('elections.js.voting_for') }}',
            voteSuccess: '{{ __('elections.js.vote_success') }}',
            voteError: '{{ __('elections.js.vote_error') }}',
            walletNotConnected: '{{ __('elections.js.wallet_not_connected') }}',
            electionNotActive: '{{ __('elections.js.election_not_active') }}',
            blockchainError: '{{ __('elections.js.blockchain_error') }}',
            vote: '{{ __('elections.js.vote') }}',
            connecting: '{{ __('elections.js.connecting') }}'
        },
        en: {
            voting: '{{ __('elections.js.voting') }}',
            votingFor: '{{ __('elections.js.voting_for') }}',
            voteSuccess: '{{ __('elections.js.vote_success') }}',
            voteError: '{{ __('elections.js.vote_error') }}',
            walletNotConnected: '{{ __('elections.js.wallet_not_connected') }}',
            electionNotActive: '{{ __('elections.js.election_not_active') }}',
            blockchainError: '{{ __('elections.js.blockchain_error') }}',
            vote: '{{ __('elections.js.vote') }}',
            connecting: '{{ __('elections.js.connecting') }}'
        }
    };

    const t = translations[locale];

    function showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    function showWalletModal() {
        document.getElementById('wallet-modal').style.display = 'flex';
    }

    function closeWalletModal() {
        document.getElementById('wallet-modal').style.display = 'none';
    }

    async function voteForCandidate(electionId, candidateId, blockchainCandidateId, candidateName) {
        const button = document.querySelector(`.vote-btn[data-candidate-id="${candidateId}"]`);
        const originalText = button.textContent;

        // Validate credential hash
        const credentialHashInput = document.getElementById('credential-hash');
        const credentialHash = credentialHashInput ? credentialHashInput.value.trim() : '';

        if (!credentialHash) {
            showAlert('Please enter your credential hash before voting. You must complete KYC verification first.', 'error');
            if (credentialHashInput) {
                credentialHashInput.focus();
                credentialHashInput.style.borderColor = '#dc3545';
                setTimeout(() => {
                    credentialHashInput.style.borderColor = '#ddd';
                }, 3000);
            }
            return;
        }

        // Basic validation for credential hash format
        if (credentialHash.length < 10) {
            showAlert('Invalid credential hash format. Please enter a valid hash from your KYC approval.', 'error');
            if (credentialHashInput) {
                credentialHashInput.focus();
                credentialHashInput.style.borderColor = '#dc3545';
                setTimeout(() => {
                    credentialHashInput.style.borderColor = '#ddd';
                }, 3000);
            }
            return;
        }

        try {
            button.disabled = true;
            button.classList.add('loading');
            button.textContent = `${t.votingFor} ${candidateName}...`;

            const voteUrl = `/vote/${electionId}/${candidateId}`;
            const response = await fetch(voteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    blockchain_candidate_id: blockchainCandidateId,
                    credential_hash: credentialHash
                })
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                showAlert(data.message || t.voteSuccess, 'success');
            } else {
                if (data.message && data.message.includes('wallet')) {
                    showWalletModal();
                }
                showAlert(data.message || t.voteError, 'error');
            }
        } catch (error) {
            console.error('Vote error:', error);
            showAlert(t.voteError, 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = originalText;
        }
    }

    window.addEventListener('DOMContentLoaded', function() {
        closeWalletModal();
    });
</script>
@endpush
