<?php
/*
 * @name:           Viscribe
 * @description     Uses AI to rename images during upload for SEO-friendly filenames.
 * @author          Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright       2025-2026 (C) Kolja Nolte
 * @see             https://docs.kolja-nolte.com/viscribe-wordpress-plugin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Released under the GNU General Public License v2 or later.
 * See: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Viscribe
 * @license GPL-2.0-or-later
 */

/**
 * Plugin Name:       Viscribe
 * Plugin URI:        https://docs.kolja-nolte.com/viscribe-wordpress-plugin
 * Description:       Free AI-powered image renaming for WordPress. Automatically replaces meaningless filenames with descriptive, SEO-friendly ones and adds alt texts — no manual work needed.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Kolja Nolte
 * Author URI:        https://www.kolja-nolte.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       viscribe
 * Domain Path:       /languages
 * Donate Link:       https://www.paypal.com/paypalme/thaikolja/10/
 *
 * @package Viscribe
 */

declare( strict_types=1 );

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use Viscribe\Plugin;

const VISCRIBE_VERSION = '1.0.0';

define( 'VISCRIBE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VISCRIBE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VISCRIBE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initializes and executes the Plugin object.
 *
 * This function retrieves the singleton instance of the Plugin class,
 * and subsequently calls its init() method to initialize it.
 *
 * @return void
 */
add_action( 'plugins_loaded', function () {
	Plugin::get_instance()->init();
} );
