<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$latest_scan  = SI_Database::get_latest_scan();
$score_data   = null;
$summary      = null;
$settings     = get_option( 'site_inspector_settings', array() );

if ( $latest_scan && 'completed' === $latest_scan->status ) {
	$score_data = SI_Database::get_score_data( $latest_scan->id );
	$summary    = SI_Database::get_summary( $latest_scan->id );
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
<div class="wrap si-wrap">
	<div class="si-header">
		<h1 class="si-title">
			<svg class="si-logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
			<?php esc_html_e( 'Site Inspector', 'site-inspector' ); ?>
		</h1>
		<p class="si-subtitle"><?php esc_html_e( 'Audit, Inspect & Improve Every WordPress Content Type.', 'site-inspector' ); ?></p>
	</div>

	<div class="si-scan-controls">
		<div class="si-scan-card">
			<div class="si-scan-info">
				<?php if ( $latest_scan ) : ?>
					<p>
						<?php if ( 'completed' === $latest_scan->status ) : ?>
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: time ago, 2: number of posts scanned, 3: number of issues found */
									__( 'Last scan completed %1$s — %2$s posts scanned, %3$s issues found.', 'site-inspector' ),
									'<strong>' . esc_html( human_time_diff( strtotime( $latest_scan->completed_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'site-inspector' ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $latest_scan->scanned_posts ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $latest_scan->issues_found ) ) . '</strong>'
								),
								array( 'strong' => array() )
							);
							?>
						<?php elseif ( 'running' === $latest_scan->status ) : ?>
							<?php esc_html_e( 'A scan is currently in progress.', 'site-inspector' ); ?>
						<?php endif; ?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'No scans have been run yet. Start your first scan to audit your site content.', 'site-inspector' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="si-scan-actions">
				<button id="si-start-scan" class="button button-primary button-hero">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php esc_html_e( 'Start New Scan', 'site-inspector' ); ?>
				</button>
				<?php if ( $latest_scan && 'completed' === $latest_scan->status ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=site-inspector-results&scan_id=' . $latest_scan->id ) ); ?>" class="button button-secondary button-hero">
						<?php esc_html_e( 'View Results', 'site-inspector' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div id="si-scan-progress" class="si-progress-container" style="display:none;">
			<div class="si-progress-header">
				<span id="si-progress-label"><?php esc_html_e( 'Preparing scan...', 'site-inspector' ); ?></span>
				<span id="si-progress-percent">0%</span>
			</div>
			<div class="si-progress-bar-wrap">
				<div class="si-progress-bar" id="si-progress-bar" style="width:0%"></div>
			</div>
			<div id="si-progress-detail" class="si-progress-detail"></div>
		</div>
	</div>

	<?php if ( $score_data && $summary ) : ?>
	<div class="si-results-overview">
		<h2><?php esc_html_e( 'Latest Scan Overview', 'site-inspector' ); ?></h2>

		<div class="si-scores-grid">
			<div class="si-score-card si-score-overall">
				<div class="si-score-circle si-score-<?php echo esc_attr( SI_Admin::get_score_class( $score_data['overall'] ) ); ?>">
					<span class="si-score-number"><?php echo esc_html( $score_data['overall'] ); ?></span>
					<span class="si-score-label"><?php esc_html_e( 'Overall', 'site-inspector' ); ?></span>
				</div>
			</div>

			<?php foreach ( $score_data['categories'] as $category => $score ) : ?>
			<div class="si-score-card">
				<div class="si-score-circle si-score-<?php echo esc_attr( SI_Admin::get_score_class( $score ) ); ?>">
					<span class="si-score-number"><?php echo esc_html( $score ); ?></span>
					<span class="si-score-label"><?php echo esc_html( SI_Admin::get_category_label( $category ) ); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="si-stats-grid">
			<?php
			$severity_counts = array( 'error' => 0, 'warning' => 0, 'info' => 0 );
			if ( ! empty( $summary['by_severity'] ) ) {
				foreach ( $summary['by_severity'] as $sev => $row ) {
					$severity_counts[ $sev ] = (int) $row->count;
				}
			}
			?>
			<div class="si-stat-card si-stat-error">
				<span class="si-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['error'] ) ); ?></span>
				<span class="si-stat-label"><?php esc_html_e( 'Errors', 'site-inspector' ); ?></span>
			</div>
			<div class="si-stat-card si-stat-warning">
				<span class="si-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['warning'] ) ); ?></span>
				<span class="si-stat-label"><?php esc_html_e( 'Warnings', 'site-inspector' ); ?></span>
			</div>
			<div class="si-stat-card si-stat-info">
				<span class="si-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['info'] ) ); ?></span>
				<span class="si-stat-label"><?php esc_html_e( 'Info', 'site-inspector' ); ?></span>
			</div>
			<div class="si-stat-card si-stat-posts">
				<span class="si-stat-number"><?php echo esc_html( number_format_i18n( $summary['posts_with_issues'] ) ); ?></span>
				<span class="si-stat-label"><?php esc_html_e( 'Posts with Issues', 'site-inspector' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $summary['by_category'] ) ) : ?>
		<div class="si-category-breakdown">
			<h3><?php esc_html_e( 'Issues by Category', 'site-inspector' ); ?></h3>
			<div class="si-category-grid">
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
				<div class="si-category-card">
					<div class="si-category-header">
						<span class="si-category-icon"><?php echo esc_html( $icon ); ?></span>
						<span class="si-category-name"><?php echo esc_html( SI_Admin::get_category_label( $category ) ); ?></span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=site-inspector-results&scan_id=' . $latest_scan->id . '&category=' . $category ) ); ?>" class="si-category-count"><?php echo esc_html( $total ); ?></a>
					</div>
					<div class="si-category-badges">
						<?php if ( $counts['error'] > 0 ) : ?>
							<span class="si-badge si-badge-error"><?php echo esc_html( $counts['error'] ); ?> <?php esc_html_e( 'errors', 'site-inspector' ); ?></span>
						<?php endif; ?>
						<?php if ( $counts['warning'] > 0 ) : ?>
							<span class="si-badge si-badge-warning"><?php echo esc_html( $counts['warning'] ); ?> <?php esc_html_e( 'warnings', 'site-inspector' ); ?></span>
						<?php endif; ?>
						<?php if ( $counts['info'] > 0 ) : ?>
							<span class="si-badge si-badge-info"><?php echo esc_html( $counts['info'] ); ?> <?php esc_html_e( 'info', 'site-inspector' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="si-dashboard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=site-inspector-results&scan_id=' . $latest_scan->id ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View All Issues', 'site-inspector' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=site-inspector-results&action=export_csv&scan_id=' . $latest_scan->id ), 'si_export_csv' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Export CSV', 'site-inspector' ); ?>
			</a>
		</div>
	</div>
	<?php else : ?>
	<div class="si-empty-state">
		<div class="si-empty-icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		</div>
		<h3><?php esc_html_e( 'No scan results yet', 'site-inspector' ); ?></h3>
		<p><?php esc_html_e( 'Run your first scan to discover content issues across your WordPress site.', 'site-inspector' ); ?></p>
	</div>
	<?php endif; ?>

	<div class="si-recent-scans">
		<?php
		$scans = SI_Database::get_scans( 5 );
		if ( count( $scans ) > 1 ) :
		?>
		<h3><?php esc_html_e( 'Recent Scans', 'site-inspector' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'site-inspector' ); ?></th>
					<th><?php esc_html_e( 'Posts Scanned', 'site-inspector' ); ?></th>
					<th><?php esc_html_e( 'Issues Found', 'site-inspector' ); ?></th>
					<th><?php esc_html_e( 'Status', 'site-inspector' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'site-inspector' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $scans as $scan ) : ?>
				<tr>
					<td><?php echo esc_html( $scan->started_at ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $scan->scanned_posts ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $scan->issues_found ) ); ?></td>
					<td>
						<span class="si-status-badge si-status-<?php echo esc_attr( $scan->status ); ?>">
							<?php echo esc_html( SI_Admin::get_scan_status_label( $scan->status ) ); ?>
						</span>
					</td>
					<td>
						<?php if ( 'completed' === $scan->status ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=site-inspector-results&scan_id=' . $scan->id ) ); ?>">
								<?php esc_html_e( 'View Results', 'site-inspector' ); ?>
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
