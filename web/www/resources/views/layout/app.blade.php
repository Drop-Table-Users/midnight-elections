<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.app_name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --sk-blue: #0B4EA2;
            --sk-red: #EE1C25;
            --sk-white: #ffffff;
            --sk-gray-light: #f5f5f5;
            --sk-gray: #e0e0e0;
            --sk-gray-dark: #666666;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--sk-white);
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: var(--sk-blue);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        nav a:hover {
            opacity: 0.8;
        }

        main {
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }

        footer {
            background-color: var(--sk-gray-light);
            padding: 2rem 0;
            margin-top: 4rem;
            border-top: 3px solid var(--sk-blue);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: var(--sk-gray-dark);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--sk-blue);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0a3d7f;
        }

        .btn-danger {
            background-color: var(--sk-red);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c91820;
        }

        .btn-secondary {
            background-color: var(--sk-gray);
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #d0d0d0;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        h1, h2, h3, h4, h5, h6 {
            margin-bottom: 1rem;
            color: var(--sk-blue);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            nav ul {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .footer-content {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="{{ route('home') }}" class="logo">
                    <span>{{ __('messages.app_name') }}</span>
                </a>
                <nav>
                    <ul>
                        <li><a href="{{ route('home') }}">{{ __('messages.nav.home') }}</a></li>
                        <li><a href="{{ route('how-to-vote') }}">{{ __('elections.how_to_vote.nav_title') }}</a></li>
                        <li>@include('components.language-switcher')</li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div>
                    <p>&copy; {{ date('Y') }} {{ __('messages.app_name') }}</p>
                </div>
                <div>
                    <p>{{ __('elections.home.footer_tech') }}</p>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
