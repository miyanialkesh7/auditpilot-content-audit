<?php
/**
 * Database layer: schema management and CRUD for scans and issues.
 *
 * @package AuditPilot_Content_Audit
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- All methods in this class operate on custom plugin tables. There is no WordPress API equivalent; direct queries are required. Caching is intentionally omitted for write paths and scan results, which change frequently during active scans.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the plugin's custom database tables and all reads/writes
 * to scan and issue records.
 */
class APCA_Database {

	const DB_VERSION = '1.0';

	/**
	 * Drop the plugin's custom tables and delete the stored schema version.
	 */
	public static function drop_tables() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'apca_issues' ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'apca_scans' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
		delete_option( 'apca_db_version' );
	}

	/**
	 * Drop the plugin's tables on deactivation, if the setting is enabled.
	 */
	public static function maybe_drop_tables() {
		$settings = get_option( 'apca_settings', array() );
		if ( ! empty( $settings['delete_on_deactivation'] ) ) {
			self::drop_tables();
		}
	}

	/**
	 * Create (or update) the plugin's custom tables via dbDelta().
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$scans_table  = $wpdb->prefix . 'apca_scans';
		$issues_table = $wpdb->prefix . 'apca_issues';

		$sql_scans = "CREATE TABLE $scans_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			status varchar(20) NOT NULL DEFAULT 'pending',
			total_posts int(11) NOT NULL DEFAULT 0,
			scanned_posts int(11) NOT NULL DEFAULT 0,
			issues_found int(11) NOT NULL DEFAULT 0,
			settings longtext DEFAULT NULL,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

		$sql_issues = "CREATE TABLE $issues_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			scan_id bigint(20) UNSIGNED NOT NULL,
			post_id bigint(20) UNSIGNED NOT NULL,
			post_type varchar(100) NOT NULL DEFAULT '',
			post_title text NOT NULL DEFAULT '',
			post_url varchar(2048) NOT NULL DEFAULT '',
			category varchar(50) NOT NULL DEFAULT '',
			issue_type varchar(100) NOT NULL DEFAULT '',
			severity varchar(20) NOT NULL DEFAULT 'info',
			message text NOT NULL DEFAULT '',
			details longtext DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY scan_id (scan_id),
			KEY post_id (post_id),
			KEY severity (severity),
			KEY category (category),
			KEY issue_type (issue_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_scans );
		dbDelta( $sql_issues );

		update_option( 'apca_db_version', self::DB_VERSION );
	}

	/**
	 * Insert a new scan row.
	 *
	 * @param array $settings Scan settings, stored as JSON.
	 * @return int Newly inserted scan ID.
	 */
	public static function create_scan( $settings = array() ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'apca_scans',
			array(
				'status'     => 'running',
				'settings'   => wp_json_encode( $settings ),
				'started_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Update a scan row.
	 *
	 * @param int   $scan_id Scan ID to update.
	 * @param array $data    Column values to update.
	 */
	public static function update_scan( $scan_id, $data ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'apca_scans',
			$data,
			array( 'id' => $scan_id ),
			null,
			array( '%d' )
		);
	}

	/**
	 * Get a single scan by ID.
	 *
	 * @param int $scan_id Scan ID.
	 * @return object|null Scan row, or null if not found.
	 */
	public static function get_scan( $scan_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $wpdb->prefix . 'apca_scans', $scan_id )
		);
	}

	/**
	 * Get the most recently created scan.
	 *
	 * @return object|null Scan row, or null if none exist.
	 */
	public static function get_latest_scan() {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT 1', $wpdb->prefix . 'apca_scans' )
		);
	}

	/**
	 * Get the most recent scans.
	 *
	 * @param int $limit Maximum number of scans to return.
	 * @return object[] Scan rows.
	 */
	public static function get_scans( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apca_scans ORDER BY id DESC LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Insert a single issue row for a post found during a scan.
	 *
	 * @param int     $scan_id Scan ID the issue belongs to.
	 * @param WP_Post $post    Post the issue was found on.
	 * @param array   $issue   Issue data (category, type, severity, message, details).
	 */
	public static function insert_issue( $scan_id, $post, $issue ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'apca_issues',
			array(
				'scan_id'    => $scan_id,
				'post_id'    => $post->ID,
				'post_type'  => $post->post_type,
				'post_title' => $post->post_title,
				'post_url'   => get_permalink( $post->ID ),
				'category'   => $issue['category'],
				'issue_type' => $issue['type'],
				'severity'   => $issue['severity'],
				'message'    => $issue['message'],
				'details'    => isset( $issue['details'] ) ? wp_json_encode( $issue['details'] ) : null,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Delete all issues belonging to a scan.
	 *
	 * @param int $scan_id Scan ID whose issues should be deleted.
	 */
	public static function delete_scan_issues( $scan_id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'apca_issues',
			array( 'scan_id' => $scan_id ),
			array( '%d' )
		);
	}

	/**
	 * Get a filtered, paginated list of issues for a scan.
	 *
	 * @param int   $scan_id Scan ID to fetch issues for.
	 * @param array $args    Filter/sort/pagination args (category, severity,
	 *                       post_type, issue_type, search, orderby, order,
	 *                       per_page, page).
	 * @return array Array with 'items' (object[]), 'total' (int), and 'pages' (int).
	 */
	public static function get_issues( $scan_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'category'   => '',
			'severity'   => '',
			'post_type'  => '',
			'issue_type' => '',
			'search'     => '',
			'orderby'    => 'post_title',
			'order'      => 'ASC',
			'per_page'   => 50,
			'page'       => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = array( 'post_title', 'post_type', 'severity', 'category', 'issue_type', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'post_title';
		$order           = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$per_page = (int) $args['per_page'];

		$table        = $wpdb->prefix . 'apca_issues';
		$where_sql    = 'scan_id = %d';
		$where_params = array( $scan_id );

		if ( ! empty( $args['category'] ) ) {
			$where_sql     .= ' AND category = %s';
			$where_params[] = $args['category'];
		}

		if ( ! empty( $args['severity'] ) ) {
			$where_sql     .= ' AND severity = %s';
			$where_params[] = $args['severity'];
		}

		if ( ! empty( $args['post_type'] ) ) {
			$where_sql     .= ' AND post_type = %s';
			$where_params[] = $args['post_type'];
		}

		if ( ! empty( $args['issue_type'] ) ) {
			$where_sql     .= ' AND issue_type = %s';
			$where_params[] = $args['issue_type'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like           = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_sql     .= ' AND (post_title LIKE %s OR message LIKE %s)';
			$where_params[] = $like;
			$where_params[] = $like;
		}

		// $where_sql is composed entirely of hardcoded string fragments + wpdb placeholders — no user input is interpolated directly.
		// $order is validated against an allowlist above. The sniffs below are false positives for this pattern.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE {$where_sql} ORDER BY %i {$order} LIMIT %d OFFSET %d",
				array_merge( array( $table ), $where_params, array( $orderby, $per_page, $offset ) )
			)
		);

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE {$where_sql}",
				array_merge( array( $table ), $where_params )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'items' => $results,
			'total' => $total,
			'pages' => $per_page > 0 ? ceil( $total / $per_page ) : 1,
		);
	}

	/**
	 * Get summary counts of issues for a scan, grouped by severity,
	 * category, and post type.
	 *
	 * @param int $scan_id Scan ID.
	 * @return array Array with 'by_severity', 'by_category', 'by_post_type',
	 *               and 'posts_with_issues' keys.
	 */
	public static function get_summary( $scan_id ) {
		global $wpdb;

		$by_severity = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT severity, COUNT(*) as count FROM {$wpdb->prefix}apca_issues WHERE scan_id = %d GROUP BY severity",
				$scan_id
			),
			OBJECT_K
		);

		$by_category = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, severity, COUNT(*) as count FROM {$wpdb->prefix}apca_issues WHERE scan_id = %d GROUP BY category, severity",
				$scan_id
			)
		);

		$by_post_type = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_type, COUNT(DISTINCT post_id) as post_count, COUNT(*) as issue_count FROM {$wpdb->prefix}apca_issues WHERE scan_id = %d GROUP BY post_type",
				$scan_id
			)
		);

		$posts_with_issues = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}apca_issues WHERE scan_id = %d",
				$scan_id
			)
		);

		return array(
			'by_severity'       => $by_severity,
			'by_category'       => $by_category,
			'by_post_type'      => $by_post_type,
			'posts_with_issues' => $posts_with_issues,
		);
	}

	/**
	 * Calculate per-category and overall content quality scores for a scan.
	 *
	 * @param int $scan_id Scan ID.
	 * @return array|null Array with 'overall' (int) and 'categories' (array
	 *                     of int scores), or null if the scan has no scanned posts.
	 */
	public static function get_score_data( $scan_id ) {
		global $wpdb;

		$scan = self::get_scan( $scan_id );
		if ( ! $scan ) {
			return null;
		}

		$total_posts = (int) $scan->scanned_posts;
		if ( 0 === $total_posts ) {
			return null;
		}

		$weights = array(
			'error'   => 10,
			'warning' => 5,
			'info'    => 2,
		);

		$categories = array( 'content', 'media', 'headings', 'links', 'seo', 'builder' );
		$scores     = array();

		$category_placeholders = implode( ', ', array_fill( 0, count( $categories ), '%s' ) );

		// $category_placeholders is a fixed '%s, %s, ...' string with no user input; all values are still bound via prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, severity, COUNT(*) as cnt FROM {$wpdb->prefix}apca_issues WHERE scan_id = %d AND category IN ( {$category_placeholders} ) GROUP BY category, severity",
				array_merge( array( $scan_id ), $categories )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$deductions_by_category = array_fill_keys( $categories, 0 );
		foreach ( $rows as $row ) {
			$weight = isset( $weights[ $row->severity ] ) ? $weights[ $row->severity ] : 1;
			$deductions_by_category[ $row->category ] += (int) $row->cnt * $weight;
		}

		$max_deductions = $total_posts * 20;
		foreach ( $categories as $category ) {
			$raw_score           = max( 0, 100 - ( $max_deductions > 0 ? ( $deductions_by_category[ $category ] / $max_deductions ) * 100 : 0 ) );
			$scores[ $category ] = (int) round( $raw_score );
		}

		$overall = (int) round( array_sum( $scores ) / count( $scores ) );

		return array(
			'overall'    => $overall,
			'categories' => $scores,
		);
	}

	/**
	 * Get all issues for a scan, ordered for CSV export.
	 *
	 * @param int $scan_id Scan ID.
	 * @return object[] Issue rows.
	 */
	public static function get_all_issues_for_export( $scan_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apca_issues WHERE scan_id = %d ORDER BY post_title ASC, category ASC",
				$scan_id
			)
		);
	}
}
