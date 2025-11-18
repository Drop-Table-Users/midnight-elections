<?php

return [
    // Standard Laravel validation messages
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'string' => 'The :attribute must be a string.',
    'array' => 'The :attribute must be an array.',
    'in' => 'The selected :attribute is invalid.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],

    // Custom validation messages
    'custom' => [
        'action' => [
            'required' => 'Action is required.',
            'in' => 'The action must be one of: open, close, register, vote.',
        ],
        'candidate_id' => [
            'required' => 'Candidate ID is required for registration.',
            'string' => 'Candidate ID must be a valid string.',
        ],
        'ballot_data' => [
            'array' => 'Ballot data must be an array.',
        ],
    ],

    // Attribute names
    'attributes' => [
        'action' => 'action',
        'candidate_id' => 'candidate ID',
        'ballot_data' => 'ballot data',
    ],

    // Error messages
    'errors' => [
        'wallet_not_connected' => 'Wallet is not connected.',
        'transaction_failed' => 'Transaction failed to process.',
        'invalid_candidate_id' => 'Invalid candidate ID format.',
        'election_not_open' => 'Election is not currently open.',
        'already_registered' => 'Candidate is already registered.',
        'already_voted' => 'You have already voted in this election.',
        'api_unavailable' => 'Elections API is currently unavailable.',
        'voting_not_started' => 'Voting has not started yet.',
        'voting_ended' => 'Voting has ended.',
        'election_or_candidate_not_found' => 'Election or candidate not found.',
        'blockchain_connection_error' => 'Error connecting to blockchain network. Please try again later.',
        'vote_processing_error' => 'An error occurred while processing your vote. Please try again.',
    ],

    // Success messages
    'success' => [
        'election_opened' => 'Election has been successfully opened.',
        'election_closed' => 'Election has been successfully closed.',
        'candidate_registered' => 'Candidate has been successfully registered.',
        'vote_cast' => 'Your vote has been successfully cast.',
        'wallet_connected' => 'Wallet connected successfully.',
        'wallet_disconnected' => 'Wallet disconnected successfully.',
    ],
];
