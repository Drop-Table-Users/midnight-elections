<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Vote - {{ $election->title_en }}</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            max-width: 800px;
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

        .card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 30px 0;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #0B4EA2;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }

        .info-box h3 {
            color: #0B4EA2;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #555;
            margin-bottom: 8px;
        }

        .info-box strong {
            color: #0B4EA2;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .required {
            color: #EE1C25;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: 'Courier New', monospace;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #0B4EA2;
        }

        .form-text {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0B4EA2;
            color: white;
        }

        .btn-primary:hover {
            background-color: #094080;
        }

        .btn-primary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-right: 10px;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .verification-steps {
            margin-top: 30px;
        }

        .verification-steps h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .verification-steps ol {
            margin-left: 20px;
        }

        .verification-steps li {
            margin-bottom: 12px;
            line-height: 1.8;
        }

        .loading-spinner {
            display: none;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
            h1 {
                font-size: 24px;
            }

            .card {
                padding: 25px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Verify Your Vote</h1>
            <p class="subtitle">{{ $election->title_en }}</p>
        </div>
    </header>

    <div class="container">
        <a href="{{ route('election.show', $election->id) }}" class="back-button">← Back to Election</a>

        <div class="card">
            <div class="info-box">
                <h3>How Vote Verification Works</h3>
                <p>Use your <strong>Credential Hash</strong> to verify that your vote was successfully recorded on the blockchain.</p>
                <p>This process confirms your vote was counted <strong>without revealing your identity or vote choice</strong>.</p>
            </div>

            <form id="verifyForm">
                @csrf
                <div class="form-group">
                    <label for="credentialHash">Credential Hash <span class="required">*</span></label>
                    <input
                        type="text"
                        id="credentialHash"
                        name="credential_hash"
                        placeholder="Enter your credential hash (e.g., 0x1234...)"
                        required
                        pattern="^(0x)?[0-9a-fA-F]{64}$"
                    >
                    <small class="form-text">
                        This is the hash you received after your KYC approval. It's a 64-character hexadecimal string.
                    </small>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        Verify Vote
                        <span class="loading-spinner" id="loadingSpinner"></span>
                    </button>
                </div>
            </form>

            <div class="verification-steps">
                <h3>Verification Steps</h3>
                <ol>
                    <li>Locate your <strong>Credential Hash</strong> from the KYC approval confirmation.</li>
                    <li>Paste it into the field above.</li>
                    <li>Click "Verify Vote" to check the blockchain.</li>
                    <li>The system will confirm if your vote was successfully recorded.</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('verifyForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const credentialHash = document.getElementById('credentialHash').value.trim();
            const submitButton = this.querySelector('button[type="submit"]');
            const loadingSpinner = document.getElementById('loadingSpinner');

            // Basic validation
            if (!credentialHash) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Credential Hash',
                    text: 'Please enter your credential hash.'
                });
                return;
            }

            // Validate format
            const hashRegex = /^(0x)?[0-9a-fA-F]{64}$/;
            if (!hashRegex.test(credentialHash)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Format',
                    text: 'Credential hash must be a 64-character hexadecimal string (with or without 0x prefix).'
                });
                return;
            }

            // Show loading state
            submitButton.disabled = true;
            loadingSpinner.style.display = 'inline-block';

            try {
                const response = await fetch('{{ route("election.verify.post", $election->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        credential_hash: credentialHash
                    })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    if (data.voted) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Vote Verified!',
                            html: `
                                <p><strong>Your vote was successfully recorded on the blockchain.</strong></p>
                                <p>${data.message}</p>
                            `,
                            confirmButtonColor: '#0B4EA2'
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Vote Not Found',
                            html: `
                                <p>${data.message}</p>
                                <p>This could mean:</p>
                                <ul style="text-align: left; margin-top: 10px;">
                                    <li>You haven't voted yet in this election</li>
                                    <li>The credential hash is incorrect</li>
                                    <li>The transaction hasn't been confirmed yet</li>
                                </ul>
                            `,
                            confirmButtonColor: '#0B4EA2'
                        });
                    }
                } else {
                    throw new Error(data.message || 'Verification failed');
                }

            } catch (error) {
                console.error('Verification error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    text: error.message || 'Failed to verify vote on blockchain. Please try again.',
                    confirmButtonColor: '#EE1C25'
                });
            } finally {
                // Reset loading state
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        });

        // Real-time format validation
        document.getElementById('credentialHash').addEventListener('input', function(e) {
            const value = e.target.value.trim();
            if (value && !/^(0x)?[0-9a-fA-F]*$/.test(value)) {
                e.target.setCustomValidity('Please enter a valid hexadecimal string');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
