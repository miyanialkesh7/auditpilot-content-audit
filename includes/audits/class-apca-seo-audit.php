<?php
/**
 * SEO audit: checks SEO title, meta description, and Open Graph image.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audits a post's SEO title, meta description, and Open Graph image,
 * reading from whichever supported SEO plugin (Yoast, Rank Math, AIOSEO) is active.
 */
class APCA_SEO_Audit extends APCA_Abstract_Audit {

	const CATEGORY = 'seo';

	/**
	 * Slug of the detected active SEO plugin, or null if none is active.
	 *
	 * @var string|null
	 */
	private $active_plugin = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->detect_seo_plugin();
	}

	/**
	 * Detect which supported SEO plugin, if any, is active.
	 */
	private function detect_seo_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->active_plugin = 'yoast';
		} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
			$this->active_plugin = 'rankmath';
		} elseif ( defined( 'AIOSEO_VERSION' ) ) {
			$this->active_plugin = 'aioseo';
		}
	}

	/**
	 * Run the SEO audit against a single post.
	 *
	 * @param WP_Post $post Post to audit.
	 * @return array List of issues found.
	 */
	public function run( $post ) {
		$issues = array();

		$issue = $this->check_seo_title( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_meta_description( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_og_image( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		return $issues;
	}

	/**
	 * Check whether the post has a custom SEO title set.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_seo_title( $post ) {
		$title = $this->get_seo_title( $post );

		if ( null === $title ) {
			return null;
		}

		if ( empty( trim( $title ) ) ) {
			$plugin_name = $this->get_plugin_display_name();
			return $this->issue(
				'missing_seo_title',
				'warning',
				sprintf(
					/* translators: SEO plugin name */
					__( 'Missing SEO title in %s. A custom SEO title helps search engines understand the page.', 'auditpilot-content-audit' ),
					$plugin_name
				),
				array( 'plugin' => $this->active_plugin )
			);
		}

		return null;
	}

	/**
	 * Check whether the post has a meta description set.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_meta_description( $post ) {
		$desc = $this->get_meta_description( $post );

		if ( null === $desc ) {
			return null;
		}

		if ( empty( trim( $desc ) ) ) {
			$plugin_name = $this->get_plugin_display_name();
			return $this->issue(
				'missing_meta_description',
				'warning',
				sprintf(
					/* translators: SEO plugin name */
					__( 'Missing meta description in %s. A meta description improves click-through rates in search results.', 'auditpilot-content-audit' ),
					$plugin_name
				),
				array( 'plugin' => $this->active_plugin )
			);
		}

		return null;
	}

	/**
	 * Check whether the post has an Open Graph image set.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_og_image( $post ) {
		$image = $this->get_og_image( $post );

		if ( null === $image ) {
			if ( ! has_post_thumbnail( $post->ID ) ) {
				return $this->issue(
					'missing_og_image',
					'info',
					__( 'No Open Graph image set. Consider adding a featured image or setting an OG image in your SEO plugin.', 'auditpilot-content-audit' )
				);
			}
			return null;
		}

		if ( empty( $image ) ) {
			return $this->issue(
				'missing_og_image',
				'info',
				sprintf(
					/* translators: SEO plugin name */
					__( 'Missing Open Graph image in %s.', 'auditpilot-content-audit' ),
					$this->get_plugin_display_name()
				),
				array( 'plugin' => $this->active_plugin )
			);
		}

		return null;
	}

	/**
	 * Get the post's SEO title from the active SEO plugin.
	 *
	 * @param WP_Post $post Post to read.
	 * @return string|null SEO title, or null if no supported plugin is active.
	 */
	private function get_seo_title( $post ) {
		switch ( $this->active_plugin ) {
			case 'yoast':
				return get_post_meta( $post->ID, '_yoast_wpseo_title', true );

			case 'rankmath':
				return get_post_meta( $post->ID, 'rank_math_title', true );

			case 'aioseo':
				$data = get_post_meta( $post->ID, '_aioseo_title', true );
				return $data;

			default:
				return null;
		}
	}

	/**
	 * Get the post's meta description from the active SEO plugin.
	 *
	 * @param WP_Post $post Post to read.
	 * @return string|null Meta description, or null if no supported plugin is active.
	 */
	private function get_meta_description( $post ) {
		switch ( $this->active_plugin ) {
			case 'yoast':
				return get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );

			case 'rankmath':
				return get_post_meta( $post->ID, 'rank_math_description', true );

			case 'aioseo':
				return get_post_meta( $post->ID, '_aioseo_description', true );

			default:
				return null;
		}
	}

	/**
	 * Get the post's Open Graph image from the active SEO plugin.
	 *
	 * @param WP_Post $post Post to read.
	 * @return string|null Open Graph image URL, or null if no supported plugin is active.
	 */
	private function get_og_image( $post ) {
		switch ( $this->active_plugin ) {
			case 'yoast':
				return get_post_meta( $post->ID, '_yoast_wpseo_opengraph-image', true );

			case 'rankmath':
				return get_post_meta( $post->ID, 'rank_math_facebook_image', true );

			case 'aioseo':
				return get_post_meta( $post->ID, '_aioseo_og_image_url', true );

			default:
				return null;
		}
	}

	/**
	 * Get a human-readable name for the active SEO plugin.
	 *
	 * @return string Display name.
	 */
	private function get_plugin_display_name() {
		$names = array(
			'yoast'    => 'Yoast SEO',
			'rankmath' => 'Rank Math',
			'aioseo'   => 'AIOSEO',
		);
		return isset( $names[ $this->active_plugin ] ) ? $names[ $this->active_plugin ] : __( 'your SEO plugin', 'auditpilot-content-audit' );
	}

	/**
	 * Get the slug of the detected active SEO plugin.
	 *
	 * @return string|null Plugin slug, or null if none is active.
	 */
	public function get_active_plugin() {
		return $this->active_plugin;
	}
}
