# blackcube/assets — LLM index

**Role:** yiisoft/assets bundles fed by a JSON catalog produced at build time (Vite or Webpack), plus a command that generates the build files.

## Package card

| | |
|---|---|
| Namespace | `Blackcube\Assets` |
| Contract | `ManifestAssetBundleInterface` (`getCatalogFile()`, `loadFromCatalog(array)`) |
| Loader | `ManifestAssetLoader`, decorator of `AssetLoaderInterface` |
| Bundles | `ViteAssetBundle`, `WebpackAssetBundle` |
| Command | `Command\AssetsController` = `blackcube:assets/init` (Symfony `Command`) |
| Templates | `resources/templates/`: `vite.config.mts`, `vite-manifest-plugin.mts`, `webpack.config.mts`, `tsconfig.json`, `tsconfig.source.json` |
| Requires | PHP ^8.2, `ext-json`, `yiisoft/aliases` ^3.1, `yiisoft/assets` ^5.1, `yiisoft/view` ^12.2, `yiisoft/yii-console` ^2.4. No Blackcube dependency. |
| Yii config | config-plugin: `params-console` only (declares the command). No `di` definition: the application declares `ManifestAssetLoader`. |
| License | BSD-3-Clause |

## Rules

- Catalog reading: only `ManifestAssetLoader::loadBundle()` calls `loadFromCatalog()` (`ManifestAssetLoader.php:55-58`). With the standard `AssetLoader`, a Vite or Webpack bundle receives nothing from the catalog.
- Catalog format: `{ "<entry>": { "js"?: "<path>", "css"?: "<path>" } }`, paths relative to `sourcePath`. The file read is `<sourcePath>/<catalogFile>`.
- `sourcePath` is mandatory on the class: without it, `RuntimeException` `sourcePath must be set for manifest bundle X`.
- A config that explicitly sets `sourcePath` to `null` skips the catalog: a bundle disabled by `customizedBundles => [X::class => false]` loads no file and throws nothing.
- The publisher publishes the whole `sourcePath` directory: files are served under `/assets/<hash>/js/...`.
- AssetManager keeps the loaded bundle: the catalog is read once per bundle per AssetManager instance.
- `loadFromCatalog()` appends its files after the `$js` / `$css` already declared by the class.
- `blackcube:assets/init` rewrites `assets-blackcube.json`, `tsconfig.json`, `<source>/tsconfig.json` and the `*.config.mts` without asking; only `package.json` is merged.

## Parts

| File | Content | Read when |
|---|---|---|
| `api.md` | The interface. `ManifestAssetLoader`: delegation, catalog skipped when `sourcePath` is `null`, errors. `ViteAssetBundle`: order, `jsOptions`, `jsPosition` not set. `WebpackAssetBundle`: order `manifest` → `vendors` → rest, explicit `$bundles` (missing names skipped), `cssOnly` / `jsOnly`, `jsPosition` HEAD. | before declaring a bundle |
| `scaffolding.md` | `blackcube:assets/init`: questions and defaults, generated files, `assets-blackcube.json`, `package.json` merge, rewrite. Templates: `dist-vite` / `dist-webpack` outputs, catalog production (Vite plugin, `AssetsWebpackPlugin`), `manifest` / `vendors` chunks, `clean: false` on the Webpack side. | before running `init` or changing the build |
| `integration.md` | Yii: aliases; DI definitions (inner `AssetLoader` built from the `yiisoft/assets` params, then `ManifestAssetLoader` as `AssetLoaderInterface`); command; registration in the view; disabling through `customizedBundles`. References: starter and dboard `AppAsset`. | before plugging the package in |
| `extension.md` | New build type (implement the interface); `selectBundles()` and `$priorityBundles` (`protected`); `catalogFile` through config; what does not extend. | before extending |
