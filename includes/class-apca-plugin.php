<?php
/**
 * Main plugin bootstrap class.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton that loads the plugin's dependencies and registers its hooks.
 */
class APCA_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var APCA_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin's singleton instance.
	 *
	 * @return APCA_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load the plugin's class files.
	 */
	private function load_dependencies() {
		require_once APCA_PATH . 'includes/class-apca-database.php';
		require_once APCA_PATH . 'includes/audits/class-apca-abstract-audit.php';
		require_once APCA_PATH . 'includes/audits/class-apca-content-audit.php';
		require_once APCA_PATH . 'includes/audits/class-apca-media-audit.php';
		require_once APCA_PATH . 'includes/audits/class-apca-heading-audit.php';
		require_once APCA_PATH . 'includes/audits/class-apca-link-audit.php';
		require_once APCA_PATH . 'includes/audits/class-apca-seo-audit.php';
		require_once APCA_PATH . 'includes/audits/class-apca-builder-audit.php';
		require_once APCA_PATH . 'includes/class-apca-scanner.php';
		require_once APCA_PATH . 'includes/class-apca-exporter.php';
		require_once APCA_PATH . 'includes/class-apca-admin.php';
	}

	/**
	 * Register activation, deactivation, and admin hooks.
	 */
	private function register_hooks() {
		register_activation_hook( APCA_FILE, array( 'APCA_Database', 'create_tables' ) );
		register_deactivation_hook( APCA_FILE, array( 'APCA_Database', 'maybe_drop_tables' ) );

		if ( is_admin() ) {
			new APCA_Admin();
			new APCA_Scanner();
		}
	}
}
