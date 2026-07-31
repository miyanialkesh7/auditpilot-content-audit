<?php
/**
 * Plugin Name: AuditPilot – Smart Content Audit
 * Plugin URI: https://wordpress.org/plugins/auditpilot-content-audit/
 * Description: Audit your WordPress content for SEO, accessibility, quality, and best practices.
 * Version: 1.0.2
 * Author: Alkesh Miyani
 * Author URI: https://miyanialkesh7.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auditpilot-content-audit
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.1
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APCA_VERSION', '1.0.2' );
define( 'APCA_FILE', __FILE__ );
define( 'APCA_PATH', plugin_dir_path( __FILE__ ) );
define( 'APCA_URL', plugin_dir_url( __FILE__ ) );
define( 'APCA_BASENAME', plugin_basename( __FILE__ ) );
define( 'APCA_MIN_WP', '6.8' );
define( 'APCA_MIN_PHP', '8.1' );

require_once APCA_PATH . 'includes/class-apca-plugin.php';

if ( ! function_exists( 'apca_plugin' ) ) {
	/**
	 * Get the plugin's singleton instance.
	 *
	 * @return APCA_Plugin
	 */
	function apca_plugin() {
		return APCA_Plugin::instance();
	}
}

apca_plugin();
