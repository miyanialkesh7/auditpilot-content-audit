<?php
/**
 * Admin UI: menu pages, asset enqueuing, settings, export, and view helpers.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's admin pages and handles admin-side requests
 * (settings save, CSV export) plus shared label/formatting helpers
 * used by the admin view templates.
 */
class APCA_Admin {

	/**
	 * Constructor. Registers admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_filter( 'plugin_action_links_' . APCA_BASENAME, array( $this, 'add_plugin_links' ) );
	}

	/**
	 * Register the plugin's top-level and submenu admin pages.
	 */
	public function add_menu_pages() {
		add_menu_page(
			__( 'AuditPilot', 'auditpilot-content-audit' ),
			__( 'AuditPilot', 'auditpilot-content-audit' ),
			'manage_options',
			'auditpilot-content-audit',
			array( $this, 'render_dashboard' ),
			'dashicons-search',
			80
		);

		add_submenu_page(
			'auditpilot-content-audit',
			__( 'Dashboard', 'auditpilot-content-audit' ),
			__( 'Dashboard', 'auditpilot-content-audit' ),
			'manage_options',
			'auditpilot-content-audit',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'auditpilot-content-audit',
			__( 'Scan Results', 'auditpilot-content-audit' ),
			__( 'Scan Results', 'auditpilot-content-audit' ),
			'manage_options',
			'auditpilot-content-audit-results',
			array( $this, 'render_results' )
		);

		add_submenu_page(
			'auditpilot-content-audit',
			__( 'Settings', 'auditpilot-content-audit' ),
			__( 'Settings', 'auditpilot-content-audit' ),
			'manage_options',
			'auditpilot-content-audit-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin CSS/JS on the plugin's own admin pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$apca_pages = array(
			'toplevel_page_auditpilot-content-audit',
			'auditpilot-content-audit_page_auditpilot-content-audit-results',
			'auditpilot-content-audit_page_auditpilot-content-audit-settings',
		);

		if ( ! in_array( $hook, $apca_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'apca-admin',
			APCA_URL . 'admin/css/admin.css',
			array(),
			APCA_VERSION
		);

		wp_enqueue_script(
			'apca-admin',
			APCA_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			APCA_VERSION,
			true
		);

		wp_localize_script(
			'apca-admin',
			'apcaData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'apca_scan_nonce' ),
				'batchSize' => 10,
				'i18n'      => array(
					'scanning'    => __( 'Scanning...', 'auditpilot-content-audit' ),
					'complete'    => __( 'Scan complete!', 'auditpilot-content-audit' ),
					'error'       => __( 'An error occurred during scanning.', 'auditpilot-content-audit' ),
					'starting'    => __( 'Preparing scan...', 'auditpilot-content-audit' ),
					/* translators: 1: current post number being scanned, 2: total posts to scan */
					'progress'    => __( 'Scanning post %1$d of %2$d...', 'auditpilot-content-audit' ),
					'redirecting' => __( 'Redirecting to results...', 'auditpilot-content-audit' ),
					'startScan'   => __( 'Start New Scan', 'auditpilot-content-audit' ),
				),
			)
		);
	}

	/**
	 * Render the dashboard admin page.
	 */
	public function render_dashboard() {
		require_once APCA_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Render the scan results admin page.
	 */
	public function render_results() {
		require_once APCA_PATH . 'admin/views/results.php';
	}

	/**
	 * Render the settings admin page.
	 */
	public function render_settings() {
		require_once APCA_PATH . 'admin/views/settings.php';
	}

	/**
	 * Handle the CSV export request on admin_init, if one was made.
	 */
	public function handle_export() {
		if (
			! isset( $_GET['page'], $_GET['action'], $_GET['scan_id'] ) ||
			'auditpilot-content-audit-results' !== $_GET['page'] ||
			'export_csv' !== $_GET['action']
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'auditpilot-content-audit' ) );
		}

		check_admin_referer( 'apca_export_csv' );

		$scan_id = (int) $_GET['scan_id'];
		APCA_Exporter::export_csv( $scan_id );
	}

