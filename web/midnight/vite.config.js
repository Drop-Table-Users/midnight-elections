import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],

    build: {
        // Library mode for package distribution
        lib: {
            entry: resolve(__dirname, 'resources/js/app.ts'),
            name: 'Midnight',
            formats: ['es', 'umd'],
            fileName: (format) => `midnight.${format}.js`
        },

        // Output directory for publishable assets
        outDir: 'dist',
        emptyOutDir: true,

        // Source maps for development
        sourcemap: true,

        // Minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: false,
                drop_debugger: true
            }
        },

        // Rollup options
        rollupOptions: {
            // Externalize dependencies that shouldn't be bundled
            external: ['vue', 'alpinejs', 'axios'],
            output: {
                // Global variables for UMD build
                globals: {
                    vue: 'Vue',
                    alpinejs: 'Alpine',
                    axios: 'axios'
                },
                // Asset file names
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name === 'style.css') {
                        return 'midnight.css';
                    }
                    return assetInfo.name;
                },
                // Preserve module structure for tree-shaking
                preserveModules: false,
                // Manual chunks for better code splitting
                manualChunks: undefined
            }
        },

        // CSS code splitting
        cssCodeSplit: false,

        // Target modern browsers
        target: 'es2022',

        // Report compressed size
        reportCompressedSize: true,

        // Chunk size warnings
        chunkSizeWarningLimit: 500
    },

    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@css': resolve(__dirname, 'resources/css')
        }
    },

    // Development server configuration
    server: {
        port: 5173,
        strictPort: false,
        host: true
    },

    // Define global constants
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false
    },

    // Optimize dependencies
    optimizeDeps: {
        include: ['vue', 'axios'],
        exclude: ['alpinejs']
    }
});
