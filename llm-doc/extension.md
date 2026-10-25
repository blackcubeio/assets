# assets — Extension

## New build type

A class extending `AssetBundle` and implementing `ManifestAssetBundleInterface`:
- `getCatalogFile(): string` — catalog filename in `sourcePath`;
- `loadFromCatalog(array $catalog): void` — receives the decoded JSON, fills `$js` / `$css`.

`ManifestAssetLoader` only reads and decodes the file (`ManifestAssetLoader.php:70-94`): the catalog shape is free, the bundle interprets it.

## `WebpackAssetBundle`

| Extension point | Visibility | Use |
|---|---|---|
| `$priorityBundles` | protected | redefine in the subclass; in auto mode these entries come first, in this order (`WebpackAssetBundle.php:126-135`) |
| `selectBundles(array $catalog): array` | protected | override; `parent::selectBundles($catalog)` returns the default selection, to filter or extend; `loadFromCatalog()` then applies `cssOnly` / `jsOnly` and skips missing names (`WebpackAssetBundle.php:94-110`) |
| `loadFromCatalog(array $catalog)` | public | override; `parent::loadFromCatalog($catalog)` loads the catalog, the subclass then adds or removes files |
| `$bundles`, `$cssOnly`, `$jsOnly`, `$catalogFile` | public | through a subclass or through config (`customizedBundles`) |

## `ViteAssetBundle`

| Extension point | Visibility | Use |
|---|---|---|
| `loadFromCatalog(array $catalog)` | public | override; `parent::loadFromCatalog($catalog)` loads every entry, the subclass then adds or removes files; no separate selection point |
| `$catalogFile` | public | through a subclass or through config |

## Not extensible

| Element | Reason |
|---|---|
| Catalog reading (path, decoding, errors) | `ManifestAssetLoader::readCatalog()` is `private` (`ManifestAssetLoader.php:70`) |
| Access to `innerLoader` / `aliases` from a `ManifestAssetLoader` subclass | `private readonly` (`ManifestAssetLoader.php:31-32`); to add behaviour, decorate `AssetLoaderInterface` again |
| `init` command: builders, questions, generated content | `BUILDER_*` constants and `generate*()` / `loadTemplate()` methods all `private` (`AssetsController.php:39-40`, `105-242`); templates read from the package `resources/templates/` (`AssetsController.php:240`) |
| File rewrite by `init` | no option: the command always rewrites (see `scaffolding.md`) |
