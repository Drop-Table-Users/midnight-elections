<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Midnight Wallet Connection Demo - Learn how to integrate Midnight blockchain wallet connectivity into your Laravel application">
    <meta name="keywords" content="midnight, blockchain, wallet, web3, laravel, demo">
    <title>Midnight Wallet Demo - Wallet Connection Integration</title>

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
                        Midnight Wallet Demo
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Connect your Midnight wallet and explore blockchain integration
                    </p>
                </div>
                <nav class="flex items-center space-x-4">
                    <a href="/midnight/examples/voting-demo" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Voting Demo
                    </a>
                    <a href="/midnight/examples/transaction-tracking-demo" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Transaction Demo
                    </a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="space-y-8">
            <!-- Wallet Connection Section -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Wallet Connection
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Connect your Midnight wallet to interact with the blockchain. Your wallet information will be displayed below.
                </p>

                <!-- Basic Wallet Connection -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        Basic Connection
                    </h3>
                    @midnightWallet
                </div>

                <!-- Wallet with Balance -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        With Balance Display
                    </h3>
                    @midnightWallet(['showBalance' => true])
                </div>

                <!-- Custom Styled Wallet -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        Custom Styling
                    </h3>
                    @midnightWallet([
                        'class' => 'inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold rounded-full shadow-xl transition-all transform hover:scale-105',
                        'showBalance' => true,
                        'showNetwork' => true
                    ])
                </div>
            </section>

            <!-- Event Listeners Section -->
            <section
                class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8"
                x-data="{
                    events: [],
                    addEvent(type, data) {
                        this.events.unshift({
                            type,
                            data,
                            timestamp: new Date().toLocaleTimeString()
                        });
                        if (this.events.length > 10) this.events.pop();
                    }
                }"
                @wallet-connected.window="addEvent('Connected', $event.detail)"
                @wallet-disconnected.window="addEvent('Disconnected', {})"
                @wallet-error.window="addEvent('Error', $event.detail)"
            >
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Wallet Events
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Real-time event log showing wallet connection status changes
                </p>

                <div class="bg-gray-900 dark:bg-black rounded-lg p-4 font-mono text-sm max-h-96 overflow-y-auto">
                    <div x-show="events.length === 0" class="text-gray-500">
                        No events yet. Try connecting your wallet...
                    </div>
                    <template x-for="(event, index) in events" :key="index">
                        <div class="mb-2 pb-2 border-b border-gray-800 last:border-0">
                            <div class="flex items-center justify-between mb-1">
                                <span
                                    class="font-semibold"
                                    :class="{
                                        'text-green-400': event.type === 'Connected',
                                        'text-red-400': event.type === 'Disconnected',
                                        'text-yellow-400': event.type === 'Error'
                                    }"
                                    x-text="event.type"
                                ></span>
                                <span class="text-gray-500 text-xs" x-text="event.timestamp"></span>
                            </div>
                            <pre class="text-gray-300 text-xs overflow-x-auto" x-text="JSON.stringify(event.data, null, 2)"></pre>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Code Examples Section -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Code Examples
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Learn how to use the Midnight wallet components in your Laravel Blade templates
                </p>

                <div class="space-y-6">
                    <!-- Example 1 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Basic Usage
                        </h3>
                        <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ '@midnightWallet' }}</code></pre>
                    </div>

                    <!-- Example 2 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            With Balance and Network
                        </h3>
                        <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ "@midnightWallet(['showBalance' => true, 'showNetwork' => true])" }}</code></pre>
                    </div>

                    <!-- Example 3 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Custom Styling
                        </h3>
                        <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">{{ "@midnightWallet([
    'class' => 'custom-btn-class',
    'showBalance' => true
])" }}</code></pre>
                    </div>

                    <!-- Example 4 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Listening to Events
                        </h3>
                        <pre class="bg-gray-900 dark:bg-black rounded-lg p-4 overflow-x-auto"><code class="text-gray-300 text-sm">&lt;div
    x-data="{ connected: false }"
    @wallet-connected.window="connected = true"
    @wallet-disconnected.window="connected = false"
&gt;
    &lt;p x-show="connected"&gt;Wallet is connected!&lt;/p&gt;
&lt;/div&gt;</code></pre>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Features
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Auto-reconnect</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Automatically reconnects to previously connected wallets</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Network Detection</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Displays current network (Mainnet, Testnet, Local)</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Balance Display</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Optional real-time wallet balance updates</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Event System</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Custom events for integration with Alpine.js</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Dark Mode</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Fully responsive with dark mode support</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Accessible</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">ARIA labels and semantic HTML</p>
                        </div>
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
