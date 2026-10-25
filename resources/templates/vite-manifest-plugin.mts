import type { Plugin, ResolvedConfig } from 'vite';
import { writeFileSync } from 'fs';
import { resolve, relative } from 'path';

interface ManifestPluginOptions {
    catalogFile: string;
}

interface CatalogEntry {
    js?: string;
    css?: string;
}

type Catalog = Record<string, CatalogEntry>;

export function blackcubeManifestPlugin(options: ManifestPluginOptions): Plugin {
    let config: ResolvedConfig;

    return {
        name: 'blackcube-manifest',
        configResolved(resolvedConfig) {
            config = resolvedConfig;
        },
        writeBundle(outputOptions, bundle) {
            const catalog: Catalog = {};
            const outDir = outputOptions.dir || config.build.outDir;

            for (const [fileName, chunk] of Object.entries(bundle)) {
                if (chunk.type === 'chunk' && chunk.isEntry) {
                    const name = chunk.name;
                    catalog[name] = catalog[name] || {};
                    catalog[name].js = fileName;

                    // Find associated CSS
                    const cssFile = Object.keys(bundle).find(
                        (f) => f.endsWith('.css') && f.includes(name)
                    );
                    if (cssFile) {
                        catalog[name].css = cssFile;
                    }
                }
            }

            // Write catalog to output directory
            const catalogPath = resolve(outDir, options.catalogFile);
            writeFileSync(catalogPath, JSON.stringify(catalog, null, 2));
            console.log(`\n  Catalog written: ${relative(process.cwd(), catalogPath)}`);
        },
    };
}
