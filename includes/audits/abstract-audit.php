<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class SI_Abstract_Audit {

	abstract public function run( $post );

	protected function issue( $type, $severity, $message, $details = array() ) {
		return array(
			'type'     => $type,
			'severity' => $severity,
			'message'  => $message,
			'details'  => $details,
		);
	}

	protected function get_clean_content( $post ) {
		$content = $post->post_content;
		$content = do_shortcode( $content );
		return $content;
	}

	protected function get_word_count( $post ) {
		$content = wp_strip_all_tags( $post->post_content );
		return str_word_count( $content );
	}

	protected function get_images_from_content( $content ) {
		$images = array();

		if ( preg_match_all( '/<img[^>]+>/i', $content, $matches ) ) {
			foreach ( $matches[0] as $img_tag ) {
				$images[] = $img_tag;
			}
		}

		return $images;
	}

	protected function extract_attr( $tag, $attr ) {
		if ( preg_match( '/' . preg_quote( $attr, '/' ) . '\s*=\s*["\']([^"\']*)["\']/', $tag, $match ) ) {
			return $match[1];
		}
		return '';
	}

	protected function get_links_from_content( $content ) {
		$links = array();

		if ( preg_match_all( '/<a[^>]*>/i', $content, $matches ) ) {
			foreach ( $matches[0] as $tag ) {
				$href  = $this->extract_attr( $tag, 'href' );
				$links[] = array(
					'tag'  => $tag,
					'href' => $href,
				);
			}
		}

		return $links;
	}

	protected function get_headings_from_content( $content ) {
		$headings = array();

		if ( preg_match_all( '/<(h[1-6])[^>]*>(.*?)<\/\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$headings[] = array(
					'tag'  => $match[1],
					'text' => wp_strip_all_tags( $match[2] ),
					'full' => $match[0],
				);
			}
		}

		return $headings;
	}

	protected function is_url_internal( $url ) {
		$home = home_url();
		$parsed_home = parse_url( $home );
		$parsed_url  = parse_url( $url );

		if ( empty( $parsed_url['host'] ) ) {
			return true;
		}

		return strtolower( $parsed_url['host'] ) === strtolower( $parsed_home['host'] );
	}
}
