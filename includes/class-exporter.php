<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAWP_Exporter {

	public static function export_csv( $scan_id ) {
		$scan = CAWP_Database::get_scan( $scan_id );
		if ( ! $scan ) {
			wp_die( esc_html__( 'Scan not found.', 'wp-content-audit' ) );
		}

		$filename = 'wp-content-audit-scan-' . $scan_id . '-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen,WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Writing to php://output stream, not the filesystem.
		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM so Excel opens the file correctly.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fprintf
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		fputcsv( $output, array(
			__( 'Post ID', 'wp-content-audit' ),
			__( 'Post Title', 'wp-content-audit' ),
			__( 'Post Type', 'wp-content-audit' ),
			__( 'Post URL', 'wp-content-audit' ),
			__( 'Category', 'wp-content-audit' ),
			__( 'Issue Type', 'wp-content-audit' ),
			__( 'Severity', 'wp-content-audit' ),
			__( 'Message', 'wp-content-audit' ),
			__( 'Found At', 'wp-content-audit' ),
		) );

		$issues = CAWP_Database::get_all_issues_for_export( $scan_id );

		foreach ( $issues as $issue ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
			fputcsv( $output, array(
				$issue->post_id,
				$issue->post_title,
				$issue->post_type,
				$issue->post_url,
				$issue->category,
				$issue->issue_type,
				$issue->severity,
				$issue->message,
				$issue->created_at,
			) );
		}

		fclose( $output );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen,WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
