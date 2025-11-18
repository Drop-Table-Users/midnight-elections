<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Midnight</title>

    {{-- Midnight CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/midnight/midnight.css') }}">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    {{-- Midnight JavaScript --}}
    <script type="module">
        import Midnight from '{{ asset('vendor/midnight/midnight.es.js') }}';

        // Initialize Midnight API
        window.midnightConfig = {
            apiUrl: '{{ config('midnight.bridge.base_uri', '/api/midnight') }}',
            network: '{{ config('midnight.network', 'testnet') }}',
            autoInit: true,
            debug: {{ config('app.debug') ? 'true' : 'false' }}
        };

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            Midnight.initialize(window.midnightConfig);
        });
    </script>

    @stack('scripts')
</body>
</html>
