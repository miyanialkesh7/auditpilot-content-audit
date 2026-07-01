<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file included inside APCA_Admin; variables are local to the method scope, not truly global.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = wp_parse_args( get_option( 'apca_settings', array() ), array(
	'post_types'              => array( 'post', 'page' ),
	'post_statuses'           => array( 'publish' ),
	'enabled_audits'          => array( 'content', 'media', 'headings', 'links', 'seo', 'builder' ),
	'short_content_threshold' => 300,
	'old_draft_days'          => 30,
	'large_image_kb'          => 500,
	'check_external_links'    => false,
) );

$all_post_types = get_post_types( array( 'public' => true ), 'objects' );
$all_statuses   = array(
	'publish' => __( 'Published', 'auditpilot-content-audit' ),
	'draft'   => __( 'Drafts', 'auditpilot-content-audit' ),
	'pending' => __( 'Pending Review', 'auditpilot-content-audit' ),
	'private' => __( 'Private', 'auditpilot-content-audit' ),
);

$all_audits = array(
	'content'  => __( 'Content Audit', 'auditpilot-content-audit' ),
	'media'    => __( 'Media Audit', 'auditpilot-content-audit' ),
	'headings' => __( 'Heading Audit', 'auditpilot-content-audit' ),
	'links'    => __( 'Link Audit', 'auditpilot-content-audit' ),
	'seo'      => __( 'SEO Audit', 'auditpilot-content-audit' ),
	'builder'  => __( 'Builder Audit', 'auditpilot-content-audit' ),
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by our own redirect after save; no data is modified here.
if ( isset( $_GET['updated'] ) && '1' === sanitize_key( wp_unslash( $_GET['updated'] ) ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'auditpilot-content-audit' ) . '</p></div>';
}
?>
<div class="wrap apca-wrap">
	<div class="apca-header">
		<h1 class="apca-title"><?php esc_html_e( 'Settings', 'auditpilot-content-audit' ); ?></h1>
	</div>

	<form method="post" action="" class="apca-settings-form">
		<?php wp_nonce_field( 'apca_save_settings', 'apca_settings_nonce' ); ?>

		<div class="apca-settings-section">
			<h2><?php esc_html_e( 'Content to Scan', 'auditpilot-content-audit' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'auditpilot-content-audit' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $all_post_types as $pt_slug => $pt_obj ) : ?>
							<label>
								<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt_slug ); ?>"
									<?php checked( in_array( $pt_slug, (array) $settings['post_types'], true ) ); ?>>
								<?php echo esc_html( $pt_obj->labels->singular_name ); ?>
								<span class="description">(<?php echo esc_html( $pt_slug ); ?>)</span>
							</label><br>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Statuses', 'auditpilot-content-audit' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $all_statuses as $status_slug => $status_label ) : ?>
							<label>
								<input type="checkbox" name="post_statuses[]" value="<?php echo esc_attr( $status_slug ); ?>"
									<?php checked( in_array( $status_slug, (array) $settings['post_statuses'], true ) ); ?>>
								<?php echo esc_html( $status_label ); ?>
							</label><br>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</table>
		</div>

		<div class="apca-settings-section">
			<h2><?php esc_html_e( 'Audit Modules', 'auditpilot-content-audit' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled Audits', 'auditpilot-content-audit' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $all_audits as $audit_slug => $audit_label ) : ?>
							<label>
								<input type="checkbox" name="enabled_audits[]" value="<?php echo esc_attr( $audit_slug ); ?>"
									<?php checked( in_array( $audit_slug, (array) $settings['enabled_audits'], true ) ); ?>>
								<?php echo esc_html( $audit_label ); ?>
							</label><br>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</table>
		</div>

		<div class="apca-settings-section">
			<h2><?php esc_html_e( 'Audit Thresholds', 'auditpilot-content-audit' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="short_content_threshold"><?php esc_html_e( 'Short Content Threshold', 'auditpilot-content-audit' ); ?></label>
					</th>
					<td>
						<input type="number" id="short_content_threshold" name="short_content_threshold"
							value="<?php echo esc_attr( $settings['short_content_threshold'] ); ?>"
							min="50" max="5000" step="50" class="small-text">
						<?php esc_html_e( 'words', 'auditpilot-content-audit' ); ?>
						<p class="description"><?php esc_html_e( 'Posts with fewer words than this will be flagged as short content.', 'auditpilot-content-audit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="old_draft_days"><?php esc_html_e( 'Old Draft Threshold', 'auditpilot-content-audit' ); ?></label>
					</th>
					<td>
						<input type="number" id="old_draft_days" name="old_draft_days"
							value="<?php echo esc_attr( $settings['old_draft_days'] ); ?>"
							min="1" max="365" class="small-text">
						<?php esc_html_e( 'days', 'auditpilot-content-audit' ); ?>
						<p class="description"><?php esc_html_e( 'Drafts not updated within this many days will be flagged.', 'auditpilot-content-audit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="large_image_kb"><?php esc_html_e( 'Large Image Threshold', 'auditpilot-content-audit' ); ?></label>
					</th>
					<td>
						<input type="number" id="large_image_kb" name="large_image_kb"
							value="<?php echo esc_attr( $settings['large_image_kb'] ); ?>"
							min="50" max="10000" step="50" class="small-text">
						<?php esc_html_e( 'KB', 'auditpilot-content-audit' ); ?>
						<p class="description"><?php esc_html_e( 'Images larger than this size will be flagged for optimization.', 'auditpilot-content-audit' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="apca-settings-section">
			<h2><?php esc_html_e( 'Link Checking', 'auditpilot-content-audit' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'External Link Checking', 'auditpilot-content-audit' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="check_external_links" value="1"
								<?php checked( ! empty( $settings['check_external_links'] ) ); ?>>
							<?php esc_html_e( 'Check external links for broken URLs', 'auditpilot-content-audit' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Warning: Enabling this significantly increases scan time as it makes HTTP requests to external URLs. Only enable on sites with few external links.', 'auditpilot-content-audit' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="apca-settings-section">
			<h2><?php esc_html_e( 'Data Management', 'auditpilot-content-audit' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Remove Data on Deactivation', 'auditpilot-content-audit' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="delete_on_deactivation" value="1"
								<?php checked( ! empty( $settings['delete_on_deactivation'] ) ); ?>>
							<?php esc_html_e( 'Delete all scan data (database tables) when the plugin is deactivated', 'auditpilot-content-audit' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, deactivating the plugin will permanently remove all scan results from the database. Your audit settings will be preserved. This cannot be undone.', 'auditpilot-content-audit' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'auditpilot-content-audit' ) ); ?>
	</form>
</div>
