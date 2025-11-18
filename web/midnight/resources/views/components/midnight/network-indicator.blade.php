@props([
    'network' => null,
])

<div
    x-data="{
        network: '{{ $network }}',

        getNetworkColor() {
            switch (this.network?.toLowerCase()) {
                case 'mainnet':
                    return 'green';
                case 'testnet':
                    return 'yellow';
                case 'local':
                    return 'blue';
                default:
                    return 'gray';
            }
        },

        getNetworkIcon() {
            switch (this.network?.toLowerCase()) {
                case 'mainnet':
                    return 'M5 13l4 4L19 7';
                case 'testnet':
                    return 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z';
                case 'local':
                    return 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z';
                default:
                    return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            }
        }
    }"
    {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium']) }}
    :class="{
        'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800': getNetworkColor() === 'green',
        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800': getNetworkColor() === 'yellow',
        'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800': getNetworkColor() === 'blue',
        'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-800': getNetworkColor() === 'gray'
    }"
>
    <svg
        class="w-3.5 h-3.5 mr-1.5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            :d="getNetworkIcon()"
        ></path>
    </svg>
    <span x-text="network || 'Unknown'"></span>
</div>
