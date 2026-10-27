import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin';
import { existsSync, mkdirSync, readdirSync, copyFileSync } from 'node:fs'
import { resolve, dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))

function copyStaticImages() {
    return {
        name: 'copy-static-images',
        closeBundle() {
            const src  = resolve(__dirname, 'resources/images')
            const dest = resolve(__dirname, 'src/dist/images')
            if (!existsSync(src)) return
            mkdirSync(dest, { recursive: true })
            for (const file of readdirSync(src)) {
                copyFileSync(join(src, file), join(dest, file))
            }
        },
    }
}


export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/lingua.js',
            refresh: true,
        }),
        copyStaticImages(),
    ],
    build: {
        manifest: false,
        outDir: 'src/dist',
        emptyOutDir: true,
        cssMinify: true,
        sourcemap: 'hidden',
        minify: true,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                entryFileNames: 'js/[name].min.js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
})
