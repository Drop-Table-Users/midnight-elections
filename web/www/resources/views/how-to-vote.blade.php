@extends('layout.app')

@section('title', __('elections.how_to_vote.title'))

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="margin-bottom: 1rem;">{{ __('elections.how_to_vote.title') }}</h1>
    <p style="color: var(--sk-gray-dark); font-size: 1.1rem; margin-bottom: 2rem;">
        {{ __('elections.how_to_vote.intro') }}
    </p>

    <div class="card" style="margin-bottom: 2rem;">
        <h2>{{ __('elections.how_to_vote.step1_title') }}</h2>
        <p style="margin-bottom: 1rem;">
            {{ __('elections.how_to_vote.step1_description') }}
        </p>

        <div style="background-color: var(--sk-gray-light); padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
            <h3 style="color: #333; margin-bottom: 1rem;">{{ __('elections.how_to_vote.step1_download') }}</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="https://www.lace.io/" target="_blank" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                    {{ __('elections.how_to_vote.step1_download_button') }}
                </a>
                <p style="font-size: 0.9rem; color: var(--sk-gray-dark);">
                    {{ __('elections.how_to_vote.step1_browsers') }}
                </p>
            </div>
        </div>

        <div style="border-left: 4px solid var(--sk-blue); padding-left: 1rem; margin-top: 1.5rem;">
            <strong>{{ __('elections.how_to_vote.step1_note') }}</strong>
            {{ __('elections.how_to_vote.step1_note_text') }}
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <h2>{{ __('elections.how_to_vote.step2_title') }}</h2>
        <p style="margin-bottom: 1rem;">
            {{ __('elections.how_to_vote.step2_description') }}
        </p>

        <div style="background-color: #e7f3ff; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--sk-blue);">
            <h4 style="color: var(--sk-blue); margin-bottom: 0.5rem;">
                {{ __('elections.how_to_vote.step2_what_is_midnight') }}
            </h4>
            <p style="margin-bottom: 0.5rem;">
                {{ __('elections.how_to_vote.step2_midnight_description') }}
            </p>
            <p style="font-weight: 600; color: var(--sk-blue);">
                {{ __('elections.how_to_vote.step2_guarantee') }}
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <h2>{{ __('elections.how_to_vote.step3_title') }}</h2>
        <p style="margin-bottom: 1rem;">
            {{ __('elections.how_to_vote.step3_description') }}
        </p>

        <ul style="margin-left: 1.5rem; line-height: 2; margin-bottom: 1.5rem;">
            <li>{{ __('elections.how_to_vote.step3_req1') }}</li>
            <li>{{ __('elections.how_to_vote.step3_req2') }}</li>
            <li>{{ __('elections.how_to_vote.step3_req3') }}</li>
            <li>{{ __('elections.how_to_vote.step3_req4') }}</li>
        </ul>
    </div>

    <!-- KYC Requirement Section -->
    <div class="card" style="margin-bottom: 2rem; background-color: #e3f2fd; border: 2px solid var(--sk-blue);">
        <h2 style="color: var(--sk-blue); display: flex; align-items: center; gap: 0.5rem;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            {{ __('elections.how_to_vote.kyc_title') }}
        </h2>
        <p style="margin-bottom: 1.5rem; font-size: 1.1rem; font-weight: 600; color: var(--sk-blue);">
            {{ __('elections.how_to_vote.kyc_requirement') }}
        </p>

        <h3 style="color: #333; margin-bottom: 1rem;">{{ __('elections.how_to_vote.kyc_process_title') }}</h3>
        <ol style="margin-left: 1.5rem; line-height: 2; margin-bottom: 1.5rem;">
            <li>{{ __('elections.how_to_vote.kyc_step1') }}</li>
            <li>{{ __('elections.how_to_vote.kyc_step2') }}</li>
            <li>{{ __('elections.how_to_vote.kyc_step3') }}</li>
            <li>{{ __('elections.how_to_vote.kyc_step4') }}</li>
        </ol>

        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-bottom: 1.5rem;">
            <strong style="color: #856404;">{{ __('elections.how_to_vote.kyc_important') }}</strong>
            <p style="color: #856404; margin-top: 0.5rem;">
                {{ __('elections.how_to_vote.kyc_important_text') }}
            </p>
        </div>

        <a href="{{ route('kyc.create') }}" style="display: inline-block; background-color: var(--sk-blue); color: white; padding: 1rem 2rem; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background-color 0.2s;">
            {{ __('elections.how_to_vote.kyc_button') }}
        </a>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <h2>{{ __('elections.how_to_vote.step4_title') }}</h2>
        <p style="margin-bottom: 1.5rem;">
            {{ __('elections.how_to_vote.step4_description') }}
        </p>

        <div style="display: grid; gap: 1rem;">
            <div style="display: flex; gap: 1rem; align-items: start;">
                <div style="background-color: var(--sk-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                    1
                </div>
                <div>
                    <h4 style="color: #333; margin-bottom: 0.25rem;">
                        {{ __('elections.how_to_vote.step4_1_title') }}
                    </h4>
                    <p style="color: var(--sk-gray-dark);">
                        {{ __('elections.how_to_vote.step4_1_description') }}
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: start;">
                <div style="background-color: var(--sk-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                    2
                </div>
                <div>
                    <h4 style="color: #333; margin-bottom: 0.25rem;">
                        {{ __('elections.how_to_vote.step4_2_title') }}
                    </h4>
                    <p style="color: var(--sk-gray-dark);">
                        {{ __('elections.how_to_vote.step4_2_description') }}
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: start;">
                <div style="background-color: var(--sk-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                    3
                </div>
                <div>
                    <h4 style="color: #333; margin-bottom: 0.25rem;">
                        {{ __('elections.how_to_vote.step4_3_title') }}
                    </h4>
                    <p style="color: var(--sk-gray-dark);">
                        {{ __('elections.how_to_vote.step4_3_description') }}
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: start;">
                <div style="background-color: var(--sk-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                    4
                </div>
                <div>
                    <h4 style="color: #333; margin-bottom: 0.25rem;">
                        {{ __('elections.how_to_vote.step4_4_title') }}
                    </h4>
                    <p style="color: var(--sk-gray-dark);">
                        {{ __('elections.how_to_vote.step4_4_description') }}
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: start;">
                <div style="background-color: var(--sk-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                    5
                </div>
                <div>
                    <h4 style="color: #333; margin-bottom: 0.25rem;">
                        {{ __('elections.how_to_vote.step4_5_title') }}
                    </h4>
                    <p style="color: var(--sk-gray-dark);">
                        {{ __('elections.how_to_vote.step4_5_description') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="background-color: #fff3cd; border: 1px solid #ffc107;">
        <h2 style="color: #856404;">{{ __('elections.how_to_vote.security_title') }}</h2>
        <ul style="margin-left: 1.5rem; line-height: 2; color: #856404;">
            <li>{{ __('elections.how_to_vote.security_1') }}</li>
            <li>{{ __('elections.how_to_vote.security_2') }}</li>
            <li>{{ __('elections.how_to_vote.security_3') }}</li>
            <li>{{ __('elections.how_to_vote.security_4') }}</li>
        </ul>
    </div>

    <div style="margin-top: 2rem; text-align: center;">
        <a href="{{ route('home') }}" class="btn btn-primary" style="text-decoration: none;">
            {{ __('elections.how_to_vote.back_to_elections') }}
        </a>
    </div>
</div>
@endsection
