# assets — API

## `ManifestAssetBundleInterface`

| Method | Role |
|---|---|
| `getCatalogFile(): string` | catalog filename, relative to `sourcePath` |
| `loadFromCatalog(array $catalog): void` | receives the decoded JSON (associative array), appends to `$js` / `$css` |

The implementing class extends `AssetBundle`: the loader types it `AssetBundle&ManifestAssetBundleInterface` (`ManifestAssetLoader.php:70`).

## `ManifestAssetLoader` (implements `AssetLoaderInterface`)

```php
new ManifestAssetLoader(
    AssetLoaderInterface $innerLoader,
    Aliases $aliases,
);
```

| Method | Behaviour |
|---|---|
| `getAssetUrl(AssetBundle $bundle, string $assetPath): string` | delegated as is to `innerLoader` |
| `loadBundle(string $name, array $config = []): AssetBundle` | `innerLoader->loadBundle($name, $config)`, then, when the bundle implements the interface and `$config` does not set `sourcePath` to `null`: reads the catalog, calls `loadFromCatalog()` |

- File read: `Aliases::get(sourcePath) . '/' . getCatalogFile()`; decoded with `json_decode(..., true, 512, JSON_THROW_ON_ERROR)` (`ManifestAssetLoader.php:78`, `94`).
- The catalog is read on every `loadBundle()`; AssetManager loads a bundle once per instance.

Errors:

| Case | Exception |
|---|---|
| `sourcePath` `null` on the bundle, config not setting it to `null` | `RuntimeException` `sourcePath must be set for manifest bundle <FQCN>` |
| file missing | `RuntimeException` `Catalog file not found: <path>` |
| read failure | `RuntimeException` `Failed to read catalog file: <path>` |
| invalid JSON | `JsonException` (`Syntax error`) |

## `ViteAssetBundle`

| Property | Default |
|---|---|
| `$catalogFile` | `'assets-catalog.json'` |
| `$jsOptions` | `['type' => 'module', 'defer' => true]` |
| `$jsPosition` | not redefined: `null` inherited from `AssetBundle` |

`loadFromCatalog()`: every entry, in catalog order; `js` appended to `$js`, `css` to `$css` when present; no selection, no filter.

## `WebpackAssetBundle`

| Property | Visibility | Default |
|---|---|---|
| `$jsPosition` | public | `WebView::POSITION_HEAD` |
| `$jsOptions` | public | `['defer' => 'defer']` |
| `$catalogFile` | public | `'assets-catalog.json'` |
| `$bundles` | public | `[]` |
| `$priorityBundles` | protected | `['manifest', 'vendors']` |
| `$cssOnly` | public | `[]` |
| `$jsOnly` | public | `[]` |

Order:
- `$bundles` empty → names of `$priorityBundles` present in the catalog, in `$priorityBundles` order, then the other entries in catalog order.
- `$bundles` not empty → exactly that order; a name missing from the catalog is skipped without error.

Filters:
- name in `$cssOnly` → its `js` is not appended;
- name in `$jsOnly` → its `css` is not appended;
- name in both → nothing is appended (`WebpackAssetBundle.php:103`, `107`).

Selection: `protected selectBundles(array $catalog): array` (see `extension.md`).

## Both bundles

- Catalog paths are appended as is, after the `$js` / `$css` declared by the class.
- `$catalogFile` changes through a subclass or through config: `loadBundle(X::class, ['catalogFile' => 'other.json'])`.
