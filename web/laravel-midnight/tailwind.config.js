/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.ts',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                midnight: {
                    primary: 'var(--midnight-primary)',
                    'primary-hover': 'var(--midnight-primary-hover)',
                    'primary-active': 'var(--midnight-primary-active)',
                    success: 'var(--midnight-success)',
                    error: 'var(--midnight-error)',
                    warning: 'var(--midnight-warning)',
                    info: 'var(--midnight-info)',
                }
            },
            spacing: {
                'midnight-xs': 'var(--midnight-spacing-xs)',
                'midnight-sm': 'var(--midnight-spacing-sm)',
                'midnight-md': 'var(--midnight-spacing-md)',
                'midnight-lg': 'var(--midnight-spacing-lg)',
                'midnight-xl': 'var(--midnight-spacing-xl)',
            },
            borderRadius: {
                'midnight-sm': 'var(--midnight-radius-sm)',
                'midnight-md': 'var(--midnight-radius-md)',
                'midnight-lg': 'var(--midnight-radius-lg)',
                'midnight-xl': 'var(--midnight-radius-xl)',
            },
            boxShadow: {
                'midnight-sm': 'var(--midnight-shadow-sm)',
                'midnight-md': 'var(--midnight-shadow-md)',
                'midnight-lg': 'var(--midnight-shadow-lg)',
            }
        },
    },
    plugins: [],
    prefix: '',
};
