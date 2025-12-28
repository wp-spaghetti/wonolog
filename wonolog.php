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

namespace WpSpaghetti\Wonolog;

/*
 * Plugin Name: Wonolog
 * Plugin URI: https://github.com/wp-spaghetti/wonolog
 * Description: Opinionated WordPress logging plugin built on top of Inpsyde's Wonolog
 * Version: 0.1.0
 * Text Domain: wonolog
 * Domain Path: /languages
 * Author: Frugan
 * Author URI: https://github.com/wp-spaghetti
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 8.1
 * License: GPL-3.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Donate link: https://buymeacoff.ee/frugan
 * Update URI: https://git-updater.com
 */

use WpSpaghetti\Deps\Inpsyde\Wonolog\Configurator;

if (!\defined('WPINC')) {
    exit;
}

// Autoload dependencies
require_once __DIR__.'/vendor-deps/scoper-autoload.php';

require_once __DIR__.'/vendor/autoload.php';

if (!class_exists(Configurator::class)) {
    return;
}

// Initialize Wonolog with opinionated configuration
add_action(Configurator::ACTION_SETUP, static function (Configurator $configurator): void {
    $config = new Bootstrap();
    $config->configure($configurator);
});
