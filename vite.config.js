import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import laravel from 'laravel-vite-plugin';
import prefixSelector from 'postcss-prefix-selector';


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
    css: {
        postcss: {
            plugins: [
                prefixSelector({
                    prefix: '.lingua',
                    exclude: [/^@/],
                    transform(prefix, selector, prefixedSelector) {
                        // Skip if already starts with the .lingua wrapper class
                        // (use word-boundary check so .lingua-editor / .lingua-modal are NOT skipped)
                        if (selector === prefix || /^\.lingua[\s,>~+]/.test(selector)) return selector;
                        // Scope :root/:host (including compound ":root,:host") to .lingua
                        if (/^(:root|:host)(,(:root|:host))*$/.test(selector.trim())) return prefix;
                        // Dark mode: strip :where(.dark,...) from wherever it appears in the selector
                        // and reconstruct as :where(.dark,...) .lingua <rest>
                        // so that .dark (ancestor on <html>) is always above .lingua in the DOM
                        if (selector.includes(':where(.dark')) {
                            const darkMatch = selector.match(/:where\(.dark[^)]*\)/);
                            if (darkMatch) {
                                const darkPart = darkMatch[0];
                                const rest = selector.replace(darkPart, '').trim();
                                return rest ? `${darkPart} ${prefix} ${rest}` : `${darkPart} ${prefix}`;
                            }
                        }
                        return prefixedSelector;
                    },
                }),
            ],
        },
    },
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
