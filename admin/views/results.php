<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scan_id = isset( $_GET['scan_id'] ) ? (int) $_GET['scan_id'] : 0;

if ( ! $scan_id ) {
	$latest = SI_Database::get_latest_scan();
	if ( $latest ) {
		$scan_id = (int) $latest->id;
	}
}

$scan = $scan_id ? SI_Database::get_scan( $scan_id ) : null;

if ( ! $scan ) {
	echo '<div class="wrap"><div class="notice notice-warning"><p>' . esc_html__( 'No scan results found. Please run a scan first.', 'site-inspector' ) . '</p></div></div>';
	return;
}

$filter_category   = isset( $_GET['category'] ) ? sanitize_key( $_GET['category'] ) : '';
$filter_severity   = isset( $_GET['severity'] ) ? sanitize_key( $_GET['severity'] ) : '';
$filter_post_type  = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';
$filter_issue_type = isset( $_GET['issue_type'] ) ? sanitize_key( $_GET['issue_type'] ) : '';
$search            = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$current_page      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$per_page          = 25;

$result = SI_Database::get_issues( $scan_id, array(
	'category'   => $filter_category,
	'severity'   => $filter_severity,
	'post_type'  => $filter_post_type,
	'issue_type' => $filter_issue_type,
	'search'     => $search,
	'per_page'   => $per_page,
	'page'       => $current_page,
) );

$issues      = $result['items'];
$total       = $result['total'];
$total_pages = $result['pages'];

$score_data = SI_Database::get_score_data( $scan_id );
$summary    = SI_Database::get_summary( $scan_id );

