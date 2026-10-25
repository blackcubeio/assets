# assets — Scaffolding

## Command `blackcube:assets/init`

`Command\AssetsController`, Symfony command (`#[AsCommand]`), constructor `(Aliases $aliases)`.

| Question | Values | Default |
|---|---|---|
| Builder(s) | `vite`, `webpack`, `both` | `both` |
| Source directory (relative to the root) | free | `assets/src` |
| Output base directory (relative to the root) | free | `assets` |

- Needs the `@root` alias: without it, `InvalidArgumentException` `Invalid path alias: @root`.
- Creates no source file: only `<sourceDir>/` (`0755`) and its `tsconfig.json`.

Files written in `@root`:

| File | When | Re-run |
|---|---|---|
| `assets-blackcube.json` | always | rewritten |
| `package.json` | always | merged |
| `tsconfig.json` (template `tsconfig.json`: `include: ["*.mts"]`, `noEmit`) | always | rewritten |
| `<sourceDir>/tsconfig.json` (template `tsconfig.source.json`: `include: ["**/*.ts"]`) | always | rewritten |
| `vite.config.mts`, `vite-manifest-plugin.mts` | Vite chosen | rewritten |
| `webpack.config.mts` | Webpack chosen | rewritten |

Rewrites happen without asking.

## `assets-blackcube.json`

Generated with the defaults:
```json
{"builders":["vite","webpack"],"sourceDir":"assets/src","outputBaseDir":"assets","entry":{"app":"js/app.ts"},"subDirectories":{"js":"js","css":"css"},"catalog":"assets-catalog.json"}
```

- Read by both build configs through `readFileSync('./assets-blackcube.json')`: the build runs from the root.
- Keys read: `sourceDir`, `outputBaseDir`, `entry` (name → path relative to `sourceDir`), `subDirectories.js` / `.css`, `catalog`.
- `builders` is written, read by neither config.
- Re-running `init` resets `entry` to `{"app": "js/app.ts"}`.

## `package.json` merge

- Missing file → created with `name: app-assets`, `private: true`, `type: module`.
- Existing scripts kept; added or replaced:
  - Vite: `dist-clean-vite` = `vite build`;
  - Webpack: `dist-clean-webpack` = `webpack --mode production`, `watch` = `webpack --mode development --watch`;
  - `dist-clean` always replaced: chains the scripts of the chosen builders with `&&`.
- The scripts of a builder no longer chosen stay.
- Existing devDependencies kept; the ones below are overwritten with these versions (`vite ^5` → `^6`), then sorted by key:
  - always: `typescript ^5.0`, `ts-node ^10.0`, `@types/node ^22.0`;
  - Vite: `vite ^6.0`, `sass ^1.80`;
  - Webpack: `webpack ^5.0`, `webpack-cli ^6.0`, `assets-webpack-plugin ^7.0`, `compression-webpack-plugin ^11.0`, `mini-css-extract-plugin ^2.0`, `ts-loader ^9.0`, `css-loader ^7.0`, `sass-loader ^16.0`, `postcss-loader ^8.0`, `source-map-loader ^5.0`, `sass ^1.80`.

## Vite template (`vite.config.mts` + `vite-manifest-plugin.mts`)

- `root` = `sourceDir`, `base: './'`, `cacheDir: './.vite-cache'`.
- Output `<outputBaseDir>/dist-vite`, `emptyOutDir: true`: emptied on every build.
- `target: 'esnext'`, `manifest: false`, `define` `APP_MODE` = `NODE_ENV` (default `development`), SCSS `api: 'modern-compiler'`, `server.port` 9000.
- Names: entries and chunks `<js>/[name].[hash].js`; CSS `<css>/[name].[hash][extname]`; other assets `assets/[name].[hash][extname]`.
- Plugins: `blackcubeManifestPlugin({ catalogFile: config.catalog })`; other plugins go into the `plugins` array.
- Catalog written on `writeBundle` in `<outDir>/<catalog>`:
  - one key per `isEntry` chunk, `js` = its file;
  - `css` = the first `.css` of the bundle whose path contains the entry name (`vite-manifest-plugin.mts:35-37`);
  - non-entry chunks are not listed.

## Webpack template (`webpack.config.mts`)

- Mode: production when `NODE_ENV=production` or `argv` contains `-p` or `production`; otherwise development.
- Output `<outputBaseDir>/dist-webpack`, `clean: false`: files from previous builds stay in `dist-webpack`.
- Production names `<js>/[name].[chunkhash:8].js`, `<css>/[name].[chunkhash:8].css`; development without hash; source maps in development only.
- Rules:
  - `ts-loader` with `<sourceDir>/tsconfig.json`, `source-map-loader` as pre-loader on `.js` / `.ts`;
  - `.scss` / `.sass`: `MiniCssExtractPlugin` + `css-loader` + `postcss-loader` + `sass-loader`;
  - `.css`: `MiniCssExtractPlugin` + `css-loader` + `postcss-loader`;
  - fonts and images: `assets/[name][ext]`, no hash.
- Chunks: `runtimeChunk` named `manifest`; `splitChunks`: `vendors` (`node_modules`, initial chunks), `vendors.async` and `commons.async` (async chunks).
- Plugins:
  - `DefinePlugin` `PRODUCTION`;
  - `CompressionWebpackPlugin`: a `.gz` next to every `.js` / `.css` / `.map` above 10 bytes;
  - `AssetsWebpackPlugin`: writes `<catalog>` in `dist-webpack`, drops the empty-name entry and the `auto/` prefix of the paths.
- Resolution: extensions `.ts`, `.tsx`, `.js`; modules `[sourceDir, node_modules]`.
- Real catalog (starter, `src/Assets/App/dist-webpack/assets-catalog.json`):
  ```json
  {"manifest":{"js":"js/manifest.c1fe72f7.js"},"app":{"css":"css/app.0a865006.css","js":"js/app.0a865006.js"},"vendors":{"js":"js/vendors.d7afa1ed.js"}}
  ```
  `WebpackAssetBundle` loads it as `manifest` → `vendors` → `app`.
