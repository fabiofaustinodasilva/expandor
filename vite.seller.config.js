import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'node:path';

/**
 * Classic IIFE vendor for Blade layouts (window.L / window.lucide).
 * Separate from Laravel Vite so map scripts keep parse-time globals.
 */
export default defineConfig({
    plugins: [tailwindcss()],
    base: './',
    publicDir: false,
    build: {
        outDir: resolve(__dirname, 'public/vendor/expandor'),
        emptyOutDir: true,
        sourcemap: false,
        cssCodeSplit: false,
        rollupOptions: {
            input: resolve(__dirname, 'resources/js/seller-app.js'),
            output: {
                format: 'iife',
                name: 'ExpandorSellerVendors',
                entryFileNames: 'seller-app.js',
                assetFileNames: (asset) => {
                    if (asset.name && asset.name.endsWith('.css')) {
                        return 'seller-app.css';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});
