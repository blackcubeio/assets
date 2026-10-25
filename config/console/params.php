<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Assets\Command\AssetsController;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'blackcube:assets/init' => AssetsController::class,
        ],
    ],
];