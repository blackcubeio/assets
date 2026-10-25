# Installation

```bash
composer require blackcube/assets
```

## Requirements

- PHP 8.2+
- `ext-json`
- `yiisoft/aliases ^3.1`
- `yiisoft/assets ^5.1`
- `yiisoft/view ^12.2`
- `yiisoft/yii-console ^2.4`

## Yii configuration

### Aliases

In `config/web/aliases.php`:

```php
return [
    '@assets' => '@root/www/assets',
    '@assetsUrl' => '@baseUrl/assets',
    '@assetsSource' => '@root/assets',
    '@baseUrl' => '/',
    '@public' => '@root/www',
];
```

| Alias | Used by | Purpose |
|-------|---------|---------|
| `@assets` | `$basePath` | Where Yii publishes assets (copy/symlink) |
| `@assetsUrl` | `$baseUrl` | Public URL to access published assets |
| `@assetsSource` | `$sourcePath` | Path to build output |

### DI container

Create `config/web/di/assets.php`:

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

The inner `AssetLoader` is declared from the `yiisoft/assets` params: without this definition, `Reference::to(AssetLoader::class)` builds it with its defaults and `appendTimestamp`, `assetMap`, `basePath`, `baseUrl` are ignored.

### Console command

The package registers `blackcube:assets/init` via config-plugin automatically.

## Scaffolding

```bash
./yii blackcube:assets/init
```

Interactive prompts:

- Builder(s): `vite`, `webpack`, or `both`
- Source directory (default: `assets/src`)
- Output base directory (default: `assets`)

Generated files:

```
project/
├── assets-blackcube.json
├── package.json
├── tsconfig.json
├── vite.config.mts          # if Vite selected
├── vite-manifest-plugin.mts # if Vite selected
├── webpack.config.mts       # if Webpack selected
└── assets/
    └── src/
        └── tsconfig.json
```

## Build

```bash
npm install
npm run dist-clean
```

Output structure:

```
assets/
├── dist-vite/
│   ├── js/
│   ├── css/
│   └── assets-catalog.json
└── dist-webpack/
    ├── js/
    ├── css/
    └── assets-catalog.json
```

## npm scripts

| Script | Description |
|--------|-------------|
| `npm run dist-clean` | Build all (Vite + Webpack) |
| `npm run dist-clean-vite` | Build Vite only |
| `npm run dist-clean-webpack` | Build Webpack only |
| `npm run watch` | Webpack dev mode with watch |
