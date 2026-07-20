<?php
/**
 * Dashboard page view.
 *
 * @package AuditPilot_Content_Audit
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file included inside APCA_Admin; variables are local to the method scope, not truly global.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$latest_scan = APCA_Database::get_latest_scan();
$score_data  = null;
$summary     = null;
$settings    = get_option( 'apca_settings', array() );

if ( $latest_scan && 'completed' === $latest_scan->status ) {
	$score_data = APCA_Database::get_score_data( $latest_scan->id );
	$summary    = APCA_Database::get_summary( $latest_scan->id );
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
<div class="wrap apca-wrap">
	<div class="apca-header">
		<h1 class="apca-title">
			<svg class="apca-logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
			<?php esc_html_e( 'AuditPilot', 'auditpilot-content-audit' ); ?>
		</h1>
		<p class="apca-subtitle"><?php esc_html_e( 'Audit, Inspect & Improve Every WordPress Content Type.', 'auditpilot-content-audit' ); ?></p>
	</div>

	<div class="apca-scan-controls">
		<div class="apca-scan-card">
			<div class="apca-scan-info">
				<?php if ( $latest_scan ) : ?>
					<p>
						<?php if ( 'completed' === $latest_scan->status ) : ?>
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: time ago, 2: number of posts scanned, 3: number of issues found */
									__( 'Last scan completed %1$s — %2$s posts scanned, %3$s issues found.', 'auditpilot-content-audit' ),
									'<strong>' . esc_html( human_time_diff( strtotime( $latest_scan->completed_at ), strtotime( current_time( 'mysql' ) ) ) . ' ' . __( 'ago', 'auditpilot-content-audit' ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $latest_scan->scanned_posts ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $latest_scan->issues_found ) ) . '</strong>'
								),
								array( 'strong' => array() )
							);
							?>
						<?php elseif ( 'running' === $latest_scan->status ) : ?>
							<?php esc_html_e( 'A scan is currently in progress.', 'auditpilot-content-audit' ); ?>
						<?php endif; ?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'No scans have been run yet. Start your first scan to audit your site content.', 'auditpilot-content-audit' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="apca-scan-actions">
				<button id="apca-start-scan" class="button button-primary button-hero">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php esc_html_e( 'Start New Scan', 'auditpilot-content-audit' ); ?>
				</button>
				<?php if ( $latest_scan && 'completed' === $latest_scan->status ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=auditpilot-content-audit-results&scan_id=' . $latest_scan->id ) ); ?>" class="button button-secondary button-hero">
						<?php esc_html_e( 'View Results', 'auditpilot-content-audit' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div id="apca-scan-progress" class="apca-progress-container" style="display:none;">
			<div class="apca-progress-header">
				<span id="apca-progress-label"><?php esc_html_e( 'Preparing scan...', 'auditpilot-content-audit' ); ?></span>
				<span id="apca-progress-percent">0%</span>
			</div>
			<div class="apca-progress-bar-wrap">
				<div class="apca-progress-bar" id="apca-progress-bar" style="width:0%"></div>
			</div>
			<div id="apca-progress-detail" class="apca-progress-detail"></div>
		</div>
	</div>

	<?php if ( $score_data && $summary ) : ?>
	<div class="apca-results-overview">
		<h2><?php esc_html_e( 'Latest Scan Overview', 'auditpilot-content-audit' ); ?></h2>

		<div class="apca-scores-grid">
			<div class="apca-score-card apca-score-overall">
				<div class="apca-score-circle apca-score-<?php echo esc_attr( APCA_Admin::get_score_class( $score_data['overall'] ) ); ?>">
					<span class="apca-score-number"><?php echo esc_html( $score_data['overall'] ); ?></span>
					<span class="apca-score-label"><?php esc_html_e( 'Overall', 'auditpilot-content-audit' ); ?></span>
				</div>
			</div>

			<?php foreach ( $score_data['categories'] as $category => $score ) : ?>
			<div class="apca-score-card">
				<div class="apca-score-circle apca-score-<?php echo esc_attr( APCA_Admin::get_score_class( $score ) ); ?>">
					<span class="apca-score-number"><?php echo esc_html( $score ); ?></span>
					<span class="apca-score-label"><?php echo esc_html( APCA_Admin::get_category_label( $category ) ); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="apca-stats-grid">
			<?php
			$severity_counts = array(
				'error'   => 0,
				'warning' => 0,
				'info'    => 0,
			);
			if ( ! empty( $summary['by_severity'] ) ) {
				foreach ( $summary['by_severity'] as $sev => $row ) {
					$severity_counts[ $sev ] = (int) $row->count;
				}
			}
			?>
			<div class="apca-stat-card apca-stat-error">
				<span class="apca-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['error'] ) ); ?></span>
				<span class="apca-stat-label"><?php esc_html_e( 'Errors', 'auditpilot-content-audit' ); ?></span>
			</div>
			<div class="apca-stat-card apca-stat-warning">
				<span class="apca-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['warning'] ) ); ?></span>
				<span class="apca-stat-label"><?php esc_html_e( 'Warnings', 'auditpilot-content-audit' ); ?></span>
			</div>
			<div class="apca-stat-card apca-stat-info">
				<span class="apca-stat-number"><?php echo esc_html( number_format_i18n( $severity_counts['info'] ) ); ?></span>
				<span class="apca-stat-label"><?php esc_html_e( 'Info', 'auditpilot-content-audit' ); ?></span>
			</div>
			<div class="apca-stat-card apca-stat-posts">
				<span class="apca-stat-number"><?php echo esc_html( number_format_i18n( $summary['posts_with_issues'] ) ); ?></span>
				<span class="apca-stat-label"><?php esc_html_e( 'Posts with Issues', 'auditpilot-content-audit' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $summary['by_category'] ) ) : ?>
		<div class="apca-category-breakdown">
			<h3><?php esc_html_e( 'Issues by Category', 'auditpilot-content-audit' ); ?></h3>
			<div class="apca-category-grid">
				<?php
				$category_totals = array();
				foreach ( $summary['by_category'] as $row ) {
					if ( ! isset( $category_totals[ $row->category ] ) ) {
						$category_totals[ $row->category ] = array(
							'error'   => 0,
							'warning' => 0,
							'info'    => 0,
						);
					}
					$category_totals[ $row->category ][ $row->severity ] = (int) $row->count;
				}

				foreach ( $category_totals as $category => $counts ) :
					$total = array_sum( $counts );
					$icon  = isset( $category_icons[ $category ] ) ? $category_icons[ $category ] : '📋';
					?>
				<div class="apca-category-card">
					<div class="apca-category-header">
						<span class="apca-category-icon"><?php echo esc_html( $icon ); ?></span>
						<span class="apca-category-name"><?php echo esc_html( APCA_Admin::get_category_label( $category ) ); ?></span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=auditpilot-content-audit-results&scan_id=' . $latest_scan->id . '&category=' . $category ) ); ?>" class="apca-category-count"><?php echo esc_html( (string) $total ); ?></a>
					</div>
					<div class="apca-category-badges">
						<?php if ( $counts['error'] > 0 ) : ?>
							<span class="apca-badge apca-badge-error"><?php echo esc_html( (string) $counts['error'] ); ?> <?php esc_html_e( 'errors', 'auditpilot-content-audit' ); ?></span>
						<?php endif; ?>
						<?php if ( $counts['warning'] > 0 ) : ?>
							<span class="apca-badge apca-badge-warning"><?php echo esc_html( (string) $counts['warning'] ); ?> <?php esc_html_e( 'warnings', 'auditpilot-content-audit' ); ?></span>
						<?php endif; ?>
						<?php if ( $counts['info'] > 0 ) : ?>
							<span class="apca-badge apca-badge-info"><?php echo esc_html( (string) $counts['info'] ); ?> <?php esc_html_e( 'info', 'auditpilot-content-audit' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="apca-dashboard-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=auditpilot-content-audit-results&scan_id=' . $latest_scan->id ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View All Issues', 'auditpilot-content-audit' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=auditpilot-content-audit-results&action=export_csv&scan_id=' . $latest_scan->id ), 'apca_export_csv' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Export CSV', 'auditpilot-content-audit' ); ?>
			</a>
		</div>
	</div>
	<?php else : ?>
	<div class="apca-empty-state">
		<div class="apca-empty-icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		</div>
		<h3><?php esc_html_e( 'No scan results yet', 'auditpilot-content-audit' ); ?></h3>
		<p><?php esc_html_e( 'Run your first scan to discover content issues across your WordPress site.', 'auditpilot-content-audit' ); ?></p>
	</div>
	<?php endif; ?>

	<div class="apca-recent-scans">
		<?php
		$scans = APCA_Database::get_scans( 5 );
		if ( count( $scans ) > 1 ) :
			?>
		<h3><?php esc_html_e( 'Recent Scans', 'auditpilot-content-audit' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'auditpilot-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Posts Scanned', 'auditpilot-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Issues Found', 'auditpilot-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Status', 'auditpilot-content-audit' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'auditpilot-content-audit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $scans as $scan ) : ?>
				<tr>
					<td><?php echo esc_html( $scan->started_at ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $scan->scanned_posts ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $scan->issues_found ) ); ?></td>
					<td>
						<span class="apca-status-badge apca-status-<?php echo esc_attr( $scan->status ); ?>">
							<?php echo esc_html( APCA_Admin::get_scan_status_label( $scan->status ) ); ?>
						</span>
					</td>
					<td>
						<?php if ( 'completed' === $scan->status ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=auditpilot-content-audit-results&scan_id=' . $scan->id ) ); ?>">
								<?php esc_html_e( 'View Results', 'auditpilot-content-audit' ); ?>
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
