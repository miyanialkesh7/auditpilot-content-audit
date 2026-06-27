<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SI_Exporter {

	public static function export_csv( $scan_id ) {
		$scan = SI_Database::get_scan( $scan_id );
		if ( ! $scan ) {
			wp_die( esc_html__( 'Scan not found.', 'site-inspector' ) );
		}

		$filename = 'site-inspector-scan-' . $scan_id . '-' . gmdate( 'Y-m-d' ) . '.csv';

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
			__( 'Post ID', 'site-inspector' ),
			__( 'Post Title', 'site-inspector' ),
			__( 'Post Type', 'site-inspector' ),
			__( 'Post URL', 'site-inspector' ),
			__( 'Category', 'site-inspector' ),
			__( 'Issue Type', 'site-inspector' ),
			__( 'Severity', 'site-inspector' ),
			__( 'Message', 'site-inspector' ),
			__( 'Found At', 'site-inspector' ),
		) );

		$issues = SI_Database::get_all_issues_for_export( $scan_id );

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
