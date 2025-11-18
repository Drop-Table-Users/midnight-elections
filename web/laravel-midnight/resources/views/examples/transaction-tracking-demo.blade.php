<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Midnight Transaction Tracking Demo - Monitor and track blockchain transactions in real-time">
    <meta name="keywords" content="midnight, blockchain, transactions, tracking, monitoring, laravel, demo">
    <title>Midnight Transaction Tracking Demo - Real-time Monitoring</title>

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
                        Transaction Tracking Demo
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Monitor and track Midnight blockchain transactions in real-time
                    </p>
                </div>
                <nav class="flex items-center space-x-4">
                    <a href="/midnight/examples/wallet-demo" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Wallet Demo
                    </a>
                    <a href="/midnight/examples/voting-demo" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Voting Demo
                    </a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="space-y-8">
            <!-- Transaction Lookup -->
            <section
                class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8"
                x-data="{
                    txHash: '',
                    currentTxHash: null,
                    sampleTransactions: [
                        '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
                        '0xfedcba0987654321fedcba0987654321fedcba0987654321fedcba0987654321',
                        '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890'
                    ],

                    loadTransaction() {
                        if (this.txHash.trim()) {
                            this.currentTxHash = this.txHash.trim();
                        }
                    },

                    loadSample(index) {
                        this.txHash = this.sampleTransactions[index];
                        this.loadTransaction();
                    },

                    clearTransaction() {
                        this.txHash = '';
                        this.currentTxHash = null;
                    }
                }"
            >
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Track a Transaction
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Enter a transaction hash to view its status and details
                </p>

                <!-- Transaction Input -->
                <form @submit.prevent="loadTransaction()" class="space-y-4">
                    <div>
                        <label for="txHash" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Transaction Hash
                        </label>
                        <div class="flex space-x-2">
                            <input
                                type="text"
                                id="txHash"
                                x-model="txHash"
                                placeholder="0x..."
                                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            >
                            <button
                                type="submit"
                                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-colors"
                            >
                                Track
                            </button>
                            <button
                                type="button"
                                @click="clearTransaction()"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium rounded-lg transition-colors"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Sample Transactions -->
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Try a sample transaction:</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="loadSample(0)"
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition-colors"
                            >
                                Confirmed Transaction
                            </button>
                            <button
                                type="button"
                                @click="loadSample(1)"
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition-colors"
                            >
                                Pending Transaction
                            </button>
                            <button
                                type="button"
                                @click="loadSample(2)"
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition-colors"
                            >
                                Failed Transaction
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Transaction Status Display -->
                <div x-show="currentTxHash" x-cloak class="mt-6">
                    @midnightTransactionStatus([
                        'txHash' => null,
                        'showDetails' => true,
                        'autoRefresh' => true,
                        'refreshInterval' => 5000
                    ])
                </div>

                <!-- Empty State -->
                <div x-show="!currentTxHash" x-cloak class="mt-6 text-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
                    <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400">Enter a transaction hash above to start tracking</p>
                </div>
            </section>

            <!-- Real-time Updates -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Features -->
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        Features
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Real-time Updates</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">
                                    Automatic polling for transaction status changes
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmation Tracking</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">
                                    Visual progress bar showing confirmation count
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detailed Information</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">
                                    Block number, timestamp, gas usage, and more
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Explorer Integration</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">
                                    Direct links to view transactions on block explorer
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status Indicators</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">
                                    Color-coded status (pending, confirmed, failed)
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Code Examples -->
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        Implementation
                    </h2>

                    <div class="space-y-6">
                        <!-- Example 1 -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Basic Usage
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Display transaction status with default settings:
                            </p>
                            <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ "@midnightTransactionStatus(['txHash' => \$transaction->hash])" }}</code></pre>
                        </div>

                        <!-- Example 2 -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                With Auto-refresh
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Enable automatic status updates:
                            </p>
                            <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ "@midnightTransactionStatus([
    'txHash' => \$transaction->hash,
    'autoRefresh' => true,
    'refreshInterval' => 5000
])" }}</code></pre>
                        </div>

                        <!-- Example 3 -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Simple Status Only
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Hide detailed information:
                            </p>
                            <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ "@midnightTransactionStatus([
    'txHash' => \$transaction->hash,
    'showDetails' => false
])" }}</code></pre>
                        </div>

                        <!-- Example 4 -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Event Listeners
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                React to status updates:
                            </p>
                            <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">&lt;div
    @transaction-status-updated.window="
        console.log('Status:', $event.detail.status)
    "
&gt;
    @midnightTransactionStatus(['txHash' => $tx])
&lt;/div&gt;</code></pre>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Transaction States -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    Transaction States
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Pending -->
                    <div class="border-2 border-yellow-200 dark:border-yellow-800 rounded-lg p-6 bg-yellow-50 dark:bg-yellow-900/20">
                        <div class="flex items-center justify-center w-12 h-12 bg-yellow-500 rounded-full mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-yellow-900 dark:text-yellow-100 mb-2">Pending</h3>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            Transaction has been broadcast but not yet confirmed. Confirmation count is increasing.
                        </p>
                    </div>

                    <!-- Confirmed -->
                    <div class="border-2 border-green-200 dark:border-green-800 rounded-lg p-6 bg-green-50 dark:bg-green-900/20">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-500 rounded-full mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-2">Confirmed</h3>
                        <p class="text-sm text-green-800 dark:text-green-200">
                            Transaction has been successfully confirmed and included in a block with sufficient confirmations.
                        </p>
                    </div>

                    <!-- Failed -->
                    <div class="border-2 border-red-200 dark:border-red-800 rounded-lg p-6 bg-red-50 dark:bg-red-900/20">
                        <div class="flex items-center justify-center w-12 h-12 bg-red-500 rounded-full mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-red-900 dark:text-red-100 mb-2">Failed</h3>
                        <p class="text-sm text-red-800 dark:text-red-200">
                            Transaction was rejected or reverted. This could be due to insufficient gas, invalid parameters, or contract errors.
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
