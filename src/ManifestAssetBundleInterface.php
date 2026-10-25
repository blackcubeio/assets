<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Assets;

/**
 * Interface for asset bundles that load files from a build manifest.
 *
 * Bundles implementing this interface will have their manifest read by
 * ManifestAssetLoader and parsed via loadFromCatalog().
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
interface ManifestAssetBundleInterface
{
    /**
     * Returns the catalog filename relative to sourcePath.
     *
     * @return string Catalog filename (e.g., 'assets-catalog.json')
     */
    public function getCatalogFile(): string;

    /**
     * Populates $js and $css arrays from the catalog data.
     *
     * Each bundle type implements its own parsing logic.
     *
     * @param array $catalog Decoded JSON catalog
     */
    public function loadFromCatalog(array $catalog): void;
}
