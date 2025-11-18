<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .info-box {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-box strong {
            display: block;
            color: #4a5568;
            margin-bottom: 5px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .info-box span {
            color: #2d3748;
            font-size: 1.1rem;
            font-family: 'Courier New', monospace;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
        }

        .btn.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn.loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-secondary {
            background: #48bb78;
        }

        .btn-secondary:hover {
            background: #38a169;
        }

        .btn-danger {
            background: #f56565;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status.connected {
            background: #c6f6d5;
            color: #22543d;
        }

        .status.disconnected {
            background: #fed7d7;
            color: #742a2a;
        }

        .status.pending {
            background: #feebc8;
            color: #7c2d12;
        }

        .wallet-info {
            background: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .wallet-address {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .logs {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .log-entry {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #4a5568;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .log-timestamp {
            color: #cbd5e0;
            font-size: 0.85rem;
        }

        .log-message {
            color: #e2e8f0;
        }

        .log-error {
            color: #fc8181;
        }

        .log-success {
            color: #68d391;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>{{ $title }}</h1>
            <p style="color: #718096; margin-bottom: 20px;">Test the integration between Laravel, Midnight blockchain, and Lace wallet</p>

            <div class="info-grid">
                <div class="info-box">
                    <strong>Network</strong>
                    <span>{{ $network }}</span>
                </div>
                <div class="info-box">
                    <strong>Elections API</strong>
                    <span>localhost:3000</span>
                </div>
                <div class="info-box">
                    <strong>API Status</strong>
                    <span id="bridge-status" class="status pending">Checking...</span>
                </div>
                <div class="info-box">
                    <strong>Wallet Status</strong>
                    <span id="wallet-status" class="status disconnected">Not Connected</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>1. Wallet Connection</h2>
            <p style="color: #718096; margin-bottom: 20px;">Connect your Lace wallet to interact with the Midnight network</p>

            <button class="btn" id="connect-wallet-btn">Connect Lace Wallet</button>
            <button class="btn btn-danger" id="disconnect-wallet-btn" style="display: none;">Disconnect Wallet</button>

            <div id="wallet-info" class="wallet-info" style="display: none;">
                <strong>Connected Wallet:</strong>
                <div class="wallet-address" id="wallet-address"></div>
                <div style="margin-top: 10px;">
                    <strong>Balance:</strong> <span id="wallet-balance">Loading...</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>2. Contract Actions</h2>
            <p style="color: #718096; margin-bottom: 20px;">Test various contract operations</p>

            <div class="actions-grid">
                <button class="btn" id="test-open-btn" disabled>Open Election</button>
                <button class="btn" id="test-close-btn" disabled>Close Election</button>
                <button class="btn btn-secondary" id="test-register-btn" disabled>Register Candidate</button>
                <button class="btn btn-secondary" id="test-vote-btn" disabled title="Requires credential system">Cast Vote (Not Available)</button>
                <button class="btn btn-secondary" id="check-wallet-status-btn">Check Wallet Status</button>
            </div>

            <div id="register-form" style="display: none; margin-top: 20px; background: #f7fafc; padding: 20px; border-radius: 8px;">
                <h3 style="color: #4a5568; margin-bottom: 15px;">Register as Candidate</h3>
                <div class="form-group">
                    <label for="register-candidate-id">Candidate Name/ID</label>
                    <input type="text" id="register-candidate-id" placeholder="e.g., 'mario' or 32-byte hex">
                    <small style="color: #718096; font-size: 0.85rem;">Enter a name or full 64-character hex string</small>
                </div>
                <button class="btn btn-secondary" id="submit-register-btn">Submit Registration</button>
                <button class="btn" id="cancel-register-btn" style="background: #a0aec0;">Cancel</button>
            </div>

            <div id="vote-form" style="display: none; margin-top: 20px; background: #fff3cd; padding: 20px; border-radius: 8px; border: 2px solid #ffc107;">
                <h3 style="color: #856404; margin-bottom: 15px;">Cast Your Vote</h3>
                <div style="background: #fff; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                    <strong style="color: #856404;">Note: Credential Required</strong>
                    <p style="color: #856404; margin: 10px 0 0 0; font-size: 0.9rem;">
                        Voting requires a signed credential from an identity authority. This credential system proves voter eligibility
                        while maintaining privacy. The current test environment does not have a credential issuance system configured.
                    </p>
                    <p style="color: #856404; margin: 10px 0 0 0; font-size: 0.9rem;">
                        To enable voting, you would need to:
                    </p>
                    <ul style="color: #856404; margin: 5px 0 0 20px; font-size: 0.9rem;">
                        <li>Set up an identity authority service</li>
                        <li>Issue signed credentials to eligible voters</li>
                        <li>Integrate the credential verification into this UI</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label for="vote-candidate-id">Candidate Name/ID to Vote For</label>
                    <input type="text" id="vote-candidate-id" placeholder="e.g., 'mario' or 32-byte hex" disabled>
                    <small style="color: #856404; font-size: 0.85rem;">Voting disabled - credential system not configured</small>
                </div>
                <button class="btn" id="cancel-vote-btn" style="background: #6c757d;">Close</button>
            </div>
        </div>

        <div class="card">
            <h2>Activity Logs</h2>
            <div class="logs" id="logs">
                <div class="log-entry">
                    <div class="log-timestamp">System ready</div>
                    <div class="log-message">Waiting for user interaction...</div>
                </div>
            </div>
            <button class="btn btn-secondary" id="clear-logs-btn" style="margin-top: 15px;">Clear Logs</button>
        </div>
    </div>

    <script>
        // Logging utility
        function addLog(message, type = 'info') {
            const logs = document.getElementById('logs');
            const timestamp = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = 'log-entry';

            let messageClass = 'log-message';
            if (type === 'error') messageClass = 'log-error';
            if (type === 'success') messageClass = 'log-success';

            entry.innerHTML = `
                <div class="log-timestamp">${timestamp}</div>
                <div class="${messageClass}">${message}</div>
            `;

            logs.appendChild(entry);
            logs.scrollTop = logs.scrollHeight;
        }

        // CSRF token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Check elections-api health on load
        async function checkBridge() {
            try {
                addLog('Checking elections-api connection...');
                const response = await fetch('/api/midnight/check-bridge');
                const data = await response.json();

                const statusEl = document.getElementById('bridge-status');
                if (data.status === 'success') {
                    statusEl.textContent = 'Connected';
                    statusEl.className = 'status connected';
                    addLog('Elections API is connected and healthy', 'success');
                } else {
                    statusEl.textContent = 'Disconnected';
                    statusEl.className = 'status disconnected';
                    addLog('Elections API is not available: ' + data.message, 'error');
                }
            } catch (error) {
                document.getElementById('bridge-status').textContent = 'Error';
                document.getElementById('bridge-status').className = 'status disconnected';
                addLog('Failed to check elections-api: ' + error.message, 'error');
            }
        }

        // REAL Lace Wallet connection (browser extension)
        let walletConnected = false;
        let laceWalletAPI = null;

        document.getElementById('connect-wallet-btn').addEventListener('click', async () => {
            try {
                addLog('Connecting to Lace Beta Wallet for Midnight Network...');

                // Check if Lace Midnight wallet is installed
                if (typeof window.midnight === 'undefined' || typeof window.midnight.mnLace === 'undefined') {
                    throw new Error('Lace Beta Wallet not found. Please install Lace Beta Wallet for Midnight Network.');
                }

                // Request wallet access
                addLog('Requesting wallet access from Lace Beta Wallet...');
                laceWalletAPI = await window.midnight.mnLace.enable();

                if (!laceWalletAPI) {
                    throw new Error('User denied wallet access');
                }

                // Get wallet state
                addLog('Fetching wallet information...');
                const walletState = await laceWalletAPI.state();
                const uris = await window.midnight.mnLace.serviceUriConfig();

                walletConnected = true;

                // Update UI with REAL wallet data from YOUR Lace wallet
                document.getElementById('wallet-status').textContent = 'Connected';
                document.getElementById('wallet-status').className = 'status connected';
                document.getElementById('connect-wallet-btn').style.display = 'none';
                document.getElementById('disconnect-wallet-btn').style.display = 'inline-block';
                document.getElementById('wallet-info').style.display = 'block';
                document.getElementById('wallet-address').textContent = walletState.address || 'N/A';
                document.getElementById('wallet-balance').textContent = 'Connected';

                // Enable action buttons
                document.getElementById('test-open-btn').disabled = false;
                document.getElementById('test-close-btn').disabled = false;
                document.getElementById('test-register-btn').disabled = false;
                document.getElementById('test-vote-btn').disabled = false;

                addLog('Lace Beta Wallet connected successfully!', 'success');
                addLog('Address: ' + walletState.address, 'info');
                addLog('Indexer: ' + uris.indexerUri, 'info');
                addLog('Prover: ' + uris.proverServerUri, 'info');
                addLog('You can now test contract actions with your REAL wallet.', 'info');
            } catch (error) {
                addLog('Failed to connect Lace Beta Wallet: ' + error.message, 'error');
                addLog('Make sure Lace Beta Wallet extension is installed and unlocked.', 'error');
            }
        });

        document.getElementById('disconnect-wallet-btn').addEventListener('click', () => {
            walletConnected = false;
            laceWalletAPI = null;

            document.getElementById('wallet-status').textContent = 'Not Connected';
            document.getElementById('wallet-status').className = 'status disconnected';
            document.getElementById('connect-wallet-btn').style.display = 'inline-block';
            document.getElementById('disconnect-wallet-btn').style.display = 'none';
            document.getElementById('wallet-info').style.display = 'none';

            // Disable action buttons
            document.getElementById('test-open-btn').disabled = true;
            document.getElementById('test-close-btn').disabled = true;
            document.getElementById('test-register-btn').disabled = true;
            document.getElementById('test-vote-btn').disabled = true;

            addLog('Lace Wallet disconnected');
        });

        // Contract action handlers - Using REAL Lace Wallet to sign transactions
        async function testContractAction(action, buttonId, additionalData = {}) {
            const button = document.getElementById(buttonId);

            try {
                if (!laceWalletAPI) {
                    addLog('Please connect your Lace Wallet first', 'error');
                    return;
                }

                // Add loading state
                button.classList.add('loading');
                button.disabled = true;

                addLog(`Preparing ${action} transaction with YOUR Lace Wallet...`);

                const payload = { action, ...additionalData };

                // Send to elections-api via Laravel backend
                addLog('Requesting transaction from elections-api...');
                const response = await fetch('/api/midnight/test-contract', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.status === 'success') {
                    addLog(`${action} transaction prepared successfully`, 'success');

                    // If we have transaction data, sign it with Lace Wallet
                    if (data.data && data.data.txHash) {
                        addLog('Transaction hash: ' + data.data.txHash, 'info');
                        addLog('Block height: ' + data.data.blockHeight, 'info');
                        if (data.data.walletAddress) {
                            addLog('Wallet: ' + data.data.walletAddress.substring(0, 40) + '...', 'info');
                        }
                    }

                    addLog('Transaction submitted to Midnight network', 'success');
                } else {
                    addLog(`${action} action failed: ` + data.message, 'error');
                }
            } catch (error) {
                addLog(`${action} action error: ` + error.message, 'error');
            } finally {
                // Remove loading state
                button.classList.remove('loading');
                button.disabled = false;
            }
        }

        // Open/Close election handlers
        document.getElementById('test-open-btn').addEventListener('click', () => testContractAction('open', 'test-open-btn'));
        document.getElementById('test-close-btn').addEventListener('click', () => testContractAction('close', 'test-close-btn'));

        // Register candidate - Show form
        document.getElementById('test-register-btn').addEventListener('click', () => {
            document.getElementById('register-form').style.display = 'block';
            document.getElementById('vote-form').style.display = 'none';
            addLog('Please enter candidate ID to register', 'info');
        });

        // Helper function to convert string to 32-byte hex
        function stringToHex32(str) {
            // If already a valid hex string, use it
            if (/^(0x)?[0-9a-fA-F]{64}$/.test(str)) {
                return str.startsWith('0x') ? str : '0x' + str;
            }

            // Convert string to UTF-8 bytes and then to hex
            const encoder = new TextEncoder();
            const bytes = encoder.encode(str);

            // Pad or truncate to exactly 32 bytes
            const paddedBytes = new Uint8Array(32);
            paddedBytes.set(bytes.slice(0, 32));

            // Convert to hex string
            const hex = Array.from(paddedBytes)
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');

            return '0x' + hex;
        }

        // Register candidate - Submit
        document.getElementById('submit-register-btn').addEventListener('click', async () => {
            const candidateInput = document.getElementById('register-candidate-id').value.trim();
            if (!candidateInput) {
                addLog('Please enter a candidate ID', 'error');
                return;
            }

            // Convert to 32-byte hex
            const candidateId = stringToHex32(candidateInput);
            addLog(`Converted "${candidateInput}" to hex: ${candidateId}`, 'info');

            await testContractAction('register', 'submit-register-btn', { candidate_id: candidateId });

            // Hide form and clear input on success
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('register-candidate-id').value = '';
        });

        // Register candidate - Cancel
        document.getElementById('cancel-register-btn').addEventListener('click', () => {
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('register-candidate-id').value = '';
            addLog('Registration cancelled', 'info');
        });

        // Cast vote - Show form
        document.getElementById('test-vote-btn').addEventListener('click', () => {
            document.getElementById('vote-form').style.display = 'block';
            document.getElementById('register-form').style.display = 'none';
            addLog('Please enter candidate ID to vote for', 'info');
        });

        // Cast vote - Submit
        document.getElementById('submit-vote-btn').addEventListener('click', async () => {
            const candidateInput = document.getElementById('vote-candidate-id').value.trim();
            if (!candidateInput) {
                addLog('Please enter a candidate ID to vote for', 'error');
                return;
            }

            // Convert to 32-byte hex
            const candidateId = stringToHex32(candidateInput);
            addLog(`Voting for candidate "${candidateInput}" (hex: ${candidateId})`, 'info');

            // Prepare ballot data with the candidate ID
            const ballotData = { candidate_id: candidateId };

            await testContractAction('vote', 'submit-vote-btn', { ballot_data: ballotData });

            // Hide form and clear input on success
            document.getElementById('vote-form').style.display = 'none';
            document.getElementById('vote-candidate-id').value = '';
        });

        // Cast vote - Cancel
        document.getElementById('cancel-vote-btn').addEventListener('click', () => {
            document.getElementById('vote-form').style.display = 'none';
            document.getElementById('vote-candidate-id').value = '';
            addLog('Vote cancelled', 'info');
        });

        document.getElementById('check-wallet-status-btn').addEventListener('click', async () => {
            const button = document.getElementById('check-wallet-status-btn');
            try {
                button.classList.add('loading');
                button.disabled = true;

                addLog('Checking wallet status from elections-api...');
                const response = await fetch('/api/midnight/wallet-status');
                const data = await response.json();

                if (data.status === 'success' && data.data) {
                    const walletData = data.data;
                    if (!walletData.initialized) {
                        addLog('Wallet not initialized yet. Make an election action first to initialize the wallet.', 'info');
                    } else {
                        addLog('Wallet Status:', 'success');
                        addLog('  Initialized: ' + walletData.initialized, 'info');
                        addLog('  Synced: ' + (walletData.synced ? 'Yes' : 'No'), walletData.synced ? 'success' : 'error');
                        if (walletData.address) {
                            addLog('  Address: ' + walletData.address.substring(0, 40) + '...', 'info');
                        }
                        if (walletData.balance) {
                            addLog('  Balance: ' + walletData.balance, 'info');
                        }
                        if (walletData.syncProgress) {
                            addLog('  Sync Progress:', 'info');
                            addLog('    Source Gap: ' + walletData.syncProgress.sourceGap, 'info');
                            addLog('    Apply Gap: ' + walletData.syncProgress.applyGap, 'info');
                        }
                    }
                } else {
                    addLog('Failed to get wallet status: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                addLog('Wallet status check error: ' + error.message, 'error');
            } finally {
                button.classList.remove('loading');
                button.disabled = false;
            }
        });

        document.getElementById('clear-logs-btn').addEventListener('click', () => {
            document.getElementById('logs').innerHTML = '<div class="log-entry"><div class="log-timestamp">Logs cleared</div><div class="log-message">Ready for new messages...</div></div>';
        });

        // Initialize
        checkBridge();
        addLog('Application initialized', 'success');
        addLog('Network: {{ $network }}', 'info');
        addLog('Elections API: localhost:3000', 'info');
    </script>
</body>
</html>
