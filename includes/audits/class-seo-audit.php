<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAWP_SEO_Audit extends CAWP_Abstract_Audit {

	const CATEGORY = 'seo';

	private $active_plugin = null;

	public function __construct() {
		$this->detect_seo_plugin();
	}

	private function detect_seo_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->active_plugin = 'yoast';
		} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
			$this->active_plugin = 'rankmath';
		} elseif ( defined( 'AIOSEO_VERSION' ) ) {
			$this->active_plugin = 'aioseo';
		}
	}

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
					__( 'Missing SEO title in %s. A custom SEO title helps search engines understand the page.', 'wp-content-audit' ),
					$plugin_name
				),
				array( 'plugin' => $this->active_plugin )
			);
		}

		return null;
	}

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
					__( 'Missing meta description in %s. A meta description improves click-through rates in search results.', 'wp-content-audit' ),
					$plugin_name
				),
				array( 'plugin' => $this->active_plugin )
			);
		}

		return null;
	}

	private function check_og_image( $post ) {
		$image = $this->get_og_image( $post );

		if ( null === $image ) {
			if ( ! has_post_thumbnail( $post->ID ) ) {
				return $this->issue(
					'missing_og_image',
					'info',
					__( 'No Open Graph image set. Consider adding a featured image or setting an OG image in your SEO plugin.', 'wp-content-audit' )
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
					__( 'Missing Open Graph image in %s.', 'wp-content-audit' ),
					$this->get_plugin_display_name()
				),
				array( 'plugin' => $this->active_plugin )
			);
		}

		return null;
	}

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

	private function get_plugin_display_name() {
		$names = array(
			'yoast'    => 'Yoast SEO',
			'rankmath' => 'Rank Math',
			'aioseo'   => 'AIOSEO',
		);
		return isset( $names[ $this->active_plugin ] ) ? $names[ $this->active_plugin ] : __( 'your SEO plugin', 'wp-content-audit' );
	}

	public function get_active_plugin() {
		return $this->active_plugin;
	}
}
