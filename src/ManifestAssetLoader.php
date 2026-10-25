<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Assets;

use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetLoaderInterface;

/**
 * Decorator for AssetLoader that handles manifest-based bundles.
 *
 * Works with all standard AssetBundle classes.
 * Adds manifest loading for bundles implementing ManifestAssetBundleInterface.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ManifestAssetLoader implements AssetLoaderInterface
{
    public function __construct(
        private readonly AssetLoaderInterface $innerLoader,
        private readonly Aliases $aliases,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
    {
        return $this->innerLoader->getAssetUrl($bundle, $assetPath);
    }

    /**
     * @inheritDoc
     *
     * A config setting sourcePath to null skips the catalog: AssetManager disables
     * a bundle by loading it with the config of an empty AssetBundle.
     */
    public function loadBundle(string $name, array $config = []): AssetBundle
    {
        $bundle = $this->innerLoader->loadBundle($name, $config);
        $sourceRemoved = array_key_exists('sourcePath', $config) === true && $config['sourcePath'] === null;

        if ($bundle instanceof ManifestAssetBundleInterface && $sourceRemoved === false) {
            $catalog = $this->readCatalog($bundle);
            $bundle->loadFromCatalog($catalog);
        }

        return $bundle;
    }

    /**
     * Reads and decodes the catalog JSON file.
     *
     * @param AssetBundle&ManifestAssetBundleInterface $bundle
     * @return array Decoded catalog
     * @throws RuntimeException If sourcePath is not set or catalog file not found
     */
    private function readCatalog(AssetBundle&ManifestAssetBundleInterface $bundle): array
    {
        if ($bundle->sourcePath === null) {
            throw new RuntimeException(
                'sourcePath must be set for manifest bundle ' . $bundle::class
            );
        }

        $sourcePath = $this->aliases->get($bundle->sourcePath);
        $catalogPath = $sourcePath . '/' . $bundle->getCatalogFile();

        if (file_exists($catalogPath) === false) {
            throw new RuntimeException(
                'Catalog file not found: ' . $catalogPath
            );
        }

        $content = file_get_contents($catalogPath);
        if ($content === false) {
            throw new RuntimeException(
                'Failed to read catalog file: ' . $catalogPath
            );
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
