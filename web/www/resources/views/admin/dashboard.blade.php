<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SK Elections</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: #0B4EA2;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .header-actions {
            margin-top: 15px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0B4EA2;
            color: white;
        }

        .btn-primary:hover {
            background-color: #094080;
        }

        .btn-danger {
            background-color: #EE1C25;
            color: white;
        }

        .btn-danger:hover {
            background-color: #cc1820;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .elections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .election-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .election-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #0B4EA2;
        }

        .election-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .election-meta div {
            margin-bottom: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #ffc107;
            color: #000;
        }

        .status-open {
            background-color: #28a745;
            color: white;
        }

        .status-closed {
            background-color: #6c757d;
            color: white;
        }

        .candidates-count {
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h1>{{ __('elections.admin.dashboard') }}</h1>
                <x-admin-language-switcher />
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.elections.create') }}" class="btn btn-success">{{ __('elections.admin.create_election') }}</a>
                <a href="{{ route('admin.kyc.index') }}" class="btn btn-primary">{{ __('elections.admin.kyc_management') }}</a>
                <a href="{{ route('home') }}" class="btn btn-secondary">{{ __('elections.admin.view_public_site') }}</a>
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

        <h2 style="margin-bottom: 20px; font-size: 24px;">{{ __('elections.admin.all_elections') }}</h2>

        @if($elections->count() > 0)
            <div class="elections-grid">
                @foreach($elections as $election)
                    <div class="election-card">
                        <h3>{{ $election->title_en }}</h3>
                        <div class="election-meta">
                            <div><strong>{{ __('elections.admin.election_card.status') }}</strong> <span class="status-badge status-{{ $election->status }}">{{ ucfirst($election->status) }}</span></div>
                            <div><strong>{{ __('elections.admin.election_card.start') }}</strong> {{ $election->start_date ? $election->start_date->format('M d, Y H:i') : __('elections.admin.election_card.not_set') }}</div>
                            <div><strong>{{ __('elections.admin.election_card.end') }}</strong> {{ $election->end_date ? $election->end_date->format('M d, Y H:i') : __('elections.admin.election_card.not_set') }}</div>
                            @if($election->contract_address)
                                <div><strong>{{ __('elections.admin.election_card.blockchain') }}</strong> {{ __('elections.admin.election_card.deployed') }}</div>
                            @endif
                        </div>

                        <div class="candidates-count">
                            <strong>{{ __('elections.admin.election_card.candidates') }}</strong> {{ $election->candidates->count() }}
                        </div>

                        <div class="card-actions">
                            <a href="{{ route('admin.elections.show', $election->id) }}" class="btn btn-primary btn-sm">{{ __('elections.admin.election_card.view_details') }}</a>
                            <a href="{{ route('admin.elections.edit', $election->id) }}" class="btn btn-secondary btn-sm">{{ __('elections.admin.election_card.edit') }}</a>

                            @if(!$election->contract_address)
                                <form action="{{ route('admin.elections.deploy', $election->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">{{ __('elections.admin.election_card.deploy_to_blockchain') }}</button>
                                </form>
                            @endif

                            @if($election->contract_address && $election->status === 'draft')
                                <form action="{{ route('admin.elections.open', $election->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">{{ __('elections.admin.election_card.open_election') }}</button>
                                </form>
                            @endif

                            @if($election->status === 'open')
                                <form action="{{ route('admin.elections.close', $election->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('elections.admin.election_card.close_election') }}</button>
                                </form>
                            @endif

                            @if($election->status !== 'open')
                                <form action="{{ route('admin.elections.destroy', $election->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('elections.admin.election_card.delete_confirm') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('elections.admin.election_card.delete') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <h2>{{ __('elections.admin.no_elections_yet') }}</h2>
                <p>{{ __('elections.admin.get_started') }}</p>
                <a href="{{ route('admin.elections.create') }}" class="btn btn-primary">{{ __('elections.admin.create_election_button') }}</a>
            </div>
        @endif
    </div>
</body>
</html>
