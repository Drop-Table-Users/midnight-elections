<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Midnight Voting Demo - Experience privacy-preserving voting with zero-knowledge proofs on the Midnight blockchain">
    <meta name="keywords" content="midnight, blockchain, voting, zero-knowledge, privacy, laravel, demo">
    <title>Midnight Voting Demo - Private & Verifiable Voting</title>

    <!-- Tailwind CSS CDN (replace with your build in production) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Midnight Scripts -->
    @midnightScripts

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <header class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        Midnight Voting Demo
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Experience privacy-preserving voting with zero-knowledge proofs
                    </p>
                </div>
                <nav class="flex items-center space-x-4">
                    <a href="/midnight/examples/wallet-demo" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Wallet Demo
                    </a>
                    <a href="/midnight/examples/transaction-tracking-demo" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Transaction Demo
                    </a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="space-y-8">
            <!-- Wallet Connection -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                            Wallet Status
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Connect your wallet to participate in voting
                        </p>
                    </div>
                    @midnightWallet(['showBalance' => true, 'showNetwork' => true])
                </div>
            </section>

            <!-- Voting Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Vote Form -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Active Proposal
                    </h2>

                    <!-- Proposal Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    Proposal #001: Community Fund Allocation
                                </h3>
                                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Ends in 7 days
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        1,247 votes
                                    </span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                Active
                            </span>
                        </div>

                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-700 dark:text-gray-300 mb-4">
                                This proposal seeks community approval to allocate 100,000 MIDNIGHT tokens from the
                                community treasury to fund three key infrastructure improvements:
                            </p>
                            <ul class="text-gray-700 dark:text-gray-300 space-y-2 ml-6 list-disc">
                                <li>Developer documentation and tutorials (40,000 MIDNIGHT)</li>
                                <li>Security audit for core contracts (35,000 MIDNIGHT)</li>
                                <li>Community events and workshops (25,000 MIDNIGHT)</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Vote Form -->
                    @midnightVoteForm([
                        'proposalId' => 'proposal-001',
                        'options' => ['Approve', 'Reject', 'Abstain'],
                        'title' => 'Cast Your Vote',
                        'description' => 'Your vote will be private and protected by zero-knowledge proofs'
                    ])
                </section>

                <!-- Info & Results -->
                <section class="space-y-6">
                    <!-- How It Works -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            How It Works
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-indigo-600 dark:bg-indigo-700 text-white rounded-full flex items-center justify-center font-bold">
                                    1
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Select Your Vote</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Choose your preferred option from the voting form
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-indigo-600 dark:bg-indigo-700 text-white rounded-full flex items-center justify-center font-bold">
                                    2
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Generate Proof</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        A zero-knowledge proof is generated to verify your eligibility without revealing your choice
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-indigo-600 dark:bg-indigo-700 text-white rounded-full flex items-center justify-center font-bold">
                                    3
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Submit Transaction</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Your encrypted vote is submitted to the blockchain
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-indigo-600 dark:bg-indigo-700 text-white rounded-full flex items-center justify-center font-bold">
                                    4
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Verification</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Your vote is counted while maintaining complete privacy
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Results (Placeholder) -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            Voting Progress
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-700 dark:text-gray-300">Total Votes Cast</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">1,247 / 5,000</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full" style="width: 24.94%"></div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    Individual vote tallies will be revealed after the voting period ends
                                </p>
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-xs text-blue-800 dark:text-blue-200">
                                            <strong>Privacy Note:</strong> Zero-knowledge proofs ensure that only the final tally is visible. Your individual vote choice remains completely private.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Code Example -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            Implementation
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Add voting to your application with a single directive:
                        </p>
                        <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ "@midnightVoteForm([
    'proposalId' => 'proposal-001',
    'options' => ['Approve', 'Reject', 'Abstain']
])" }}</code></pre>
                    </div>
                </section>
            </div>

            <!-- Features Section -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    Privacy & Security Features
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Private Voting</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Zero-knowledge proofs ensure your vote choice remains completely private
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Verifiable</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            All votes are cryptographically verifiable on the blockchain
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Fast & Efficient</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Optimized proof generation for quick voting experience
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="mt-12 text-center text-gray-500 dark:text-gray-400 text-sm">
            <p>Midnight Laravel Integration &copy; {{ date('Y') }}</p>
        </footer>
    </div>
</body>
</html>
