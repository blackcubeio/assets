# Blackcube Assets

Vite and Webpack asset bundles for Yii with manifest support.

[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[![Packagist Version](https://img.shields.io/packagist/v/blackcube/assets.svg)](https://packagist.org/packages/blackcube/assets)

## Installation
```bash
composer require blackcube/assets
```

## Configuration

### Aliases

In `config/web/aliases.php`, ensure these aliases are defined:
```php
<?php

declare(strict_types=1);

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
| `@assetsSource` | `$sourcePath` | Path to build output (optional shortcut) |

### DI Container

Create `config/web/di/assets.php`:
```php
<?php

declare(strict_types=1);

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

## Quick Start

### 1. Initialize build tools (once)
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

### 2. Build
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

### 3. Create your asset bundle(s)

Create one or more bundles pointing to the build output:

**Vite:**
```php
<?php

declare(strict_types=1);

namespace App\Asset;

use Blackcube\Assets\ViteAssetBundle;

class AppAsset extends ViteAssetBundle
{
    public ?string $basePath = '@assets';
    public ?string $baseUrl = '@assetsUrl';
    public ?string $sourcePath = '@assetsSource/dist-vite';
}
```

**Webpack:**
```php
<?php

declare(strict_types=1);

namespace App\Asset;

use Blackcube\Assets\WebpackAssetBundle;

class AppAsset extends WebpackAssetBundle
{
    public ?string $basePath = '@assets';
    public ?string $baseUrl = '@assetsUrl';
    public ?string $sourcePath = '@assetsSource/dist-webpack';
}
```

### 4. Register in view
```php
$assetManager->register(AppAsset::class);
```

## npm Scripts

| Script | Description |
|--------|-------------|
| `npm run dist-clean` | Build all (Vite + Webpack) |
| `npm run dist-clean-vite` | Build Vite only |
| `npm run dist-clean-webpack` | Build Webpack only |
| `npm run watch` | Webpack dev mode with watch |

## Advanced Options

### WebpackAssetBundle
```php
class AppAsset extends WebpackAssetBundle
{
    public ?string $basePath = '@assets';
    public ?string $baseUrl = '@assetsUrl';
    public ?string $sourcePath = '@assetsSource/dist-webpack';

    // Explicit bundle order (default: auto-detect from catalog)
    public array $bundles = ['manifest', 'vendors', 'app'];

    // Load only CSS from these bundles (skip JS)
    public array $cssOnly = [];

    // Load only JS from these bundles (skip CSS)
    public array $jsOnly = ['manifest'];
}
```

By default, `WebpackAssetBundle` auto-loads all bundles from the catalog with ordering: `manifest` → `vendors` → rest.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

## Author

Philippe Gaultier <philippe@blackcube.io>