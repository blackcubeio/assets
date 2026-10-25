import { defineConfig } from 'vite';
import { resolve } from 'path';
import { readFileSync } from 'fs';
import { blackcubeManifestPlugin } from './vite-manifest-plugin.mts';

// Load configuration
const config = JSON.parse(readFileSync('./assets-blackcube.json', 'utf-8'));

// Paths
const sourceDir = resolve(process.cwd(), config.sourceDir);
const outputDir = resolve(process.cwd(), `${config.outputBaseDir}/dist-vite`);

// Build entries
const entries: Record<string, string> = {};
for (const [name, entry] of Object.entries(config.entry)) {
    entries[name] = resolve(sourceDir, entry as string);
}

export default defineConfig({
    root: sourceDir,
    base: './',
    cacheDir: './.vite-cache',
    define: {
        APP_MODE: JSON.stringify(process.env.NODE_ENV || 'development'),
    },
    plugins: [
        // Add your plugins here (aurelia, tailwindcss, etc.)
        blackcubeManifestPlugin({
            catalogFile: config.catalog,
        }),
    ],
    build: {
        outDir: outputDir,
        emptyOutDir: true,
        target: 'esnext',
        manifest: false, // We use our own manifest plugin
        rollupOptions: {
            input: entries,
            output: {
                entryFileNames: `${config.subDirectories.js}/[name].[hash].js`,
                chunkFileNames: `${config.subDirectories.js}/[name].[hash].js`,
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name?.endsWith('.css')) {
                        return `${config.subDirectories.css}/[name].[hash][extname]`;
                    }
                    return 'assets/[name].[hash][extname]';
                },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
    server: {
        port: 9000,
    },
});
