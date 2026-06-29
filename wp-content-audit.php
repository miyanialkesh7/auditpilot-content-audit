<?php
/**
 * Plugin Name: Content Audit for WordPress
 * Plugin URI: https://wordpress.org/plugins/wp-content-audit/
 * Description: Audit your WordPress content for SEO, accessibility, quality, and best practices.
 * Version: 1.0.0
 * Author: Alkesh Miyani
 * Author URI: https://miyanialkesh7.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-content-audit
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAWP_VERSION', '1.0.0' );
define( 'CAWP_FILE', __FILE__ );
define( 'CAWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CAWP_URL', plugin_dir_url( __FILE__ ) );
define( 'CAWP_BASENAME', plugin_basename( __FILE__ ) );
define( 'CAWP_MIN_WP', '6.8' );
define( 'CAWP_MIN_PHP', '7.4' );

require_once CAWP_PATH . 'includes/class-cawp-plugin.php';

if ( ! function_exists( 'cawp_plugin' ) ) {
	function cawp_plugin() {
		return CAWP_Plugin::instance();
	}
}

cawp_plugin();
