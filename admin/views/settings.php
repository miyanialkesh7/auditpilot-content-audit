<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = wp_parse_args( get_option( 'site_inspector_settings', array() ), array(
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
	'publish' => __( 'Published', 'site-inspector' ),
	'draft'   => __( 'Drafts', 'site-inspector' ),
	'pending' => __( 'Pending Review', 'site-inspector' ),
	'private' => __( 'Private', 'site-inspector' ),
);

$all_audits = array(
	'content'  => __( 'Content Audit', 'site-inspector' ),
	'media'    => __( 'Media Audit', 'site-inspector' ),
	'headings' => __( 'Heading Audit', 'site-inspector' ),
	'links'    => __( 'Link Audit', 'site-inspector' ),
	'seo'      => __( 'SEO Audit', 'site-inspector' ),
	'builder'  => __( 'Builder Audit', 'site-inspector' ),
);

if ( isset( $_GET['updated'] ) && '1' === sanitize_key( wp_unslash( $_GET['updated'] ) ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'site-inspector' ) . '</p></div>';
}
?>
<div class="wrap si-wrap">
	<div class="si-header">
		<h1 class="si-title"><?php esc_html_e( 'Settings', 'site-inspector' ); ?></h1>
	</div>

	<form method="post" action="" class="si-settings-form">
		<?php wp_nonce_field( 'si_save_settings', 'si_settings_nonce' ); ?>

		<div class="si-settings-section">
			<h2><?php esc_html_e( 'Content to Scan', 'site-inspector' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'site-inspector' ); ?></th>
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
					<th scope="row"><?php esc_html_e( 'Post Statuses', 'site-inspector' ); ?></th>
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

		<div class="si-settings-section">
			<h2><?php esc_html_e( 'Audit Modules', 'site-inspector' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled Audits', 'site-inspector' ); ?></th>
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

		<div class="si-settings-section">
			<h2><?php esc_html_e( 'Audit Thresholds', 'site-inspector' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="short_content_threshold"><?php esc_html_e( 'Short Content Threshold', 'site-inspector' ); ?></label>
					</th>
					<td>
						<input type="number" id="short_content_threshold" name="short_content_threshold"
							value="<?php echo esc_attr( $settings['short_content_threshold'] ); ?>"
							min="50" max="5000" step="50" class="small-text">
						<?php esc_html_e( 'words', 'site-inspector' ); ?>
						<p class="description"><?php esc_html_e( 'Posts with fewer words than this will be flagged as short content.', 'site-inspector' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="old_draft_days"><?php esc_html_e( 'Old Draft Threshold', 'site-inspector' ); ?></label>
					</th>
					<td>
						<input type="number" id="old_draft_days" name="old_draft_days"
							value="<?php echo esc_attr( $settings['old_draft_days'] ); ?>"
							min="1" max="365" class="small-text">
						<?php esc_html_e( 'days', 'site-inspector' ); ?>
						<p class="description"><?php esc_html_e( 'Drafts not updated within this many days will be flagged.', 'site-inspector' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="large_image_kb"><?php esc_html_e( 'Large Image Threshold', 'site-inspector' ); ?></label>
					</th>
					<td>
						<input type="number" id="large_image_kb" name="large_image_kb"
							value="<?php echo esc_attr( $settings['large_image_kb'] ); ?>"
							min="50" max="10000" step="50" class="small-text">
						<?php esc_html_e( 'KB', 'site-inspector' ); ?>
						<p class="description"><?php esc_html_e( 'Images larger than this size will be flagged for optimization.', 'site-inspector' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="si-settings-section">
			<h2><?php esc_html_e( 'Link Checking', 'site-inspector' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'External Link Checking', 'site-inspector' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="check_external_links" value="1"
								<?php checked( ! empty( $settings['check_external_links'] ) ); ?>>
							<?php esc_html_e( 'Check external links for broken URLs', 'site-inspector' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Warning: Enabling this significantly increases scan time as it makes HTTP requests to external URLs. Only enable on sites with few external links.', 'site-inspector' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'site-inspector' ) ); ?>
	</form>
</div>
