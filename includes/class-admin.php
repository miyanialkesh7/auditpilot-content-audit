<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAWP_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_filter( 'plugin_action_links_' . CAWP_BASENAME, array( $this, 'add_plugin_links' ) );
	}

	public function add_menu_pages() {
		add_menu_page(
			__( 'Content Audit', 'wp-content-audit' ),
			__( 'Content Audit', 'wp-content-audit' ),
			'manage_options',
			'wp-content-audit',
			array( $this, 'render_dashboard' ),
			'dashicons-search',
			80
		);

		add_submenu_page(
			'wp-content-audit',
			__( 'Dashboard', 'wp-content-audit' ),
			__( 'Dashboard', 'wp-content-audit' ),
			'manage_options',
			'wp-content-audit',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'wp-content-audit',
			__( 'Scan Results', 'wp-content-audit' ),
			__( 'Scan Results', 'wp-content-audit' ),
			'manage_options',
			'wp-content-audit-results',
			array( $this, 'render_results' )
		);

		add_submenu_page(
			'wp-content-audit',
			__( 'Settings', 'wp-content-audit' ),
			__( 'Settings', 'wp-content-audit' ),
			'manage_options',
			'wp-content-audit-settings',
			array( $this, 'render_settings' )
		);
	}

	public function enqueue_assets( $hook ) {
		$cawp_pages = array(
			'toplevel_page_wp-content-audit',
			'wp-content-audit_page_wp-content-audit-results',
			'wp-content-audit_page_wp-content-audit-settings',
		);

		if ( ! in_array( $hook, $cawp_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'cawp-admin',
			CAWP_URL . 'admin/css/admin.css',
			array(),
			CAWP_VERSION
		);

		wp_enqueue_script(
			'cawp-admin',
			CAWP_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			CAWP_VERSION,
			true
		);

		wp_localize_script( 'cawp-admin', 'cawpData', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cawp_scan_nonce' ),
			'batchSize' => 10,
			'i18n'     => array(
				'scanning'    => __( 'Scanning...', 'wp-content-audit' ),
				'complete'    => __( 'Scan complete!', 'wp-content-audit' ),
				'error'       => __( 'An error occurred during scanning.', 'wp-content-audit' ),
				'starting'    => __( 'Preparing scan...', 'wp-content-audit' ),
				'progress'    => __( 'Scanning post %1$d of %2$d...', 'wp-content-audit' ),
				'redirecting' => __( 'Redirecting to results...', 'wp-content-audit' ),
				'startScan'   => __( 'Start New Scan', 'wp-content-audit' ),
			),
		) );
	}

	public function render_dashboard() {
		require_once CAWP_PATH . 'admin/views/dashboard.php';
	}

	public function render_results() {
		require_once CAWP_PATH . 'admin/views/results.php';
	}

	public function render_settings() {
		require_once CAWP_PATH . 'admin/views/settings.php';
	}

	public function handle_export() {
		if (
			! isset( $_GET['page'], $_GET['action'], $_GET['scan_id'] ) ||
			'wp-content-audit-results' !== $_GET['page'] ||
			'export_csv' !== $_GET['action']
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-content-audit' ) );
		}

		check_admin_referer( 'cawp_export_csv' );

		$scan_id = (int) $_GET['scan_id'];
		CAWP_Exporter::export_csv( $scan_id );
	}

	public function save_settings() {
		if (
			! isset( $_POST['cawp_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['cawp_settings_nonce'] ), 'cawp_save_settings' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types = isset( $_POST['post_types'] ) ? array_map( 'sanitize_key', (array) $_POST['post_types'] ) : array();
		$post_statuses = isset( $_POST['post_statuses'] ) ? array_map( 'sanitize_key', (array) $_POST['post_statuses'] ) : array();
		$enabled_audits = isset( $_POST['enabled_audits'] ) ? array_map( 'sanitize_key', (array) $_POST['enabled_audits'] ) : array();

		$settings = array(
			'post_types'              => $post_types,
			'post_statuses'           => $post_statuses,
			'enabled_audits'          => $enabled_audits,
			'short_content_threshold' => isset( $_POST['short_content_threshold'] ) ? absint( $_POST['short_content_threshold'] ) : 300,
			'old_draft_days'          => isset( $_POST['old_draft_days'] ) ? absint( $_POST['old_draft_days'] ) : 30,
			'large_image_kb'          => isset( $_POST['large_image_kb'] ) ? absint( $_POST['large_image_kb'] ) : 500,
			'check_external_links'    => isset( $_POST['check_external_links'] ) ? (bool) $_POST['check_external_links'] : false,
		);

		update_option( 'cawp_settings', $settings );

		wp_safe_redirect( add_query_arg( array(
			'page'    => 'wp-content-audit-settings',
			'updated' => '1',
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function add_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wp-content-audit' ) ) . '">' . esc_html__( 'Dashboard', 'wp-content-audit' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=wp-content-audit-settings' ) ) . '">' . esc_html__( 'Settings', 'wp-content-audit' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	public static function get_severity_label( $severity ) {
		$labels = array(
			'error'   => __( 'Error', 'wp-content-audit' ),
			'warning' => __( 'Warning', 'wp-content-audit' ),
			'info'    => __( 'Info', 'wp-content-audit' ),
		);
		return isset( $labels[ $severity ] ) ? $labels[ $severity ] : ucfirst( $severity );
	}

	public static function get_category_label( $category ) {
		$labels = array(
			'content'  => __( 'Content', 'wp-content-audit' ),
			'media'    => __( 'Media', 'wp-content-audit' ),
			'headings' => __( 'Headings', 'wp-content-audit' ),
			'links'    => __( 'Links', 'wp-content-audit' ),
			'seo'      => __( 'SEO', 'wp-content-audit' ),
			'builder'  => __( 'Builder', 'wp-content-audit' ),
		);
		return isset( $labels[ $category ] ) ? $labels[ $category ] : ucfirst( $category );
	}

	public static function get_scan_status_label( $status ) {
		$labels = array(
			'running'   => __( 'Running', 'wp-content-audit' ),
			'completed' => __( 'Completed', 'wp-content-audit' ),
			'pending'   => __( 'Pending', 'wp-content-audit' ),
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
	}

	public static function get_score_class( $score ) {
		if ( $score >= 80 ) {
			return 'good';
		} elseif ( $score >= 60 ) {
			return 'average';
		}
		return 'poor';
	}
}
