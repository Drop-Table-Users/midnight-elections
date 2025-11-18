<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('elections.kyc.title') }} - Identity Verification Provider</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --kyc-primary: #00695C;
            --kyc-primary-dark: #004D40;
            --kyc-accent: #26A69A;
            --kyc-light: #B2DFDB;
            --kyc-bg: #E0F2F1;
            --kyc-white: #ffffff;
            --kyc-gray: #757575;
            --kyc-gray-light: #F5F5F5;
            --kyc-danger: #D32F2F;
            --kyc-success: #388E3C;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--kyc-bg) 0%, var(--kyc-white) 100%);
            color: #263238;
            line-height: 1.6;
            min-height: 100vh;
        }

        .kyc-header {
            background: linear-gradient(135deg, var(--kyc-primary-dark) 0%, var(--kyc-primary) 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 20px rgba(0, 105, 92, 0.3);
            position: relative;
            overflow: hidden;
            margin-bottom: 3rem;
        }

        .kyc-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .kyc-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .kyc-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            backdrop-filter: blur(10px);
        }

        .kyc-brand-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: white;
        }

        .kyc-brand-text p {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .language-switcher {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .locale-current {
            color: white;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: rgba(255,255,255,0.25);
        }

        .locale-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: rgba(255,255,255,0.1);
            transition: all 0.2s;
            font-weight: 500;
        }

        .locale-link:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .kyc-main {
            max-width: 900px;
            margin: 0 auto 3rem;
            padding: 0 1.5rem;
        }

        .security-banner {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 105, 92, 0.15);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border-left: 6px solid var(--kyc-accent);
        }

        .security-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--kyc-light) 0%, var(--kyc-accent) 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            flex-shrink: 0;
        }

        .security-info h2 {
            color: var(--kyc-primary);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .security-info p {
            color: var(--kyc-gray);
            font-size: 1rem;
        }

        .requirements-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 105, 92, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .requirements-section h3 {
            color: var(--kyc-primary);
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .requirement-card {
            background: var(--kyc-bg);
            padding: 1.25rem;
            border-radius: 8px;
            border: 2px solid var(--kyc-light);
            transition: all 0.3s;
        }

        .requirement-card:hover {
            border-color: var(--kyc-accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(38, 166, 154, 0.2);
        }

        .requirement-card strong {
            color: var(--kyc-primary);
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .requirement-card span {
            color: var(--kyc-gray);
            font-size: 0.95rem;
        }

        .wallet-status-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 105, 92, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .wallet-status {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--kyc-primary) 0%, var(--kyc-primary-dark) 100%);
            color: white;
        }

        .wallet-status.disconnected {
            background: linear-gradient(135deg, var(--kyc-danger) 0%, #B71C1C 100%);
        }

        .wallet-status.connected {
            background: linear-gradient(135deg, var(--kyc-success) 0%, #2E7D32 100%);
        }

        .wallet-icon-large {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .wallet-info {
            flex: 1;
        }

        .wallet-info strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .wallet-address {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            opacity: 0.95;
            word-break: break-all;
        }

        .connect-wallet-btn {
            background: white;
            color: var(--kyc-primary);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .connect-wallet-btn:hover {
            background: var(--kyc-gray-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .form-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 105, 92, 0.1);
            padding: 2.5rem;
        }

        .form-section h3 {
            color: var(--kyc-primary);
            font-size: 1.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--kyc-light);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--kyc-primary);
            font-size: 1rem;
        }

        .form-group label .required {
            color: var(--kyc-danger);
            margin-left: 0.25rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: var(--kyc-white);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--kyc-accent);
            box-shadow: 0 0 0 4px rgba(38, 166, 154, 0.1);
            background: white;
        }

        .form-control.is-invalid {
            border-color: var(--kyc-danger);
        }

        .invalid-feedback {
            color: var(--kyc-danger);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: block;
            font-weight: 500;
        }

        .alert {
            padding: 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background-color: #FFEBEE;
            color: var(--kyc-danger);
            border: 2px solid #FFCDD2;
        }

        .alert-error ul {
            margin: 0.5rem 0 0 1.5rem;
        }

        .form-actions {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid var(--kyc-bg);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--kyc-accent) 0%, var(--kyc-primary) 100%);
            color: white;
            padding: 1.25rem 3rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(38, 166, 154, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(38, 166, 154, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .footer {
            background: var(--kyc-primary-dark);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
            text-align: center;
        }

        .footer p {
            opacity: 0.9;
        }

        .powered-by {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            opacity: 0.8;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .kyc-header {
                padding: 1.5rem 0;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .kyc-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .kyc-brand-text h1 {
                font-size: 1.25rem;
            }

            .security-banner {
                flex-direction: column;
                text-align: center;
            }

            .security-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }

            .requirements-grid {
                grid-template-columns: 1fr;
            }

            .wallet-status {
                flex-direction: column;
                text-align: center;
            }

            .form-section {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="kyc-header">
        <div class="container">
            <div class="header-content">
                <div class="kyc-brand">
                    <div class="kyc-icon">🔐</div>
                    <div class="kyc-brand-text">
                        <h1>{{ app()->getLocale() === 'sk' ? 'Poskytovateľ overenia totožnosti' : 'Identity Verification Provider' }}</h1>
                        <p>{{ app()->getLocale() === 'sk' ? 'Zabezpečené Midnight blockchain technológiou' : 'Secured by Midnight blockchain technology' }}</p>
                    </div>
                </div>
                @php
                    $currentLocale = app()->getLocale();
                    $currentRouteName = request()->route()->getName();

                    // Map Slovak routes to English routes for KYC
                    $routeMapping = [
                        'kyc.create' => 'en.kyc.create',
                        'en.kyc.create' => 'kyc.create',
                    ];

                    // Generate opposite language URL
                    if (isset($routeMapping[$currentRouteName])) {
                        $oppositeRouteName = $routeMapping[$currentRouteName];
                        $oppositeUrl = route($oppositeRouteName);
                    } else {
                        // Fallback
                        $oppositeUrl = $currentLocale === 'sk' ? route('en.kyc.create') : route('kyc.create');
                    }

                    $oppositeName = $currentLocale === 'sk' ? 'EN' : 'SK';
                @endphp
                <div class="language-switcher">
                    <span class="locale-current">{{ $currentLocale === 'sk' ? 'SK' : 'EN' }}</span>
                    <a href="{{ $oppositeUrl }}" class="locale-link">{{ $oppositeName }}</a>
                </div>
            </div>
        </div>
    </header>

    <main class="kyc-main">
        <div class="security-banner">
            <div class="security-icon">🛡️</div>
            <div class="security-info">
                <h2>{{ __('elections.kyc.title') }}</h2>
                <p>{{ __('elections.kyc.subtitle') }}</p>
            </div>
        </div>

        <div class="requirements-section">
            <h3>
                <span>📋</span>
                {{ __('elections.kyc.form.requirements_title') }}
            </h3>
            <div class="requirements-grid">
                <div class="requirement-card">
                    <strong>✓ {{ app()->getLocale() === 'sk' ? '18+' : '18+ Years Old' }}</strong>
                    <span>{{ __('elections.kyc.form.requirement_18') }}</span>
                </div>
                <div class="requirement-card">
                    <strong>✓ {{ app()->getLocale() === 'sk' ? 'Slovenský občan' : 'Slovak Citizen' }}</strong>
                    <span>{{ __('elections.kyc.form.requirement_sk') }}</span>
                </div>
                <div class="requirement-card">
                    <strong>✓ Lace Wallet</strong>
                    <span>{{ __('elections.kyc.form.requirement_wallet') }}</span>
                </div>
            </div>
        </div>

        <div id="message-container"></div>

        @if($errors->any())
            <div class="alert alert-error">
                <strong>{{ __('elections.admin.create.fix_errors') }}</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="wallet-status-card">
            <div id="wallet-status-container">
                <div class="wallet-status disconnected">
                    <div class="wallet-icon-large">💳</div>
                    <div class="wallet-info">
                        <strong>{{ __('elections.wallet.not_found') }}</strong>
                        <div class="wallet-address">{{ __('elections.wallet.connect_first') }}</div>
                    </div>
                    <button type="button" class="connect-wallet-btn" onclick="connectWallet()">
                        {{ app()->getLocale() === 'sk' ? 'Pripojiť peňaženku' : 'Connect Wallet' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>{{ app()->getLocale() === 'sk' ? 'Formulár overenia totožnosti' : 'Identity Verification Form' }}</h3>

            <form action="{{ route('kyc.store') }}" method="POST" id="kyc-form">
                @csrf

                <input type="hidden" name="wallet_address" id="wallet_address" value="{{ old('wallet_address') }}">

                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">
                            {{ __('elections.kyc.form.full_name') }}
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control @error('full_name') is-invalid @enderror"
                            value="{{ old('full_name') }}"
                            required
                            placeholder="{{ app()->getLocale() === 'sk' ? 'Ján Novák' : 'John Doe' }}"
                        >
                        @error('full_name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="national_id">
                            {{ __('elections.kyc.form.national_id') }}
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="national_id"
                            name="national_id"
                            class="form-control @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id') }}"
                            required
                            placeholder="AB123456"
                        >
                        @error('national_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">
                            {{ __('elections.kyc.form.date_of_birth') }}
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="date_of_birth"
                            name="date_of_birth"
                            class="form-control @error('date_of_birth') is-invalid @enderror"
                            value="{{ old('date_of_birth') }}"
                            placeholder="DD.MM.YYYY"
                            required
                            readonly
                        >
                        @error('date_of_birth')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="nationality">
                            {{ __('elections.kyc.form.nationality') }}
                            <span class="required">*</span>
                        </label>
                        <select
                            id="nationality"
                            name="nationality"
                            class="form-control @error('nationality') is-invalid @enderror"
                            required
                        >
                            <option value="">{{ app()->getLocale() === 'sk' ? '-- Vyberte štátnu príslušnosť --' : '-- Select Nationality --' }}</option>
                            <option value="SK" {{ old('nationality', 'SK') == 'SK' ? 'selected' : '' }}>{{ app()->getLocale() === 'sk' ? 'Slovensko' : 'Slovakia' }}</option>
                        </select>
                        @error('nationality')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submit-btn" disabled>
                        {{ __('elections.kyc.form.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ app()->getLocale() === 'sk' ? 'Poskytovateľ overenia totožnosti' : 'Identity Verification Provider' }}</p>
            <div class="powered-by">
                <span>⚡</span>
                <span>{{ app()->getLocale() === 'sk' ? 'Poháňané Midnight blockchain technológiou' : 'Powered by Midnight blockchain technology' }}</span>
            </div>
        </div>
    </footer>

    <script>
        let walletConnected = false;
        let walletAddress = null;
        let laceWalletAPI = null;

        // Show message in page
        function showMessage(message, type = 'error') {
            const container = document.getElementById('message-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';

            container.innerHTML = `
                <div class="alert ${alertClass}" style="margin-bottom: 1.5rem;">
                    <strong>${type === 'success' ? '✓' : '✗'}</strong>
                    <div>${message}</div>
                </div>
            `;

            // Scroll to message
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    container.innerHTML = '';
                }, 5000);
            }
        }

        function clearMessage() {
            document.getElementById('message-container').innerHTML = '';
        }

        async function connectWallet() {
            try {
                console.log('Connecting to Lace Beta Wallet for Midnight Network...');

                // Check if Lace Midnight wallet is installed
                if (typeof window.midnight === 'undefined' || typeof window.midnight.mnLace === 'undefined') {
                    throw new Error('Lace Beta Wallet not found. Please install Lace Beta Wallet for Midnight Network.');
                }

                // Request wallet access
                console.log('Requesting wallet access from Lace Beta Wallet...');
                laceWalletAPI = await window.midnight.mnLace.enable();

                if (!laceWalletAPI) {
                    throw new Error('User denied wallet access');
                }

                // Get wallet state
                console.log('Fetching wallet information...');
                const walletState = await laceWalletAPI.state();
                const uris = await window.midnight.mnLace.serviceUriConfig();

                walletConnected = true;
                walletAddress = walletState.address || 'N/A';

                console.log('Lace Beta Wallet connected successfully!');
                console.log('Address: ' + walletState.address);
                console.log('Indexer: ' + uris.indexerUri);
                console.log('Prover: ' + uris.proverServerUri);

                updateWalletStatus();
            } catch (error) {
                console.error('Failed to connect Lace Beta Wallet: ' + error.message);
                console.error('Make sure Lace Beta Wallet extension is installed and unlocked.');
                showMessage('{{ __("elections.wallet.connection_error") }}<br><br>' + error.message, 'error');
            }
        }

        function updateWalletStatus() {
            const statusContainer = document.getElementById('wallet-status-container');
            const submitBtn = document.getElementById('submit-btn');
            const walletAddressInput = document.getElementById('wallet_address');

            if (walletConnected && walletAddress) {
                statusContainer.innerHTML = `
                    <div class="wallet-status connected">
                        <div class="wallet-icon-large">✅</div>
                        <div class="wallet-info">
                            <strong>{{ __("elections.wallet.connected_wallet") }}</strong>
                            <div class="wallet-address">${walletAddress}</div>
                        </div>
                    </div>
                `;
                submitBtn.disabled = false;
                walletAddressInput.value = walletAddress;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Check for wallet on page load
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize Flatpickr for date of birth field
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());

            flatpickr("#date_of_birth", {
                dateFormat: "d.m.Y",
                minDate: "01.01.1900",
                maxDate: maxDate,
                allowInput: false,
                disableMobile: true
            });

            if (typeof window.midnight !== 'undefined' && typeof window.midnight.mnLace !== 'undefined') {
                try {
                    // Try to get wallet state if already connected
                    laceWalletAPI = await window.midnight.mnLace.enable();
                    if (laceWalletAPI) {
                        const walletState = await laceWalletAPI.state();
                        if (walletState && walletState.address) {
                            walletAddress = walletState.address;
                            walletConnected = true;
                            updateWalletStatus();
                        }
                    }
                } catch (error) {
                    // Wallet not connected yet, user will need to click connect button
                    console.log('Wallet not auto-connected:', error.message);
                }
            }
        });

        // Handle form submission via AJAX
        document.getElementById('kyc-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessage();

            if (!walletConnected) {
                showMessage('{{ __("elections.wallet.connect_first") }}', 'error');
                return false;
            }

            const submitBtn = document.getElementById('submit-btn');
            const originalText = submitBtn.textContent;

            try {
                // Disable submit button and show loading state
                submitBtn.disabled = true;
                submitBtn.textContent = '{{ app()->getLocale() === "sk" ? "Odosielam..." : "Submitting..." }}';

                // Get form data
                const formData = new FormData(this);

                // Submit via fetch API
                const response = await fetch('{{ route("kyc.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    // Success - show message and reload after 2 seconds
                    showMessage(data.message || '{{ __("validation.success.kyc_submitted") }}', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Error - show error message
                    let errorMessage = data.message || '{{ __("validation.errors.kyc_submission_failed") }}';

                    // If there are specific field errors, show them as list
                    if (data.errors) {
                        const errorList = Object.values(data.errors).flat();
                        errorMessage += '<ul style="margin-top: 0.5rem; padding-left: 1.5rem;">';
                        errorList.forEach(err => {
                            errorMessage += '<li>' + err + '</li>';
                        });
                        errorMessage += '</ul>';
                    }

                    showMessage(errorMessage, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showMessage('{{ __("validation.errors.kyc_submission_failed") }}<br><br>' + error.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    </script>
</body>
</html>
