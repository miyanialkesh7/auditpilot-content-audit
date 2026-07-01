<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APCA_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	private function load_dependencies() {
		require_once APCA_PATH . 'includes/class-database.php';
		require_once APCA_PATH . 'includes/audits/abstract-audit.php';
		require_once APCA_PATH . 'includes/audits/class-content-audit.php';
		require_once APCA_PATH . 'includes/audits/class-media-audit.php';
		require_once APCA_PATH . 'includes/audits/class-heading-audit.php';
		require_once APCA_PATH . 'includes/audits/class-link-audit.php';
		require_once APCA_PATH . 'includes/audits/class-seo-audit.php';
		require_once APCA_PATH . 'includes/audits/class-builder-audit.php';
		require_once APCA_PATH . 'includes/class-scanner.php';
		require_once APCA_PATH . 'includes/class-exporter.php';
		require_once APCA_PATH . 'includes/class-admin.php';
	}

	private function register_hooks() {
		register_activation_hook( APCA_FILE, array( 'APCA_Database', 'create_tables' ) );
		register_deactivation_hook( APCA_FILE, array( 'APCA_Database', 'maybe_drop_tables' ) );

		if ( is_admin() ) {
			new APCA_Admin();
			new APCA_Scanner();
		}
	}

}
