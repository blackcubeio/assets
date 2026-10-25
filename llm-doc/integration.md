# assets — Yii integration

## Split

- Package: the command only, through config-plugin `params-console` (`yiisoft/yii-console.commands['blackcube:assets/init']`).
- Application: aliases, DI definitions, `assetLoader` params, bundles, registration in the view.

## Aliases

The application's own, used in the bundles' `basePath` / `baseUrl` / `sourcePath`.

| Use | README | starter (`config/common/aliases.php`) |
|---|---|---|
| public publication directory (`basePath`) | `@assets` = `@root/www/assets` | `@assets` = `@public/assets` |
| public URL (`baseUrl`) | `@assetsUrl` = `@baseUrl/assets` | `@assetsUrl` = `/assets` |
| build output (`sourcePath`) | `@assetsSource/dist-<builder>` | `@src/Assets/App/dist-webpack` |

- `@root` is needed by `blackcube:assets/init`.
- dboard sets `@dboard` (= `dboard/src`) in `config/common/bootstrap.php`.

## DI definitions (web group, e.g. `config/web/di/assets.php`)

```php
use Blackcube\Assets\ManifestAssetLoader;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    AssetLoader::class => [
        '__construct()' => [
            'appendTimestamp' => $params['yiisoft/assets']['assetLoader']['appendTimestamp'],
            'assetMap' => $params['yiisoft/assets']['assetLoader']['assetMap'],
            'basePath' => $params['yiisoft/assets']['assetLoader']['basePath'],
            'baseUrl' => $params['yiisoft/assets']['assetLoader']['baseUrl'],
        ],
    ],
    AssetLoaderInterface::class => [
        'class' => ManifestAssetLoader::class,
        '__construct()' => [
            'innerLoader' => Reference::to(AssetLoader::class),
            'aliases' => Reference::to(Aliases::class),
        ],
    ],
];
```

- yiisoft/assets declares `AssetLoaderInterface` → `AssetLoader` in its `di` group; the application definition replaces it.
- Without the `AssetLoader::class` definition, the inner loader is built with its defaults: `appendTimestamp`, `assetMap`, `basePath`, `baseUrl` from the params are ignored.
- yiisoft/assets' `AssetManager` receives `$container->get(AssetLoaderInterface::class)` = `ManifestAssetLoader`.
- Standard bundles (`AssetBundle`) go through the same loader unchanged (starter `FaviconAsset`, dboard `PasskeysAsset`, `StaticAsset`).
- Reference: starter `config/web/di/assets.php`.

## Params `yiisoft/assets.assetLoader`

| Key | Effect |
|---|---|
| `basePath` / `baseUrl` | default for bundles without their own (`AssetLoader.php:243-260`); a bundle that declares them is not affected. Starter: `@assets` / `@assetsUrl` (`config/web/params.php`) |
| `appendTimestamp` | `?v=<mtime>` on published URLs (`AssetLoader.php:93-95`); default `false` |
| `assetMap` | replaces an asset with another URL; default `[]` |

- A `cdn = true` bundle without `baseUrl` receives the loader's `baseUrl` (`AssetLoader.php:105`) and its URLs get prefixed (`AssetLoader.php:76-79`): declare `baseUrl` in such a bundle.
- These params reach the inner loader only through the `AssetLoader::class` DI definition.

## Bundle

- One bundle = one application; several applications → one bundle per application (`AppXxxAsset`).
- Extends `ViteAssetBundle` or `WebpackAssetBundle`; declares `basePath`, `baseUrl`, and `sourcePath` = the `dist-vite` / `dist-webpack` directory that holds the catalog.
- `$css` / `$js` declared by the class come before the catalog's (starter `AppAsset`: Google fonts + `preconnect`).
- References: starter `src/Assets/AppAsset.php` (`sourcePath` `@src/Assets/App/dist-webpack`), dboard `src/Assets/AppAsset.php` (`sourcePath` `@dboard/Assets/App/dist-webpack`).

## Registration in the view

Starter layout (`src/Views/Layouts/main.php:24-32`):
```php
$assetManager->register(AppAsset::class);
$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
$bundle = $assetManager->getBundle(AppAsset::class);
$this->registerJsVar('webpackBaseUrl', $bundle->baseUrl . '/');
```

- `$bundle->baseUrl` = the published URL, `/assets/<hash>`.
- starter and dboard assign `webpackBaseUrl` to `__webpack_public_path__` in `startup.ts`.

## `customizedBundles` (`yiisoft/assets.assetManager.customizedBundles`)

Following `AssetManager::loadBundle()` (`AssetManager.php:438-454`):

| Value | Effect |
|---|---|
| absent | `loader->loadBundle($name)`: catalog read |
| `array` | `loader->loadBundle($name, $config)`: catalog read, unless `$config` sets `sourcePath` to `null` |
| `AssetBundle` instance | used as is, the loader is not called: catalog not read |
| `false` | bundle disabled: no file, no exception |
