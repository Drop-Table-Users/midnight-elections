<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('elections.kyc.admin.title') }} - Identity Verification Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --kyc-warning: #F57C00;
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
            max-width: 1400px;
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
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: white;
        }

        .kyc-brand-text p {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--kyc-success) 0%, #2E7D32 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(56, 142, 60, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(56, 142, 60, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--kyc-danger) 0%, #B71C1C 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(211, 47, 47, 0.4);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 13px;
        }

        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 6px solid;
        }

        .alert-success {
            background-color: #E8F5E9;
            border-color: var(--kyc-success);
            color: #1B5E20;
        }

        .alert-error {
            background-color: #FFEBEE;
            border-color: var(--kyc-danger);
            color: #B71C1C;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 105, 92, 0.1);
            text-align: center;
            transition: all 0.3s;
            border-top: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 105, 92, 0.2);
        }

        .stat-card.pending {
            border-color: var(--kyc-warning);
        }

        .stat-card.approved {
            border-color: var(--kyc-success);
        }

        .stat-card.rejected {
            border-color: var(--kyc-danger);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-number.pending {
            color: var(--kyc-warning);
        }

        .stat-number.approved {
            color: var(--kyc-success);
        }

        .stat-number.rejected {
            color: var(--kyc-danger);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--kyc-gray);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 105, 92, 0.1);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--kyc-primary);
            border-bottom: 3px solid var(--kyc-light);
            padding-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-count {
            font-size: 1rem;
            color: var(--kyc-gray);
            font-weight: 600;
            background: var(--kyc-bg);
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--kyc-bg);
        }

        th {
            background: linear-gradient(135deg, var(--kyc-primary) 0%, var(--kyc-primary-dark) 100%);
            color: white;
            font-weight: 700;
            cursor: pointer;
            user-select: none;
            position: relative;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        th:hover {
            background: linear-gradient(135deg, var(--kyc-primary-dark) 0%, var(--kyc-primary) 100%);
        }

        th.sortable::after {
            content: '⇅';
            position: absolute;
            right: 8px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }

        th.sorted-asc::after {
            content: '↑';
            color: white;
        }

        th.sorted-desc::after {
            content: '↓';
            color: white;
        }

        tbody tr {
            transition: all 0.2s;
        }

        tbody tr:hover {
            background-color: var(--kyc-bg);
            transform: scale(1.01);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
            color: var(--kyc-warning);
            border: 2px solid var(--kyc-warning);
        }

        .status-approved {
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
            color: var(--kyc-success);
            border: 2px solid var(--kyc-success);
        }

        .status-rejected {
            background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
            color: var(--kyc-danger);
            border: 2px solid var(--kyc-danger);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .wallet-address {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--kyc-primary);
            background: var(--kyc-bg);
            padding: 0.5rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .masked-id {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--kyc-gray);
            background: var(--kyc-gray-light);
            padding: 0.5rem;
            border-radius: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--kyc-gray);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--kyc-primary);
        }

        .empty-state p {
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 77, 64, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2.5rem;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 105, 92, 0.4);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            margin-bottom: 1.5rem;
            border-bottom: 3px solid var(--kyc-light);
            padding-bottom: 1rem;
        }

        .modal-header h2 {
            font-size: 1.5rem;
            color: var(--kyc-primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: var(--kyc-primary);
            font-size: 1rem;
        }

        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--kyc-light);
            border-radius: 8px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: var(--kyc-accent);
            box-shadow: 0 0 0 4px rgba(38, 166, 154, 0.1);
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 2px solid var(--kyc-bg);
        }

        .info-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 1rem;
            padding: 0.75rem;
            background: var(--kyc-bg);
            border-radius: 8px;
        }

        .info-label {
            font-weight: 700;
            color: var(--kyc-primary);
            min-width: 100px;
        }

        .info-value {
            color: #263238;
            font-weight: 500;
        }

        .credential-hash-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .credential-hash-value {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: var(--kyc-primary);
            background: var(--kyc-bg);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            word-break: break-all;
            flex: 1;
            min-width: 150px;
        }

        .btn-copy {
            background: linear-gradient(135deg, var(--kyc-accent) 0%, #1B9E8F 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-copy:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(38, 166, 154, 0.4);
        }

        .btn-copy:active {
            transform: translateY(0);
        }

        .credential-hash-note {
            font-size: 0.75rem;
            color: var(--kyc-primary);
            font-weight: 600;
            text-align: center;
            margin-top: 0.25rem;
            display: block;
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

            .stats {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1.5rem;
            }

            .section-title {
                font-size: 1.25rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .modal-content {
                padding: 1.5rem;
                width: 95%;
            }
        }

        @media (max-width: 1200px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
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
                        <h1>{{ __('elections.kyc.admin.title') }}</h1>
                        <p>{{ __('elections.kyc.admin.subtitle') }}</p>
                    </div>
                </div>
                <div class="header-actions">
                    <x-admin-language-switcher />
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                        {{ __('elections.admin.show.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <!-- Statistics Overview -->
        <div class="stats">
            <div class="stat-card pending">
                <div class="stat-number pending">{{ $pendingCount ?? 0 }}</div>
                <div class="stat-label">{{ __('elections.kyc.status.pending') }}</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number approved">{{ $approvedCount ?? 0 }}</div>
                <div class="stat-label">{{ __('elections.kyc.status.approved') }}</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-number rejected">{{ $rejectedCount ?? 0 }}</div>
                <div class="stat-label">{{ __('elections.kyc.status.rejected') }}</div>
            </div>
        </div>

        <!-- Pending Verifications -->
        <div class="card">
            <div class="section-title">
                <span>{{ __('elections.kyc.status.pending') }}</span>
                <span class="section-count">({{ $pendingVerifications->count() }})</span>
            </div>

            @if($pendingVerifications->count() > 0)
                <table id="pending-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="wallet_address">{{ __('elections.kyc.admin.wallet_address') }}</th>
                            <th class="sortable" data-sort="full_name">{{ __('elections.kyc.form.full_name') }}</th>
                            <th class="sortable" data-sort="national_id">{{ __('elections.kyc.form.national_id') }}</th>
                            <th class="sortable" data-sort="date_of_birth">{{ __('elections.kyc.form.date_of_birth') }}</th>
                            <th class="sortable" data-sort="age">{{ __('elections.kyc.admin.age') }}</th>
                            <th class="sortable" data-sort="nationality">{{ __('elections.kyc.form.nationality') }}</th>
                            <th class="sortable" data-sort="submitted_at">{{ __('elections.kyc.admin.submitted_at') }}</th>
                            <th>{{ __('elections.kyc.admin.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingVerifications as $verification)
                            <tr>
                                <td class="wallet-address" title="{{ $verification->wallet_address }}">
                                    {{ substr($verification->wallet_address, 0, 10) }}...{{ substr($verification->wallet_address, -8) }}
                                </td>
                                <td>{{ $verification->full_name }}</td>
                                <td>{{ $verification->national_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($verification->date_of_birth)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($verification->date_of_birth)->age }}</td>
                                <td>{{ $verification->nationality }}</td>
                                <td>{{ $verification->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <form id="approve-form-{{ $verification->id }}" action="{{ route('admin.kyc.approve', $verification->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="button" class="btn btn-success btn-sm" onclick="confirmApprove({{ $verification->id }}, '{{ $verification->full_name }}')">
                                                {{ __('elections.kyc.admin.approve') }}
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="openRejectModal({{ $verification->id }}, '{{ $verification->full_name }}')">
                                            {{ __('elections.kyc.admin.reject') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">✅</div>
                    <h3>{{ __('elections.kyc.admin.no_pending') }}</h3>
                    <p>{{ __('elections.kyc.admin.all_processed') }}</p>
                </div>
            @endif
        </div>

        <!-- Approved Verifications -->
        <div class="card">
            <div class="section-title">
                <span>{{ __('elections.kyc.status.approved') }}</span>
                <span class="section-count">({{ $approvedVerifications->count() }})</span>
            </div>

            @if($approvedVerifications->count() > 0)
                <table id="approved-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="wallet_address">{{ __('elections.kyc.admin.wallet_address') }}</th>
                            <th class="sortable" data-sort="full_name">{{ __('elections.kyc.form.full_name') }}</th>
                            <th class="sortable" data-sort="national_id">{{ __('elections.kyc.form.national_id') }}</th>
                            <th class="sortable" data-sort="date_of_birth">{{ __('elections.kyc.form.date_of_birth') }}</th>
                            <th class="sortable" data-sort="age">{{ __('elections.kyc.admin.age') }}</th>
                            <th class="sortable" data-sort="nationality">{{ __('elections.kyc.form.nationality') }}</th>
                            <th class="sortable" data-sort="approved_at">{{ __('elections.kyc.admin.approved_at') }}</th>
                            <th>{{ __('elections.kyc.admin.credential_hash') }}</th>
                            <th>{{ __('elections.kyc.admin.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approvedVerifications as $verification)
                            <tr>
                                <td class="wallet-address" title="{{ $verification->wallet_address }}">
                                    {{ substr($verification->wallet_address, 0, 10) }}...{{ substr($verification->wallet_address, -8) }}
                                </td>
                                <td>{{ $verification->full_name }}</td>
                                <td>{{ $verification->national_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($verification->date_of_birth)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($verification->date_of_birth)->age }}</td>
                                <td>{{ $verification->nationality }}</td>
                                <td>{{ $verification->approved_at ? $verification->approved_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>
                                    @if($verification->credential_hash)
                                        <div class="credential-hash-cell">
                                            <span class="credential-hash-value" id="hash-{{ $verification->id }}" title="{{ $verification->credential_hash }}">{{ substr($verification->credential_hash, 0, 8) }}...{{ substr($verification->credential_hash, -8) }}</span>
                                            <button type="button" class="btn-copy" onclick="copyToClipboard('{{ $verification->credential_hash }}', {{ $verification->id }})">{{ __('elections.kyc.admin.copy') }}</button>
                                        </div>
                                        <span class="credential-hash-note">{{ __('elections.kyc.admin.credential_note') }}</span>
                                    @else
                                        <span style="color: var(--kyc-gray); font-style: italic;">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-approved">{{ __('elections.kyc.status.approved') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>{{ __('elections.kyc.admin.no_approved') }}</p>
                </div>
            @endif
        </div>

        <!-- Rejected Verifications -->
        <div class="card">
            <div class="section-title">
                <span>{{ __('elections.kyc.status.rejected') }}</span>
                <span class="section-count">({{ $rejectedVerifications->count() }})</span>
            </div>

            @if($rejectedVerifications->count() > 0)
                <table id="rejected-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="wallet_address">{{ __('elections.kyc.admin.wallet_address') }}</th>
                            <th class="sortable" data-sort="full_name">{{ __('elections.kyc.form.full_name') }}</th>
                            <th class="sortable" data-sort="national_id">{{ __('elections.kyc.form.national_id') }}</th>
                            <th class="sortable" data-sort="date_of_birth">{{ __('elections.kyc.form.date_of_birth') }}</th>
                            <th class="sortable" data-sort="age">{{ __('elections.kyc.admin.age') }}</th>
                            <th class="sortable" data-sort="nationality">{{ __('elections.kyc.form.nationality') }}</th>
                            <th class="sortable" data-sort="rejected_at">{{ __('elections.kyc.admin.rejected_at') }}</th>
                            <th>{{ __('elections.kyc.admin.rejection_reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rejectedVerifications as $verification)
                            <tr>
                                <td class="wallet-address" title="{{ $verification->wallet_address }}">
                                    {{ substr($verification->wallet_address, 0, 10) }}...{{ substr($verification->wallet_address, -8) }}
                                </td>
                                <td>{{ $verification->full_name }}</td>
                                <td>{{ $verification->national_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($verification->date_of_birth)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($verification->date_of_birth)->age }}</td>
                                <td>{{ $verification->nationality }}</td>
                                <td>{{ $verification->rejected_at ? $verification->rejected_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>{{ $verification->rejection_reason ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>{{ __('elections.kyc.admin.no_rejected') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('elections.kyc.admin.reject_verification') }}</h2>
            </div>
            <div class="modal-body">
                <div class="info-row">
                    <div class="info-label">{{ __('elections.kyc.admin.applicant') }}:</div>
                    <div class="info-value" id="rejectApplicantName"></div>
                </div>
                <form id="rejectForm" action="" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="rejection_reason">{{ __('elections.kyc.admin.rejection_reason') }} *</label>
                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            required
                            placeholder="{{ __('elections.kyc.admin.rejection_placeholder') }}"
                        ></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">{{ __('elections.kyc.admin.cancel') }}</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">{{ __('elections.kyc.admin.reject') }}</button>
            </div>
        </div>
    </div>

    <script>
        // Copy credential hash to clipboard
        function copyToClipboard(text, verificationId) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    title: 'Copied!',
                    text: 'Credential hash has been copied to clipboard',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(err => {
                console.error('Failed to copy:', err);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to copy credential hash to clipboard',
                    icon: 'error',
                    confirmButtonColor: '#D32F2F'
                });
            });
        }

        // SweetAlert2 confirmation for approve
        function confirmApprove(verificationId, applicantName) {
            Swal.fire({
                title: 'Approve Verification?',
                html: `Are you sure you want to approve the verification for <strong>${applicantName}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#388E3C',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('approve-form-' + verificationId).submit();
                }
            });
        }

        // Modal functions
        function openRejectModal(verificationId, applicantName) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            const nameElement = document.getElementById('rejectApplicantName');

            form.action = `/admin/kyc/${verificationId}/reject`;
            nameElement.textContent = applicantName;
            modal.classList.add('active');
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            const textarea = document.getElementById('rejection_reason');

            modal.classList.remove('active');
            textarea.value = '';
        }

        function confirmReject() {
            const textarea = document.getElementById('rejection_reason');
            const form = document.getElementById('rejectForm');

            if (!textarea.value.trim()) {
                Swal.fire({
                    title: 'Error',
                    text: 'Please provide a rejection reason',
                    icon: 'error',
                    confirmButtonColor: '#D32F2F'
                });
                return;
            }

            Swal.fire({
                title: 'Reject Verification?',
                html: `Are you sure you want to reject this verification?<br><br><strong>Reason:</strong> ${textarea.value}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D32F2F',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rejectModal');
            if (event.target === modal) {
                closeRejectModal();
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeRejectModal();
            }
        });

        // Table sorting functionality
        function sortTable(table, columnIndex, direction) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();

                // Try to parse as numbers first
                const aNum = parseFloat(aValue);
                const bNum = parseFloat(bValue);

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                }

                // Otherwise compare as strings
                return direction === 'asc'
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });

            // Reappend sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        // Add sorting to all tables
        document.querySelectorAll('table').forEach(table => {
            const headers = table.querySelectorAll('th.sortable');

            headers.forEach((header, index) => {
                header.addEventListener('click', () => {
                    // Determine sort direction
                    let direction = 'asc';
                    if (header.classList.contains('sorted-asc')) {
                        direction = 'desc';
                    }

                    // Remove sorted class from all headers in this table
                    table.querySelectorAll('th').forEach(h => {
                        h.classList.remove('sorted-asc', 'sorted-desc');
                    });

                    // Add sorted class to current header
                    header.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');

                    // Sort the table
                    sortTable(table, index, direction);
                });
            });
        });
    </script>
</body>
</html>
