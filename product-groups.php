<?php
/**
 * Plugin Name: Product Groups (Link + Affiliate)
 * Plugin URI: https://github.com/alireza-kh95/product-groups
 * Description: Create and display responsive groups of Digikala & Snapp Shop products using product and affiliate links with automatic API caching and GitHub auto-updates.
 * Version: 1.5.0
 * Author: Alireza Khosravani
 * Author URI: https://github.com/alireza-kh95
 * License: GPL-2.0-or-later
 * Text Domain: product-groups
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/alireza-kh95/product-groups
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Plugin Constants
define( 'PRODUCT_GROUPS_VERSION', '1.5.0' );
define( 'PRODUCT_GROUPS_FILE', __FILE__ );
define( 'PRODUCT_GROUPS_BASENAME', plugin_basename( __FILE__ ) );
define( 'PRODUCT_GROUPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRODUCT_GROUPS_URL', plugin_dir_url( __FILE__ ) );

// Load Classes
require_once PRODUCT_GROUPS_DIR . 'includes/class-utils.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-api.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-settings.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-post-type.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-meta-box.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-shortcode.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-updater.php';
require_once PRODUCT_GROUPS_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin.
 */
function pg_run_plugin() {
	return PG_Plugin::get_instance();
}
pg_run_plugin();