$base_url = admin_url( 'admin.php?page=site-inspector-results&scan_id=' . $scan_id );
?>
<div class="wrap si-wrap">
	<div class="si-header">
		<h1 class="si-title">
			<?php esc_html_e( 'Scan Results', 'site-inspector' ); ?>
			<?php if ( 'completed' === $scan->status ) : ?>
				<span class="si-title-meta"><?php echo esc_html( human_time_diff( strtotime( $scan->completed_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'site-inspector' ) ); ?></span>
			<?php endif; ?>
		</h1>
		<div class="si-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=site-inspector' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( '← Dashboard', 'site-inspector' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( $base_url . '&action=export_csv', 'si_export_csv' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Export CSV', 'site-inspector' ); ?>
			</a>
		</div>
	</div>

	<?php if ( $score_data ) : ?>
	<div class="si-scores-row">
		<?php foreach ( array_merge( array( 'overall' => $score_data['overall'] ), $score_data['categories'] ) as $key => $score ) : ?>
		<div class="si-score-pill si-score-<?php echo esc_attr( SI_Admin::get_score_class( $score ) ); ?>">
			<span class="si-score-pill-num"><?php echo esc_html( $score ); ?></span>
			<span class="si-score-pill-label"><?php echo esc_html( 'overall' === $key ? __( 'Overall', 'site-inspector' ) : SI_Admin::get_category_label( $key ) ); ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="si-filters-bar">
		<form method="get" class="si-filters-form">
			<input type="hidden" name="page" value="site-inspector-results">
			<input type="hidden" name="scan_id" value="<?php echo esc_attr( $scan_id ); ?>">

			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search posts or messages...', 'site-inspector' ); ?>" class="si-search-input">

			<select name="category">
				<option value=""><?php esc_html_e( 'All Categories', 'site-inspector' ); ?></option>
				<?php foreach ( array( 'content', 'media', 'headings', 'links', 'seo', 'builder' ) as $cat ) : ?>
				<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>>
					<?php echo esc_html( SI_Admin::get_category_label( $cat ) ); ?>
				</option>
				<?php endforeach; ?>
			</select>

			<select name="severity">
				<option value=""><?php esc_html_e( 'All Severities', 'site-inspector' ); ?></option>
				<option value="error" <?php selected( $filter_severity, 'error' ); ?>><?php esc_html_e( 'Error', 'site-inspector' ); ?></option>
				<option value="warning" <?php selected( $filter_severity, 'warning' ); ?>><?php esc_html_e( 'Warning', 'site-inspector' ); ?></option>
				<option value="info" <?php selected( $filter_severity, 'info' ); ?>><?php esc_html_e( 'Info', 'site-inspector' ); ?></option>
			</select>

			<?php
			$post_types_in_results = array();
			if ( ! empty( $summary['by_post_type'] ) ) {
				foreach ( $summary['by_post_type'] as $pt ) {
					$post_types_in_results[ $pt->post_type ] = get_post_type_object( $pt->post_type );
				}
			}
			if ( ! empty( $post_types_in_results ) ) : ?>
			<select name="post_type">
				<option value=""><?php esc_html_e( 'All Post Types', 'site-inspector' ); ?></option>
				<?php foreach ( $post_types_in_results as $pt_slug => $pt_obj ) : ?>
				<option value="<?php echo esc_attr( $pt_slug ); ?>" <?php selected( $filter_post_type, $pt_slug ); ?>>
					<?php echo esc_html( $pt_obj ? $pt_obj->labels->singular_name : $pt_slug ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'site-inspector' ); ?></button>

			<?php if ( $filter_category || $filter_severity || $filter_post_type || $search ) : ?>
			<a href="<?php echo esc_url( $base_url ); ?>" class="button si-reset-filters"><?php esc_html_e( 'Reset', 'site-inspector' ); ?></a>
			<?php endif; ?>
		</form>

		<div class="si-results-count">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: number of issues shown, 2: total issues */
					__( 'Showing %1$s of %2$s issues', 'site-inspector' ),
					'<strong>' . esc_html( number_format_i18n( count( $issues ) ) ) . '</strong>',
					'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>'
				),
				array( 'strong' => array() )
			);
			?>
		</div>
	</div>

	<?php if ( empty( $issues ) ) : ?>
	<div class="si-no-results">
		<p><?php esc_html_e( 'No issues found with the current filters.', 'site-inspector' ); ?></p>
	</div>
	<?php else : ?>

	<table class="wp-list-table widefat fixed striped si-issues-table">
		<thead>
			<tr>
				<th class="si-col-severity"><?php esc_html_e( 'Severity', 'site-inspector' ); ?></th>
				<th class="si-col-post"><?php esc_html_e( 'Post', 'site-inspector' ); ?></th>
				<th class="si-col-type"><?php esc_html_e( 'Post Type', 'site-inspector' ); ?></th>
				<th class="si-col-category"><?php esc_html_e( 'Category', 'site-inspector' ); ?></th>
				<th class="si-col-issue"><?php esc_html_e( 'Issue', 'site-inspector' ); ?></th>
				<th class="si-col-actions"><?php esc_html_e( 'Actions', 'site-inspector' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $issues as $issue ) :
				$post_type_obj = get_post_type_object( $issue->post_type );
				$pt_label      = $post_type_obj ? $post_type_obj->labels->singular_name : $issue->post_type;
			?>
			<tr class="si-issue-row si-issue-<?php echo esc_attr( $issue->severity ); ?>">
				<td class="si-col-severity">
					<span class="si-severity-badge si-severity-<?php echo esc_attr( $issue->severity ); ?>">
						<?php echo esc_html( SI_Admin::get_severity_label( $issue->severity ) ); ?>
					</span>
				</td>
				<td class="si-col-post">
					<strong><?php echo esc_html( $issue->post_title ?: __( '(no title)', 'site-inspector' ) ); ?></strong>
				</td>
				<td class="si-col-type">
					<?php echo esc_html( $pt_label ); ?>
				</td>
				<td class="si-col-category">
					<a href="<?php echo esc_url( add_query_arg( 'category', $issue->category, $base_url ) ); ?>" class="si-category-link">
						<?php echo esc_html( SI_Admin::get_category_label( $issue->category ) ); ?>
					</a>
				</td>
				<td class="si-col-issue">
					<?php echo esc_html( $issue->message ); ?>
				</td>
				<td class="si-col-actions">
					<?php if ( ! empty( $issue->post_url ) ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $issue->post_id ) ); ?>" class="button button-small" target="_blank">
						<?php esc_html_e( 'Edit', 'site-inspector' ); ?>
					</a>
					<a href="<?php echo esc_url( $issue->post_url ); ?>" class="button button-small" target="_blank">
						<?php esc_html_e( 'View', 'site-inspector' ); ?>
					</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) :
		$pagination_args = array(
			'base'      => add_query_arg( 'paged', '%#%', $base_url ),
			'format'    => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total'     => $total_pages,
			'current'   => $current_page,
		);
		if ( $filter_category ) {
			$pagination_args['base'] = add_query_arg( 'category', $filter_category, $pagination_args['base'] );
		}
		if ( $filter_severity ) {
			$pagination_args['base'] = add_query_arg( 'severity', $filter_severity, $pagination_args['base'] );
		}
		?>
	<div class="si-pagination">
		<?php echo paginate_links( $pagination_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php endif; ?>

	<?php endif; ?>
</div>