	/**
	 * Save the plugin's settings from the settings page form submission.
	 */
	public function save_settings() {
		if (
			! isset( $_POST['apca_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['apca_settings_nonce'] ), 'apca_save_settings' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types     = isset( $_POST['post_types'] ) ? array_map( 'sanitize_key', (array) $_POST['post_types'] ) : array();
		$post_statuses  = isset( $_POST['post_statuses'] ) ? array_map( 'sanitize_key', (array) $_POST['post_statuses'] ) : array();
		$enabled_audits = isset( $_POST['enabled_audits'] ) ? array_map( 'sanitize_key', (array) $_POST['enabled_audits'] ) : array();

		$settings = array(
			'post_types'              => $post_types,
			'post_statuses'           => $post_statuses,
			'enabled_audits'          => $enabled_audits,
			'short_content_threshold' => isset( $_POST['short_content_threshold'] ) ? absint( $_POST['short_content_threshold'] ) : 300,
			'old_draft_days'          => isset( $_POST['old_draft_days'] ) ? absint( $_POST['old_draft_days'] ) : 30,
			'large_image_kb'          => isset( $_POST['large_image_kb'] ) ? absint( $_POST['large_image_kb'] ) : 500,
			'check_external_links'    => isset( $_POST['check_external_links'] ) ? (bool) $_POST['check_external_links'] : false,
			'delete_on_deactivation'  => isset( $_POST['delete_on_deactivation'] ) ? (bool) $_POST['delete_on_deactivation'] : false,
		);

		update_option( 'apca_settings', $settings );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'auditpilot-content-audit-settings',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Add Dashboard/Settings links to the plugin's row on the Plugins page.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[] Modified plugin action links.
	 */
	public function add_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=auditpilot-content-audit' ) ) . '">' . esc_html__( 'Dashboard', 'auditpilot-content-audit' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=auditpilot-content-audit-settings' ) ) . '">' . esc_html__( 'Settings', 'auditpilot-content-audit' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Get a human-readable label for an issue severity.
	 *
	 * @param string $severity Severity slug.
	 * @return string Display label.
	 */
	public static function get_severity_label( $severity ) {
		$labels = array(
			'error'   => __( 'Error', 'auditpilot-content-audit' ),
			'warning' => __( 'Warning', 'auditpilot-content-audit' ),
			'info'    => __( 'Info', 'auditpilot-content-audit' ),
		);
		return isset( $labels[ $severity ] ) ? $labels[ $severity ] : ucfirst( $severity );
	}

	/**
	 * Get a human-readable label for an issue category.
	 *
	 * @param string $category Category slug.
	 * @return string Display label.
	 */
	public static function get_category_label( $category ) {
		$labels = array(
			'content'  => __( 'Content', 'auditpilot-content-audit' ),
			'media'    => __( 'Media', 'auditpilot-content-audit' ),
			'headings' => __( 'Headings', 'auditpilot-content-audit' ),
			'links'    => __( 'Links', 'auditpilot-content-audit' ),
			'seo'      => __( 'SEO', 'auditpilot-content-audit' ),
			'builder'  => __( 'Builder', 'auditpilot-content-audit' ),
		);
		return isset( $labels[ $category ] ) ? $labels[ $category ] : ucfirst( $category );
	}

	/**
	 * Get a human-readable label for a scan status.
	 *
	 * @param string $status Status slug.
	 * @return string Display label.
	 */
	public static function get_scan_status_label( $status ) {
		$labels = array(
			'running'   => __( 'Running', 'auditpilot-content-audit' ),
			'completed' => __( 'Completed', 'auditpilot-content-audit' ),
			'pending'   => __( 'Pending', 'auditpilot-content-audit' ),
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
	}

	/**
	 * Get a CSS class name for a score, based on its value band.
	 *
	 * @param int $score Score value (0-100).
	 * @return string CSS class: 'good', 'average', or 'poor'.
	 */
	public static function get_score_class( $score ) {
		if ( $score >= 80 ) {
			return 'good';
		} elseif ( $score >= 60 ) {
			return 'average';
		}
		return 'poor';
	}
}
