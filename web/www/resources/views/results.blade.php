<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - {{ $election->title_en }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        header {
            background: linear-gradient(135deg, #0B4EA2 0%, #0D6BB8 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #0B4EA2;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .chart-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .results-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 30px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #0B4EA2;
            color: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        .percentage {
            font-weight: 600;
            color: #0B4EA2;
        }

        .votes-count {
            font-size: 18px;
            font-weight: 600;
        }

        .rank {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background-color: #f0f0f0;
            font-weight: 600;
            color: #666;
        }

        .rank.first {
            background-color: #FFD700;
            color: #fff;
        }

        .rank.second {
            background-color: #C0C0C0;
            color: #fff;
        }

        .rank.third {
            background-color: #CD7F32;
            color: #fff;
        }

        .blockchain-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 30px 0;
        }

        .blockchain-info h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }

        .blockchain-info code {
            background: #f5f5f5;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #0B4EA2;
            display: inline-block;
            margin-top: 5px;
            word-break: break-all;
        }

        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 20px 0;
            transition: background-color 0.2s;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 24px;
            }

            .stat-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>{{ $election->title_en }}</h1>
            <p class="subtitle">Election Results - Verified on Blockchain</p>
        </div>
    </header>

    <div class="container">
        <a href="{{ route('election.show', $election->id) }}" class="back-button">← Back to Election</a>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Votes Cast</div>
                <div class="stat-value">{{ number_format($totalVotes) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Candidates</div>
                <div class="stat-value">{{ count($results) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Election Status</div>
                <div class="stat-value" style="font-size: 24px;">{{ ucfirst($election->status) }}</div>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3 class="chart-title">Vote Distribution (Bar Chart)</h3>
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">Vote Share (Pie Chart)</h3>
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <div class="results-table">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Candidate</th>
                        <th>Votes</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $index => $result)
                        <tr>
                            <td>
                                <span class="rank {{ $index === 0 ? 'first' : ($index === 1 ? 'second' : ($index === 2 ? 'third' : '')) }}">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $result['candidate']->name_en }}</strong>
                                @if($result['candidate']->party_en)
                                    <br><small style="color: #666;">{{ $result['candidate']->party_en }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="votes-count">{{ number_format($result['votes']) }}</span>
                            </td>
                            <td>
                                <span class="percentage">
                                    {{ $totalVotes > 0 ? number_format(($result['votes'] / $totalVotes) * 100, 2) : 0 }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="blockchain-info">
            <h3>Blockchain Verification</h3>
            <p>These results are publicly verifiable on the Midnight Network blockchain.</p>
            <p><strong>Contract Address:</strong> <code>{{ $contractAddress }}</code></p>
        </div>
    </div>

    <script>
        // Prepare data for charts
        const candidateNames = @json(array_map(fn($r) => $r['candidate']->name_en, $results));
        const votes = @json(array_map(fn($r) => (int)$r['votes'], $results));

        const colors = [
            'rgba(11, 78, 162, 0.8)',
            'rgba(238, 28, 37, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(40, 167, 69, 0.8)',
            'rgba(220, 53, 69, 0.8)',
            'rgba(23, 162, 184, 0.8)',
            'rgba(108, 117, 125, 0.8)',
        ];

        const borderColors = [
            'rgba(11, 78, 162, 1)',
            'rgba(238, 28, 37, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(40, 167, 69, 1)',
            'rgba(220, 53, 69, 1)',
            'rgba(23, 162, 184, 1)',
            'rgba(108, 117, 125, 1)',
        ];

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: candidateNames,
                datasets: [{
                    label: 'Votes',
                    data: votes,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: candidateNames,
                datasets: [{
                    data: votes,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
