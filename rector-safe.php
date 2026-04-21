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

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\Finder\Finder;

use function WpSpaghetti\Deps\Safe\getcwd;

require_once __DIR__.'/vendor-deps/scoper-autoload.php';

// First, load and apply Safe function replacements
$safeConfigPath = __DIR__.'/vendor-deps/thecodingmachine/safe/rector-migrate.php';
if (!file_exists($safeConfigPath)) {
    throw new RuntimeException('Safe rector-migrate.php not found. Run: composer install');
}

$safeConfig = require $safeConfigPath;

// Return a configuration that combines Safe rules with custom rules
return static function (RectorConfig $rectorConfig) use ($safeConfig): void {
    // First apply Safe configuration (function rename rules)
    if (is_callable($safeConfig)) {
        $safeConfig($rectorConfig);
    }

    // Collect paths: src/ + root PHP files
    $paths = [__DIR__.'/src'];

    // Add root PHP files (equivalent to withRootFiles())
    $rootPhpFilesFinder = (new Finder())
        ->files()
        ->in(getcwd())
        ->depth(0)
        ->ignoreDotFiles(false)
        ->ignoreVCSIgnored(true)
        ->name('*.php')
        ->name('.*.php')
        ->notName('.phpstorm.meta.php')
    ;

    foreach ($rootPhpFilesFinder as $rootPhpFileFinder) {
        $paths[] = $rootPhpFileFinder->getRealPath();
    }

    // Apply paths
    $rectorConfig->paths($paths);

    // Skip vendor-deps
    $rectorConfig->skip([
        __DIR__.'/vendor-deps',
    ]);

    // Apply sets (REQUIRED - these are the refactoring rules)
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::NAMING,
        SetList::INSTANCEOF,
        SetList::EARLY_RETURN,
        SetList::STRICT_BOOLEANS,
        LevelSetList::UP_TO_PHP_81,
    ]);
};
