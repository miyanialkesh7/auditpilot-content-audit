<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SI_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_filter( 'plugin_action_links_' . SITE_INSPECTOR_BASENAME, array( $this, 'add_plugin_links' ) );
	}

	public function add_menu_pages() {
		add_menu_page(
			__( 'Site Inspector', 'site-inspector' ),
			__( 'Site Inspector', 'site-inspector' ),
			'manage_options',
			'site-inspector',
			array( $this, 'render_dashboard' ),
			'dashicons-search',
			80
		);

		add_submenu_page(
			'site-inspector',
			__( 'Dashboard', 'site-inspector' ),
			__( 'Dashboard', 'site-inspector' ),
			'manage_options',
			'site-inspector',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'site-inspector',
			__( 'Scan Results', 'site-inspector' ),
			__( 'Scan Results', 'site-inspector' ),
			'manage_options',
			'site-inspector-results',
			array( $this, 'render_results' )
		);

		add_submenu_page(
			'site-inspector',
			__( 'Settings', 'site-inspector' ),
			__( 'Settings', 'site-inspector' ),
			'manage_options',
			'site-inspector-settings',
			array( $this, 'render_settings' )
		);
	}

	public function enqueue_assets( $hook ) {
		$si_pages = array(
			'toplevel_page_site-inspector',
			'site-inspector_page_site-inspector-results',
			'site-inspector_page_site-inspector-settings',
		);

		if ( ! in_array( $hook, $si_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'site-inspector-admin',
			SITE_INSPECTOR_URL . 'admin/css/admin.css',
			array(),
			SITE_INSPECTOR_VERSION
		);

		wp_enqueue_script(
			'site-inspector-admin',
			SITE_INSPECTOR_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			SITE_INSPECTOR_VERSION,
			true
		);

		wp_localize_script( 'site-inspector-admin', 'siteInspector', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'si_scan_nonce' ),
			'batchSize' => 10,
			'i18n'     => array(
				'scanning'    => __( 'Scanning...', 'site-inspector' ),
				'complete'    => __( 'Scan complete!', 'site-inspector' ),
				'error'       => __( 'An error occurred during scanning.', 'site-inspector' ),
				'starting'    => __( 'Preparing scan...', 'site-inspector' ),
				'progress'    => __( 'Scanning post %1$d of %2$d...', 'site-inspector' ),
				'redirecting' => __( 'Redirecting to results...', 'site-inspector' ),
				'startScan'   => __( 'Start New Scan', 'site-inspector' ),
			),
		) );
	}

	public function render_dashboard() {
		require_once SITE_INSPECTOR_PATH . 'admin/views/dashboard.php';
	}

	public function render_results() {
		require_once SITE_INSPECTOR_PATH . 'admin/views/results.php';
	}

	public function render_settings() {
		require_once SITE_INSPECTOR_PATH . 'admin/views/settings.php';
	}

	public function handle_export() {
		if (
			! isset( $_GET['page'], $_GET['action'], $_GET['scan_id'] ) ||
			'site-inspector-results' !== $_GET['page'] ||
			'export_csv' !== $_GET['action']
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'site-inspector' ) );
		}

		check_admin_referer( 'si_export_csv' );

		$scan_id = (int) $_GET['scan_id'];
		SI_Exporter::export_csv( $scan_id );
	}

	public function save_settings() {
		if (
			! isset( $_POST['si_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['si_settings_nonce'] ), 'si_save_settings' )
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

		update_option( 'site_inspector_settings', $settings );

		wp_safe_redirect( add_query_arg( array(
			'page'    => 'site-inspector-settings',
			'updated' => '1',
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function add_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=site-inspector' ) ) . '">' . esc_html__( 'Dashboard', 'site-inspector' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=site-inspector-settings' ) ) . '">' . esc_html__( 'Settings', 'site-inspector' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	public static function get_severity_label( $severity ) {
		$labels = array(
			'error'   => __( 'Error', 'site-inspector' ),
			'warning' => __( 'Warning', 'site-inspector' ),
			'info'    => __( 'Info', 'site-inspector' ),
		);
		return isset( $labels[ $severity ] ) ? $labels[ $severity ] : ucfirst( $severity );
	}

	public static function get_category_label( $category ) {
		$labels = array(
			'content'  => __( 'Content', 'site-inspector' ),
			'media'    => __( 'Media', 'site-inspector' ),
			'headings' => __( 'Headings', 'site-inspector' ),
			'links'    => __( 'Links', 'site-inspector' ),
			'seo'      => __( 'SEO', 'site-inspector' ),
			'builder'  => __( 'Builder', 'site-inspector' ),
		);
		return isset( $labels[ $category ] ) ? $labels[ $category ] : ucfirst( $category );
	}

	public static function get_scan_status_label( $status ) {
		$labels = array(
			'running'   => __( 'Running', 'site-inspector' ),
			'completed' => __( 'Completed', 'site-inspector' ),
			'pending'   => __( 'Pending', 'site-inspector' ),
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
