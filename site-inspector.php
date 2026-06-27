<?php
/**
 * Plugin Name: Site Inspector
 * Plugin URI: https://wordpress.org/plugins/site-inspector/
 * Description: Audit, Inspect & Improve Every WordPress Content Type.
 * Version: 1.0.0
 * Author: Alkesh Miyani
 * Author URI: https://miyanialkesh7.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: site-inspector
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SITE_INSPECTOR_VERSION', '1.0.0' );
define( 'SITE_INSPECTOR_FILE', __FILE__ );
define( 'SITE_INSPECTOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'SITE_INSPECTOR_URL', plugin_dir_url( __FILE__ ) );
define( 'SITE_INSPECTOR_BASENAME', plugin_basename( __FILE__ ) );
define( 'SITE_INSPECTOR_MIN_WP', '6.8' );
define( 'SITE_INSPECTOR_MIN_PHP', '7.4' );

require_once SITE_INSPECTOR_PATH . 'includes/class-site-inspector.php';

if ( ! function_exists( 'site_inspector' ) ) {
	function site_inspector() {
		return Site_Inspector::instance();
	}
}

site_inspector();
