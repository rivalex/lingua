import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import laravel from 'laravel-vite-plugin';


export default defineConfig({
    plugins: [
        laravel({
            input: {
                'lingua': 'resources/js/lingua.js',
                'lingua-styles': 'resources/css/lingua.css',
            },
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        manifest: false,
        outDir: 'src/dist',
        emptyOutDir: true,
        cssMinify: true,
        sourcemap: true,
        minify: true,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                entryFileNames: 'js/[name].min.js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'css/lingua.min.css';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
})
