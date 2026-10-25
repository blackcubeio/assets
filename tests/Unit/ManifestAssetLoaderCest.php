<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Assets\Tests\Unit;

use Blackcube\Assets\ManifestAssetLoader;
use Blackcube\Assets\ManifestAssetBundleInterface;
use Blackcube\Assets\ViteAssetBundle;
use Blackcube\Assets\WebpackAssetBundle;
use Blackcube\Assets\Tests\Support\UnitTester;
use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetLoaderInterface;

/**
 * Unit tests for ManifestAssetLoader.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ManifestAssetLoaderCest
{
    private string $dataPath;
    private Aliases $aliases;

    public function _before(UnitTester $I): void
    {
        $this->dataPath = dirname(__DIR__) . '/Support/Data';
        $this->aliases = new Aliases([
            '@test' => $this->dataPath,
            '@assets' => $this->dataPath,
        ]);
    }


    public function testImplementsAssetLoaderInterface(UnitTester $I): void
    {
        $I->assertTrue(
            in_array(AssetLoaderInterface::class, class_implements(ManifestAssetLoader::class), true)
        );
    }


    public function testLoadBundleLoadsViteCatalog(UnitTester $I): void
    {
        $innerLoader = new class($this->aliases) implements AssetLoaderInterface {
            private Aliases $aliases;

            public function __construct(Aliases $aliases)
            {
                $this->aliases = $aliases;
            }

            public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
            {
                return $assetPath;
            }

            public function loadBundle(string $name, array $config = []): AssetBundle
            {
                $bundle = new ViteAssetBundle();
                $bundle->sourcePath = '@test';
                $bundle->catalogFile = 'vite-catalog.json';
                return $bundle;
            }
        };

        $loader = new ManifestAssetLoader($innerLoader, $this->aliases);
        $bundle = $loader->loadBundle(ViteAssetBundle::class);

        $I->assertEquals([
            'js/app.abc123.js',
            'js/vendor.xyz789.js',
        ], $bundle->js);
        $I->assertEquals(['css/app.def456.css'], $bundle->css);
    }


    public function testLoadBundleLoadsWebpackCatalogWithAutoDetection(UnitTester $I): void
    {
        $innerLoader = new class($this->aliases) implements AssetLoaderInterface {
            private Aliases $aliases;

            public function __construct(Aliases $aliases)
            {
                $this->aliases = $aliases;
            }

            public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
            {
                return $assetPath;
            }

            public function loadBundle(string $name, array $config = []): AssetBundle
            {
                $bundle = new WebpackAssetBundle();
                $bundle->sourcePath = '@test';
                $bundle->catalogFile = 'webpack-catalog.json';
                return $bundle;
            }
        };

        $loader = new ManifestAssetLoader($innerLoader, $this->aliases);
        $bundle = $loader->loadBundle(WebpackAssetBundle::class);

        $I->assertEquals([
            'js/manifest.abc123.js',
            'js/vendors.def456.js',
            'js/app.xyz789.js',
        ], $bundle->js);
        $I->assertEquals(['css/app.uvw012.css'], $bundle->css);
    }


    public function testLoadBundleDoesNotModifyStandardBundle(UnitTester $I): void
    {
        $innerLoader = new class implements AssetLoaderInterface {
            public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
            {
                return $assetPath;
            }

            public function loadBundle(string $name, array $config = []): AssetBundle
            {
                $bundle = new AssetBundle();
                $bundle->js = ['standard.js'];
                $bundle->css = ['standard.css'];
                return $bundle;
            }
        };

        $loader = new ManifestAssetLoader($innerLoader, $this->aliases);
        $bundle = $loader->loadBundle(AssetBundle::class);

        $I->assertEquals(['standard.js'], $bundle->js);
        $I->assertEquals(['standard.css'], $bundle->css);
    }


    public function testThrowsExceptionWhenSourcePathMissing(UnitTester $I): void
    {
        $innerLoader = new class implements AssetLoaderInterface {
            public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
            {
                return $assetPath;
            }

            public function loadBundle(string $name, array $config = []): AssetBundle
            {
                $bundle = new ViteAssetBundle();
                $bundle->sourcePath = null;
                return $bundle;
            }
        };

        $loader = new ManifestAssetLoader($innerLoader, $this->aliases);

        $I->expectThrowable(RuntimeException::class, function () use ($loader) {
            $loader->loadBundle(ViteAssetBundle::class);
        });
    }

    public function testThrowsExceptionWhenCatalogNotFound(UnitTester $I): void
    {
        $innerLoader = new class implements AssetLoaderInterface {
            public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
            {
                return $assetPath;
            }

            public function loadBundle(string $name, array $config = []): AssetBundle
            {
                $bundle = new ViteAssetBundle();
                $bundle->sourcePath = '@test';
                $bundle->catalogFile = 'nonexistent.json';
                return $bundle;
            }
        };

        $loader = new ManifestAssetLoader($innerLoader, $this->aliases);

        $I->expectThrowable(RuntimeException::class, function () use ($loader) {
            $loader->loadBundle(ViteAssetBundle::class);
        });
    }


    public function testGetAssetUrlDelegatesToInnerLoader(UnitTester $I): void
    {
        $innerLoader = new class implements AssetLoaderInterface {
            public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
            {
                return '/delegated/' . $assetPath;
            }

            public function loadBundle(string $name, array $config = []): AssetBundle
            {
                return new AssetBundle();
            }
        };

        $loader = new ManifestAssetLoader($innerLoader, $this->aliases);
        $bundle = new AssetBundle();

        $url = $loader->getAssetUrl($bundle, 'test.js');

        $I->assertEquals('/delegated/test.js', $url);
    }
}
