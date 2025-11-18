<div class="language-switcher" style="display: flex; gap: 0.5rem; align-items: center;">
    @php
        $currentLocale = app()->getLocale();
        $currentRouteName = request()->route()->getName();

        // Map Slovak routes to English routes
        $routeMapping = [
            'home' => 'en.home',
            'how-to-vote' => 'en.how-to-vote',
            'election.show' => 'en.election.show',
            'vote' => 'en.vote',
            'en.home' => 'home',
            'en.how-to-vote' => 'how-to-vote',
            'en.election.show' => 'election.show',
            'en.vote' => 'vote',
        ];

        // Get route parameters
        $routeParams = request()->route()->parameters();

        // Generate opposite language URL
        if (isset($routeMapping[$currentRouteName])) {
            $oppositeRouteName = $routeMapping[$currentRouteName];
            $oppositeUrl = route($oppositeRouteName, $routeParams);
        } else {
            // Fallback to home page
            $oppositeUrl = $currentLocale === 'sk' ? route('en.home') : route('home');
        }

        $oppositeName = $currentLocale === 'sk' ? 'EN' : 'SK';
    @endphp

    <span class="locale-current" style="color: white; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 4px; background-color: rgba(255,255,255,0.2);">
        {{ $currentLocale === 'sk' ? 'SK' : 'EN' }}
    </span>

    <a href="{{ $oppositeUrl }}"
       class="locale-link"
       style="color: white; text-decoration: none; padding: 0.25rem 0.5rem; border-radius: 4px; background-color: rgba(255,255,255,0.1); transition: background-color 0.2s;"
       onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'"
       onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
        {{ $oppositeName }}
    </a>
</div>
