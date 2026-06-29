<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$latest_scan  = CAWP_Database::get_latest_scan();
$score_data   = null;
$summary      = null;
$settings     = get_option( 'cawp_settings', array() );

if ( $latest_scan && 'completed' === $latest_scan->status ) {
	$score_data = CAWP_Database::get_score_data( $latest_scan->id );
	$summary    = CAWP_Database::get_summary( $latest_scan->id );
}

$category_icons = array(
	'content'  => '📄',
	'media'    => '🖼️',
	'headings' => '🔤',
	'links'    => '🔗',
	'seo'      => '🔍',
	'builder'  => '🧩',
);
?>
<div class="wrap cawp-wrap">
	<div class="cawp-header">
		<h1 class="cawp-title">
			<svg class="cawp-logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
			<?php esc_html_e( 'Content Audit', 'wp-content-audit' ); ?>
		</h1>
		<p class="cawp-subtitle"><?php esc_html_e( 'Audit, Inspect & Improve Every WordPress Content Type.', 'wp-content-audit' ); ?></p>
	</div>

	<div class="cawp-scan-controls">
		<div class="cawp-scan-card">
			<div class="cawp-scan-info">
				<?php if ( $latest_scan ) : ?>
					<p>
						<?php if ( 'completed' === $latest_scan->status ) : ?>
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: time ago, 2: number of posts scanned, 3: number of issues found */
									__( 'Last scan completed %1$s — %2$s posts scanned, %3$s issues found.', 'wp-content-audit' ),
									'<strong>' . esc_html( human_time_diff( strtotime( $latest_scan->completed_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'wp-content-audit' ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $latest_scan->scanned_posts ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $latest_scan->issues_found ) ) . '</strong>'
								),
								array( 'strong' => array() )
							);
							?>
						<?php elseif ( 'running' === $latest_scan->status ) : ?>
							<?php esc_html_e( 'A scan is currently in progress.', 'wp-content-audit' ); ?>
						<?php endif; ?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'No scans have been run yet. Start your first scan to audit your site content.', 'wp-content-audit' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="cawp-scan-actions">
				<button id="cawp-start-scan" class="button button-primary button-hero">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php esc_html_e( 'Start New Scan', 'wp-content-audit' ); ?>
				</button>
				<?php if ( $latest_scan && 'completed' === $latest_scan->status ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-content-audit-results&scan_id=' . $latest_scan->id ) ); ?>" class="button button-secondary button-hero">
						<?php esc_html_e( 'View Results', 'wp-content-audit' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div id="cawp-scan-progress" class="cawp-progress-container" style="display:none;">
			<div class="cawp-progress-header">
				<span id="cawp-progress-label"><?php esc_html_e( 'Preparing scan...', 'wp-content-audit' ); ?></span>
				<span id="cawp-progress-percent">0%</span>
			</div>
			<div class="cawp-progress-bar-wrap">
				<div class="cawp-progress-bar" id="cawp-progress-bar" style="width:0%"></div>
			</div>
			<div id="cawp-progress-detail" class="cawp-progress-detail"></div>
		</div>
	</div>

	<?php if ( $score_data && $summary ) : ?>
	<div class="cawp-results-overview">
		<h2><?php esc_html_e( 'Latest Scan Overview', 'wp-content-audit' ); ?></h2>

		<div class="cawp-scores-grid">
			<div class="cawp-score-card cawp-score-overall">
				<div class="cawp-score-circle cawp-score-<?php echo esc_attr( CAWP_Admin::get_score_class( $score_data['overall'] ) ); ?>">
					<span class="cawp-score-number"><?php echo esc_html( $score_data['overall'] ); ?></span>
					<span class="cawp-score-label"><?php esc_html_e( 'Overall', 'wp-content-audit' ); ?></span>
				</div>
			</div>

			<?php foreach ( $score_data['categories'] as $category => $score ) : ?>
			<div class="cawp-score-card">
				<div class="cawp-score-circle cawp-score-<?php echo esc_attr( CAWP_Admin::get_score_class( $score ) ); ?>">
					<span class="cawp-score-number"><?php echo esc_html( $score ); ?></span>
					<span class="cawp-score-label"><?php echo esc_html( CAWP_Admin::get_category_label( $category ) ); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="cawp-stats-grid">
			<?php
			$severity_counts = array( 'error' => 0, 'warning' => 0, 'info' => 0 );
			if ( ! empty( $summary['by_severity'] ) ) {
				foreach ( $summary['by_severity'] as $sev => $row ) {
					$severity_counts[ $sev ] = (int) $row->count;
				}
			}
			?>
			<div class="cawp-stat-card cawp-stat-error">
				<span class="cawp-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['error'] ) ); ?></span>
				<span class="cawp-stat-label"><?php esc_html_e( 'Errors', 'wp-content-audit' ); ?></span>
			</div>
			<div class="cawp-stat-card cawp-stat-warning">
				<span class="cawp-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['warning'] ) ); ?></span>
				<span class="cawp-stat-label"><?php esc_html_e( 'Warnings', 'wp-content-audit' ); ?></span>
			</div>
			<div class="cawp-stat-card cawp-stat-info">
				<span class="cawp-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['info'] ) ); ?></span>
				<span class="cawp-stat-label"><?php esc_html_e( 'Info', 'wp-content-audit' ); ?></span>
			</div>
			<div class="cawp-stat-card cawp-stat-posts">
				<span class="cawp-stat-number"><?php echo esc_html( number_format_i18n( $summary['posts_with_issues'] ) ); ?></span>
				<span class="cawp-stat-label"><?php esc_html_e( 'Posts with Issues', 'wp-content-audit' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $summary['by_category'] ) ) : ?>
		<div class="cawp-category-breakdown">
			<h3><?php esc_html_e( 'Issues by Category', 'wp-content-audit' ); ?></h3>
			<div class="cawp-category-grid">
				<?php
				$category_totals = array();
				foreach ( $summary['by_category'] as $row ) {
					if ( ! isset( $category_totals[ $row->category ] ) ) {
						$category_totals[ $row->category ] = array( 'error' => 0, 'warning' => 0, 'info' => 0 );
					}
					$category_totals[ $row->category ][ $row->severity ] = (int) $row->count;
				}

				foreach ( $category_totals as $category => $counts ) :
					$total = array_sum( $counts );
					$icon  = isset( $category_icons[ $category ] ) ? $category_icons[ $category ] : '📋';
				?>
				<div class="cawp-category-card">
					<div class="cawp-category-header">
						<span class="cawp-category-icon"><?php echo esc_html( $icon ); ?></span>
						<span class="cawp-category-name"><?php echo esc_html( CAWP_Admin::get_category_label( $category ) ); ?></span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-content-audit-results&scan_id=' . $latest_scan->id . '&category=' . $category ) ); ?>" class="cawp-category-count"><?php echo esc_html( $total ); ?></a>
					</div>
					<div class="cawp-category-badges">
						<?php if ( $counts['error'] > 0 ) : ?>
							<span class="cawp-badge cawp-badge-error"><?php echo esc_html( $counts['error'] ); ?> <?php esc_html_e( 'errors', 'wp-content-audit' ); ?></span>
						<?php endif; ?>
						<?php if ( $counts['warning'] > 0 ) : ?>
							<span class="cawp-badge cawp-badge-warning"><?php echo esc_html( $counts['warning'] ); ?> <?php esc_html_e( 'warnings', 'wp-content-audit' ); ?></span>
						<?php endif; ?>
						<?php if ( $counts['info'] > 0 ) : ?>
							<span class="cawp-badge cawp-badge-info"><?php echo esc_html( $counts['info'] ); ?> <?php esc_html_e( 'info', 'wp-content-audit' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="cawp-dashboard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-content-audit-results&scan_id=' . $latest_scan->id ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View All Issues', 'wp-content-audit' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-content-audit-results&action=export_csv&scan_id=' . $latest_scan->id ), 'cawp_export_csv' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Export CSV', 'wp-content-audit' ); ?>
			</a>
		</div>
	</div>
	<?php else : ?>
	<div class="cawp-empty-state">
		<div class="cawp-empty-icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		</div>
		<h3><?php esc_html_e( 'No scan results yet', 'wp-content-audit' ); ?></h3>
		<p><?php esc_html_e( 'Run your first scan to discover content issues across your WordPress site.', 'wp-content-audit' ); ?></p>
	</div>
	<?php endif; ?>

	<div class="cawp-recent-scans">
		<?php
		$scans = CAWP_Database::get_scans( 5 );
		if ( count( $scans ) > 1 ) :
		?>
		<h3><?php esc_html_e( 'Recent Scans', 'wp-content-audit' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'wp-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Posts Scanned', 'wp-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Issues Found', 'wp-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-content-audit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $scans as $scan ) : ?>
				<tr>
					<td><?php echo esc_html( $scan->started_at ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $scan->scanned_posts ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $scan->issues_found ) ); ?></td>
					<td>
						<span class="cawp-status-badge cawp-status-<?php echo esc_attr( $scan->status ); ?>">
							<?php echo esc_html( CAWP_Admin::get_scan_status_label( $scan->status ) ); ?>
						</span>
					</td>
					<td>
						<?php if ( 'completed' === $scan->status ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-content-audit-results&scan_id=' . $scan->id ) ); ?>">
								<?php esc_html_e( 'View Results', 'wp-content-audit' ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
</div>
