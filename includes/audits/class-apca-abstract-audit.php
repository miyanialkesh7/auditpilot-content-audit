<?php
/**
 * Abstract base class for content audits.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class providing shared helpers for all audit types.
 */
abstract class APCA_Abstract_Audit {

	/**
	 * Run the audit against a single post.
	 *
	 * @param WP_Post $post Post to audit.
	 * @return array List of issues found.
	 */
	abstract public function run( $post );

	/**
	 * Build a single issue entry.
	 *
	 * @param string $type     Issue type identifier.
	 * @param string $severity Issue severity (error, warning, info).
	 * @param string $message  Human-readable issue message.
	 * @param array  $details  Optional extra details about the issue.
	 * @return array Issue entry.
	 */
	protected function issue( $type, $severity, $message, $details = array() ) {
		return array(
			'type'     => $type,
			'severity' => $severity,
			'message'  => $message,
			'details'  => $details,
		);
	}

	/**
	 * Get post content with shortcodes rendered.
	 *
	 * @param WP_Post $post Post to read content from.
	 * @return string Rendered post content.
	 */
	protected function get_clean_content( $post ) {
		$content = $post->post_content;
		$content = do_shortcode( $content );
		return $content;
	}

	/**
	 * Get the word count of a post's content.
	 *
	 * @param WP_Post $post Post to count words for.
	 * @return int Word count.
	 */
	protected function get_word_count( $post ) {
		$content = wp_strip_all_tags( $post->post_content );
		return str_word_count( $content );
	}

	/**
	 * Extract all image tags from HTML content.
	 *
	 * @param string $content HTML content to scan.
	 * @return string[] Matched <img> tags.
	 */
	protected function get_images_from_content( $content ) {
		$images = array();

		if ( preg_match_all( '/<img[^>]+>/i', $content, $matches ) ) {
			foreach ( $matches[0] as $img_tag ) {
				$images[] = $img_tag;
			}
		}

		return $images;
	}

	/**
	 * Extract an attribute value from an HTML tag.
	 *
	 * @param string $tag  HTML tag markup.
	 * @param string $attr Attribute name to extract.
	 * @return string Attribute value, or an empty string if not found.
	 */
	protected function extract_attr( $tag, $attr ) {
		if ( preg_match( '/' . preg_quote( $attr, '/' ) . '\s*=\s*["\']([^"\']*)["\']/', $tag, $match ) ) {
			return $match[1];
		}
		return '';
	}

	/**
	 * Extract all links from HTML content.
	 *
	 * @param string $content HTML content to scan.
	 * @return array[] List of arrays with 'tag' and 'href' keys.
	 */
	protected function get_links_from_content( $content ) {
		$links = array();

		if ( preg_match_all( '/<a[^>]*>/i', $content, $matches ) ) {
			foreach ( $matches[0] as $tag ) {
				$href    = $this->extract_attr( $tag, 'href' );
				$links[] = array(
					'tag'  => $tag,
					'href' => $href,
				);
			}
		}

		return $links;
	}

	/**
	 * Extract all headings from HTML content.
	 *
	 * @param string $content HTML content to scan.
	 * @return array[] List of arrays with 'tag', 'text', and 'full' keys.
	 */
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

	/**
	 * Check whether a URL points to the current site.
	 *
	 * @param string $url URL to check.
	 * @return bool True if the URL's host matches the site's host.
	 */
	protected function is_url_internal( $url ) {
		$home        = home_url();
		$parsed_home = wp_parse_url( $home );
		$parsed_url  = wp_parse_url( $url );

		if ( empty( $parsed_url['host'] ) ) {
			return true;
		}

		return strtolower( $parsed_url['host'] ) === strtolower( $parsed_home['host'] );
	}
}
