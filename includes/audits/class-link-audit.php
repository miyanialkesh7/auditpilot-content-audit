<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SI_Link_Audit extends SI_Abstract_Audit {

	const CATEGORY = 'links';

	private $settings;
	private static $checked_urls = array();

	public function __construct( $settings = array() ) {
		$this->settings = wp_parse_args( $settings, array(
			'check_external_links' => false,
			'external_link_timeout' => 5,
		) );
	}

	public function run( $post ) {
		$issues  = array();
		$content = $post->post_content;
		$links   = $this->get_links_from_content( $content );

		foreach ( $this->check_empty_links( $links ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		foreach ( $this->check_internal_links( $links ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		if ( ! empty( $this->settings['check_external_links'] ) ) {
			foreach ( $this->check_external_links( $links ) as $issue ) {
				$issue['category'] = self::CATEGORY;
				$issues[]          = $issue;
			}
		}

		return $issues;
	}

	private function check_empty_links( $links ) {
		$issues = array();
		$count  = 0;

		foreach ( $links as $link ) {
			$href = trim( $link['href'] );
			if ( '' === $href || '#' === $href || 'javascript:void(0)' === strtolower( $href ) || 'javascript:;' === strtolower( $href ) ) {
				$count++;
			}
		}

		if ( $count > 0 ) {
			$issues[] = $this->issue(
				'empty_link',
				'warning',
				sprintf(
					_n(
						'%d empty or placeholder link found.',
						'%d empty or placeholder links found.',
						$count,
						'site-inspector'
					),
					$count
				),
				array( 'count' => $count )
			);
		}

		return $issues;
	}

	private function check_internal_links( $links ) {
		$issues = array();
		$broken = array();

		foreach ( $links as $link ) {
			$href = trim( $link['href'] );

			if ( empty( $href ) || '#' === $href || strpos( $href, 'mailto:' ) === 0 || strpos( $href, 'tel:' ) === 0 ) {
				continue;
			}

			if ( strpos( $href, '/' ) === 0 && strpos( $href, '//' ) !== 0 ) {
				$href = home_url( $href );
			}

			if ( ! $this->is_url_internal( $href ) ) {
				continue;
			}

			$path = $this->get_path_from_url( $href );
			$post_exists = $this->internal_url_exists( $path );

			if ( ! $post_exists ) {
				$broken[] = $href;
			}
		}

		$broken = array_unique( $broken );

		foreach ( $broken as $url ) {
			$issues[] = $this->issue(
				'broken_internal_link',
				'error',
				sprintf(
					/* translators: URL */
					__( 'Broken internal link: %s', 'site-inspector' ),
					esc_url( $url )
				),
				array( 'url' => $url )
			);
		}

		return $issues;
	}

	private function check_external_links( $links ) {
		$issues  = array();
		$checked = array();

		foreach ( $links as $link ) {
			$href = trim( $link['href'] );

			if ( empty( $href ) || $this->is_url_internal( $href ) ) {
				continue;
			}

			if ( strpos( $href, 'http' ) !== 0 ) {
				continue;
			}

			$base_url = strtok( $href, '#' );
			if ( isset( $checked[ $base_url ] ) ) {
				continue;
			}

			if ( isset( self::$checked_urls[ $base_url ] ) ) {
				$status = self::$checked_urls[ $base_url ];
			} else {
				$status = $this->check_url_status( $base_url );
				self::$checked_urls[ $base_url ] = $status;
			}

			$checked[ $base_url ] = true;

			if ( $status >= 400 || $status === 0 ) {
				$issues[] = $this->issue(
					'broken_external_link',
					'error',
					sprintf(
						/* translators: 1: URL, 2: HTTP status code */
						__( 'Broken external link (HTTP %2$d): %1$s', 'site-inspector' ),
						esc_url( $href ),
						$status
					),
					array( 'url' => $href, 'status' => $status )
				);
			} elseif ( $status >= 300 ) {
				$issues[] = $this->issue(
					'redirecting_link',
					'info',
					sprintf(
						/* translators: 1: URL, 2: HTTP status code */
						__( 'Redirecting link (HTTP %2$d): %1$s', 'site-inspector' ),
						esc_url( $href ),
						$status
					),
					array( 'url' => $href, 'status' => $status )
				);
			}
		}

		return $issues;
	}

	private function get_path_from_url( $url ) {
		$parsed = parse_url( $url );
		return isset( $parsed['path'] ) ? $parsed['path'] : '/';
	}

	private function internal_url_exists( $path ) {
		if ( '/' === $path || '' === $path ) {
			return true;
		}

		$url     = home_url( $path );
		$post_id = url_to_postid( $url );

		if ( $post_id > 0 ) {
			return true;
		}

		$slug             = basename( rtrim( $path, '/' ) );
		$public_taxonomies = get_taxonomies( array( 'public' => true ) );

		foreach ( $public_taxonomies as $taxonomy ) {
			if ( get_term_by( 'slug', $slug, $taxonomy ) ) {
				return true;
			}
		}

		return false;
	}

	private function check_url_status( $url ) {
		$timeout = isset( $this->settings['external_link_timeout'] ) ? (int) $this->settings['external_link_timeout'] : 5;

		$response = wp_remote_head( $url, array(
			'timeout'     => $timeout,
			'user-agent'  => 'Mozilla/5.0 (compatible; SiteInspector/' . SITE_INSPECTOR_VERSION . ')',
			'redirection' => 0,
		) );

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		return (int) wp_remote_retrieve_response_code( $response );
	}
}
