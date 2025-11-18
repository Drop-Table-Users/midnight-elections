@extends('layout.app')

@section('title', __('elections.home.title'))

@section('content')
<div style="margin-bottom: 2rem;">
    <h1>{{ __('elections.home.title') }}</h1>
    <p style="color: var(--sk-gray-dark); font-size: 1.1rem;">
        {{ __('elections.home.subtitle') }}
    </p>
</div>

@if($elections->isEmpty())
    <div class="card" style="text-align: center; padding: 3rem;">
        <h2>{{ __('elections.home.no_elections') }}</h2>
        <p style="color: var(--sk-gray-dark); margin-top: 1rem;">
            {{ __('elections.home.check_back') }}
        </p>
    </div>
@else
    <div style="display: grid; gap: 1.5rem;">
        @foreach($elections as $election)
            <div class="card" style="position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 2rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <h2 style="margin-bottom: 0.5rem;">{{ $election->getTitle() }}</h2>

                        @if($election->getDescription())
                            <p style="color: var(--sk-gray-dark); margin-bottom: 1rem;">
                                {{ $election->getDescription() }}
                            </p>
                        @endif

                        <div style="display: flex; gap: 2rem; flex-wrap: wrap; margin-top: 1rem;">
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

                        <div style="margin-top: 1rem;">
                            <strong style="color: var(--sk-blue);">
                                {{ __('elections.election.candidates') }}
                            </strong>
                            <span>{{ $election->candidates->count() }}</span>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem; min-width: 200px;">
                        @if($election->isOpen() && $election->start_date <= now() && $election->end_date >= now())
                            <span style="display: inline-block; padding: 0.5rem 1rem; background-color: #28a745; color: white; border-radius: 4px; font-weight: 600; text-align: center;">
                                {{ __('elections.election.open') }}
                            </span>
                        @elseif($election->start_date > now())
                            <span style="display: inline-block; padding: 0.5rem 1rem; background-color: var(--sk-gray); color: #333; border-radius: 4px; font-weight: 600; text-align: center;">
                                {{ __('elections.election.coming_soon') }}
                            </span>
                        @else
                            <span style="display: inline-block; padding: 0.5rem 1rem; background-color: var(--sk-gray-dark); color: white; border-radius: 4px; font-weight: 600; text-align: center;">
                                {{ __('elections.election.closed') }}
                            </span>
                        @endif

                        <a href="{{ route('election.show', $election->id) }}" class="btn btn-primary" style="text-align: center;">
                            {{ __('elections.election.view_details') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="card" style="margin-top: 2rem; background-color: var(--sk-gray-light);">
    <h3>{{ __('elections.home.how_to_vote') }}</h3>
    <p style="margin-bottom: 1rem;">
        {{ __('elections.home.how_to_vote_description') }}
    </p>
    <a href="{{ route('how-to-vote') }}" class="btn btn-secondary">
        {{ __('elections.home.learn_more') }}
    </a>
</div>
@endsection
