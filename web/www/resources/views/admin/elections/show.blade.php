<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $election->title_en }} - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
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
            margin-bottom: 10px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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

        .btn-danger {
            background-color: #EE1C25;
            color: white;
        }

        .btn-danger:hover {
            background-color: #cc1820;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .card {
            background: white;
            border-radius: 6px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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

        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
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

        .section-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #0B4EA2;
            border-bottom: 2px solid #0B4EA2;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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

        .candidate-actions {
            display: flex;
            gap: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .blockchain-info {
            background-color: #e8f5e9;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h1>{{ $election->title_en }}</h1>
                <x-admin-language-switcher />
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('elections.admin.show.back_to_dashboard') }}</a>
                <a href="{{ route('admin.elections.edit', $election->id) }}" class="btn btn-primary">{{ __('elections.admin.show.edit_election') }}</a>
                <a href="{{ route('admin.candidates.create', $election->id) }}" class="btn btn-success">{{ __('elections.admin.show.add_candidate') }}</a>

                @if(!$election->contract_address)
                    <form action="{{ route('admin.elections.deploy', $election->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ __('elections.admin.election_card.deploy_to_blockchain') }}</button>
                    </form>
                @endif

                @if($election->contract_address && $election->status === 'draft')
                    <form action="{{ route('admin.elections.open', $election->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ __('elections.admin.election_card.open_election') }}</button>
                    </form>
                @endif

                @if($election->status === 'open')
                    <form action="{{ route('admin.elections.close', $election->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">{{ __('elections.admin.election_card.close_election') }}</button>
                    </form>
                @endif

                @if($election->status !== 'open')
                    <form action="{{ route('admin.elections.destroy', $election->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('elections.admin.show.delete_confirm') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ __('elections.admin.show.delete_election') }}</button>
                    </form>
                @endif
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

        @if($election->contract_address)
            <div class="blockchain-info">
                <strong>{{ __('elections.admin.show.blockchain_status') }}</strong> {{ __('elections.admin.show.blockchain_deployed') }}
                @if($election->blockchain_election_id)
                    <br><strong>{{ __('elections.admin.show.election_id') }}</strong> {{ $election->blockchain_election_id }}
                @endif
            </div>
        @else
            <div class="warning-box">
                <strong>{{ __('elections.admin.show.not_deployed') }}</strong> {{ __('elections.admin.show.not_deployed_text') }}
            </div>
        @endif

        <div class="card">
            <h2 class="section-title">{{ __('elections.admin.show.election_details') }}</h2>

            <div class="info-grid">
                <div class="info-label">{{ __('elections.admin.election_card.status') }}</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $election->status }}">{{ ucfirst($election->status) }}</span>
                </div>

                <div class="info-label">{{ __('elections.admin.show.title_en') }}</div>
                <div class="info-value">{{ $election->title_en }}</div>

                <div class="info-label">{{ __('elections.admin.show.title_sk') }}</div>
                <div class="info-value">{{ $election->title_sk }}</div>

                @if($election->description_en)
                    <div class="info-label">{{ __('elections.admin.show.description_en') }}</div>
                    <div class="info-value">{{ $election->description_en }}</div>
                @endif

                @if($election->description_sk)
                    <div class="info-label">{{ __('elections.admin.show.description_sk') }}</div>
                    <div class="info-value">{{ $election->description_sk }}</div>
                @endif

                <div class="info-label">{{ __('elections.admin.show.start_date') }}</div>
                <div class="info-value">{{ $election->start_date ? $election->start_date->format('F d, Y H:i') : __('elections.admin.election_card.not_set') }}</div>

                <div class="info-label">{{ __('elections.admin.show.end_date') }}</div>
                <div class="info-value">{{ $election->end_date ? $election->end_date->format('F d, Y H:i') : __('elections.admin.election_card.not_set') }}</div>

                @if($election->contract_address)
                    <div class="info-label">{{ __('elections.admin.show.contract_address') }}</div>
                    <div class="info-value">{{ $election->contract_address }}</div>
                @endif

                <div class="info-label">{{ __('elections.admin.show.created') }}</div>
                <div class="info-value">{{ $election->created_at->format('F d, Y H:i') }}</div>

                <div class="info-label">{{ __('elections.admin.show.last_updated') }}</div>
                <div class="info-value">{{ $election->updated_at->format('F d, Y H:i') }}</div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">{{ __('elections.admin.show.candidates_count', ['count' => $election->candidates->count()]) }}</h2>

            @if($election->candidates->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('elections.admin.show.table_order') }}</th>
                            <th>{{ __('elections.admin.show.table_name_en') }}</th>
                            <th>{{ __('elections.admin.show.table_name_sk') }}</th>
                            <th>{{ __('elections.admin.show.table_blockchain_status') }}</th>
                            <th>{{ __('elections.admin.show.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($election->candidates as $candidate)
                            <tr>
                                <td>{{ $candidate->display_order }}</td>
                                <td>{{ $candidate->name_en }}</td>
                                <td>{{ $candidate->name_sk }}</td>
                                <td>
                                    @if($candidate->blockchain_candidate_id)
                                        <span class="status-badge status-open">{{ __('elections.admin.show.registered') }}</span>
                                    @else
                                        <span class="status-badge status-draft">{{ __('elections.admin.show.not_registered') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="candidate-actions">
                                        <a href="{{ route('admin.candidates.edit', $candidate->id) }}" class="btn btn-primary btn-sm">{{ __('elections.admin.election_card.edit') }}</a>

                                        @if($election->contract_address && !$candidate->blockchain_candidate_id)
                                            <form action="{{ route('admin.candidates.register', $candidate->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">{{ __('elections.admin.show.register') }}</button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.candidates.destroy', $candidate->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('elections.admin.show.delete_candidate_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">{{ __('elections.admin.show.delete_candidate') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <p>{{ __('elections.admin.show.no_candidates') }}</p>
                    <a href="{{ route('admin.candidates.create', $election->id) }}" class="btn btn-success">{{ __('elections.admin.show.add_first_candidate') }}</a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
