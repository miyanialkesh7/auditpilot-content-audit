<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAWP_Scanner {

	public function __construct() {
		add_action( 'wp_ajax_cawp_start_scan', array( $this, 'ajax_start_scan' ) );
		add_action( 'wp_ajax_cawp_scan_batch', array( $this, 'ajax_scan_batch' ) );
		add_action( 'wp_ajax_cawp_get_scan_status', array( $this, 'ajax_get_scan_status' ) );
	}

	public function ajax_start_scan() {
		check_ajax_referer( 'cawp_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-content-audit' ) ) );
		}

		$settings = $this->get_settings();

		$post_ids = $this->get_posts_to_scan( $settings );

		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No posts found to scan with current settings.', 'wp-content-audit' ) ) );
		}

		$scan_id = CAWP_Database::create_scan( $settings );

		CAWP_Database::update_scan( $scan_id, array(
			'total_posts'  => count( $post_ids ),
			'status'       => 'running',
		) );

		wp_send_json_success( array(
			'scan_id'  => $scan_id,
			'post_ids' => $post_ids,
			'total'    => count( $post_ids ),
		) );
	}

	public function ajax_scan_batch() {
		check_ajax_referer( 'cawp_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-content-audit' ) ) );
		}

		$scan_id  = isset( $_POST['scan_id'] ) ? (int) $_POST['scan_id'] : 0;
		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', (array) $_POST['post_ids'] ) : array();

		if ( ! $scan_id || empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid scan parameters.', 'wp-content-audit' ) ) );
		}

		$scan = CAWP_Database::get_scan( $scan_id );
		if ( ! $scan || 'running' !== $scan->status ) {
			wp_send_json_error( array( 'message' => __( 'Scan not found or already completed.', 'wp-content-audit' ) ) );
		}

		$settings      = json_decode( $scan->settings, true ) ?: array();
		$issues_found  = 0;
		$scanned_count = 0;

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
				CAWP_Database::insert_issue( $scan_id, $post, $issue );
				$issues_found++;
			}

			$scanned_count++;
		}

		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}cawp_scans SET scanned_posts = scanned_posts + %d, issues_found = issues_found + %d WHERE id = %d",
			$scanned_count,
			$issues_found,
			$scan_id
		) );

		wp_send_json_success( array(
			'scanned'      => $scanned_count,
			'issues_found' => $issues_found,
		) );
	}

	public function ajax_get_scan_status() {
		check_ajax_referer( 'cawp_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$scan_id = isset( $_POST['scan_id'] ) ? (int) $_POST['scan_id'] : 0;

		if ( ! $scan_id ) {
			wp_send_json_error();
		}

		$scan = CAWP_Database::get_scan( $scan_id );
		if ( ! $scan ) {
			wp_send_json_error();
		}

		$is_complete = isset( $_POST['complete'] ) && '1' === $_POST['complete'];

		if ( $is_complete && 'running' === $scan->status ) {
			CAWP_Database::update_scan( $scan_id, array(
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
			) );
			$scan->status       = 'completed';
			$scan->completed_at = current_time( 'mysql' );
		}

		wp_send_json_success( array(
			'scan'    => $scan,
			'results_url' => admin_url( 'admin.php?page=wp-content-audit-results&scan_id=' . $scan_id ),
		) );
	}

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

	private function get_audit_instances( $settings ) {
		$enabled = isset( $settings['enabled_audits'] ) ? (array) $settings['enabled_audits'] : array(
			'content', 'media', 'headings', 'links', 'seo', 'builder',
		);

		$audits = array();

		if ( in_array( 'content', $enabled, true ) ) {
			$audits[] = new CAWP_Content_Audit( $settings );
		}

		if ( in_array( 'media', $enabled, true ) ) {
			$audits[] = new CAWP_Media_Audit( $settings );
		}

		if ( in_array( 'headings', $enabled, true ) ) {
			$audits[] = new CAWP_Heading_Audit();
		}

		if ( in_array( 'links', $enabled, true ) ) {
			$audits[] = new CAWP_Link_Audit( $settings );
		}

		if ( in_array( 'seo', $enabled, true ) ) {
			$audits[] = new CAWP_SEO_Audit();
		}

		if ( in_array( 'builder', $enabled, true ) ) {
			$audits[] = new CAWP_Builder_Audit();
		}

		return $audits;
	}

	private function get_settings() {
		$saved = get_option( 'cawp_settings', array() );

		return wp_parse_args( $saved, array(
			'post_types'             => array( 'post', 'page' ),
			'post_statuses'          => array( 'publish' ),
			'short_content_threshold' => 300,
			'old_draft_days'         => 30,
			'large_image_kb'         => 500,
			'check_external_links'   => false,
			'enabled_audits'         => array( 'content', 'media', 'headings', 'links', 'seo', 'builder' ),
		) );
	}
}
