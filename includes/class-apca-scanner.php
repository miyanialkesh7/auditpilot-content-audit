<?php
/**
 * Scan orchestration: AJAX handlers that run audits in batches over posts.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the AJAX-driven scan lifecycle: starting a scan, processing
 * batches of posts through the enabled audits, and reporting status.
 */
class APCA_Scanner {

	/**
	 * Constructor. Registers AJAX action hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_apca_start_scan', array( $this, 'ajax_start_scan' ) );
		add_action( 'wp_ajax_apca_scan_batch', array( $this, 'ajax_scan_batch' ) );
		add_action( 'wp_ajax_apca_get_scan_status', array( $this, 'ajax_get_scan_status' ) );
	}

	/**
	 * AJAX handler: create a new scan and return the post IDs to process.
	 */
	public function ajax_start_scan() {
		check_ajax_referer( 'apca_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auditpilot-content-audit' ) ) );
		}

		$settings = $this->get_settings();

		$post_ids = $this->get_posts_to_scan( $settings );

		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No posts found to scan with current settings.', 'auditpilot-content-audit' ) ) );
		}

		$scan_id = APCA_Database::create_scan( $settings );

		APCA_Database::update_scan(
			$scan_id,
			array(
				'total_posts' => count( $post_ids ),
				'status'      => 'running',
			)
		);

		wp_send_json_success(
			array(
				'scan_id'  => $scan_id,
				'post_ids' => $post_ids,
				'total'    => count( $post_ids ),
			)
		);
	}

	/**
	 * AJAX handler: run enabled audits over a batch of posts and record issues.
	 */
	public function ajax_scan_batch() {
		check_ajax_referer( 'apca_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auditpilot-content-audit' ) ) );
		}

		$scan_id  = isset( $_POST['scan_id'] ) ? (int) $_POST['scan_id'] : 0;
		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', (array) $_POST['post_ids'] ) : array();

		if ( ! $scan_id || empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid scan parameters.', 'auditpilot-content-audit' ) ) );
		}

		$scan = APCA_Database::get_scan( $scan_id );
		if ( ! $scan || 'running' !== $scan->status ) {
			wp_send_json_error( array( 'message' => __( 'Scan not found or already completed.', 'auditpilot-content-audit' ) ) );
		}

		$decoded_settings = json_decode( $scan->settings, true );
		$settings         = $decoded_settings ? $decoded_settings : array();
		$issues_found     = 0;
		$scanned_count    = 0;

		$audits = $this->get_audit_instances( $settings );

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$post_issues = array();
			foreach ( $audits as $audit ) {
				$post_issues = array_merge( $post_issues, $audit->run( $post ) );
			}

			foreach ( $post_issues as $issue ) {
				APCA_Database::insert_issue( $scan_id, $post, $issue );
				++$issues_found;
			}

			++$scanned_count;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter increment on a custom table; no WordPress API equivalent.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}apca_scans SET scanned_posts = scanned_posts + %d, issues_found = issues_found + %d WHERE id = %d",
				$scanned_count,
				$issues_found,
				$scan_id
			)
		);

		wp_send_json_success(
			array(
				'scanned'      => $scanned_count,
				'issues_found' => $issues_found,
			)
		);
	}

	/**
	 * AJAX handler: return a scan's current status, optionally marking it complete.
	 */
	public function ajax_get_scan_status() {
		check_ajax_referer( 'apca_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$scan_id = isset( $_POST['scan_id'] ) ? (int) $_POST['scan_id'] : 0;

		if ( ! $scan_id ) {
			wp_send_json_error();
		}

		$scan = APCA_Database::get_scan( $scan_id );
		if ( ! $scan ) {
			wp_send_json_error();
		}

		$is_complete = isset( $_POST['complete'] ) && '1' === $_POST['complete'];

		if ( $is_complete && 'running' === $scan->status ) {
			APCA_Database::update_scan(
				$scan_id,
				array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
				)
			);
			$scan->status       = 'completed';
			$scan->completed_at = current_time( 'mysql' );
		}

		wp_send_json_success(
			array(
				'scan'        => $scan,
				'results_url' => admin_url( 'admin.php?page=auditpilot-content-audit-results&scan_id=' . $scan_id ),
			)
		);
	}

	/**
	 * Query the post IDs to scan based on the configured post types/statuses.
	 *
	 * @param array $settings Scan settings.
	 * @return int[] Post IDs to scan.
	 */
	private function get_posts_to_scan( $settings ) {
		$post_types = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : array( 'post', 'page' );
		$post_types = array_filter( $post_types );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		$post_statuses = isset( $settings['post_statuses'] ) ? (array) $settings['post_statuses'] : array( 'publish' );
		$post_statuses = array_filter( $post_statuses );
		if ( empty( $post_statuses ) ) {
			$post_statuses = array( 'publish' );
		}

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => $post_statuses,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Instantiate the audit classes enabled in settings.
	 *
	 * @param array $settings Scan settings.
	 * @return APCA_Abstract_Audit[] Enabled audit instances.
	 */
	private function get_audit_instances( $settings ) {
		$enabled = isset( $settings['enabled_audits'] ) ? (array) $settings['enabled_audits'] : array(
			'content',
			'media',
			'headings',
			'links',
			'seo',
			'builder',
		);

		$audits = array();

		if ( in_array( 'content', $enabled, true ) ) {
			$audits[] = new APCA_Content_Audit( $settings );
		}

		if ( in_array( 'media', $enabled, true ) ) {
			$audits[] = new APCA_Media_Audit( $settings );
		}

		if ( in_array( 'headings', $enabled, true ) ) {
			$audits[] = new APCA_Heading_Audit();
		}

		if ( in_array( 'links', $enabled, true ) ) {
			$audits[] = new APCA_Link_Audit( $settings );
		}

		if ( in_array( 'seo', $enabled, true ) ) {
			$audits[] = new APCA_SEO_Audit();
		}

		if ( in_array( 'builder', $enabled, true ) ) {
			$audits[] = new APCA_Builder_Audit();
		}

		return $audits;
	}

	/**
	 * Get the plugin's saved settings, merged with defaults.
	 *
	 * @return array Settings.
	 */
	private function get_settings() {
		$saved = get_option( 'apca_settings', array() );

		return wp_parse_args(
			$saved,
			array(
				'post_types'              => array( 'post', 'page' ),
				'post_statuses'           => array( 'publish' ),
				'short_content_threshold' => 300,
				'old_draft_days'          => 30,
				'large_image_kb'          => 500,
				'check_external_links'    => false,
				'enabled_audits'          => array( 'content', 'media', 'headings', 'links', 'seo', 'builder' ),
			)
		);
	}
}
