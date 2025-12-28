<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog WordPress plugin.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 or later license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Customize PHP Scoper configuration to fix Rector namespace conflicts.
 *
 * @param array $config Pre-configured configuration array from wpify/scoper
 *
 * @return array Valid PHP Scoper configuration array
 */
function customize_php_scoper_config(array $config): array
{
    // Add a patcher to restore original Rector namespaces in rector-migrate.php files
    // These files are configuration files for Rector and must use non-scoped Rector classes
    $config['patchers'][] = static function (string $filePath, string $prefix, string $content): string {
        // Check if this is a rector-migrate.php file
        if (str_contains($filePath, 'thecodingmachine/safe')
            && str_contains($filePath, 'rector-migrate.php')) {
            // Restore original Rector namespaces
            return str_replace(
                'WpSpaghetti\Deps\Rector\\',
                'Rector\\',
                $content
            );
        }

        return $content;
    };

    return $config;
}
