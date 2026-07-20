<?php
/**
 * Content audit: checks for empty content, missing featured image/excerpt,
 * short content, and stale drafts.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audits a post's core content fields: body, featured image, excerpt,
 * word count, and draft staleness.
 */
class APCA_Content_Audit extends APCA_Abstract_Audit {

	const CATEGORY = 'content';

	/**
	 * Audit settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param array $settings Audit settings.
	 */
	public function __construct( $settings = array() ) {
		$this->settings = wp_parse_args(
			$settings,
			array(
				'short_content_threshold' => 300,
				'old_draft_days'          => 30,
			)
		);
	}

	/**
	 * Run the content audit against a single post.
	 *
	 * @param WP_Post $post Post to audit.
	 * @return array List of issues found.
	 */
	public function run( $post ) {
		$issues = array();

		$issue = $this->check_empty_content( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_missing_featured_image( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_missing_excerpt( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_short_content( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_old_draft( $post );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		return $issues;
	}

	/**
	 * Check whether a post's content is empty.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_empty_content( $post ) {
		$content = trim( wp_strip_all_tags( $post->post_content ) );

		if ( empty( $content ) ) {
			return $this->issue(
				'empty_content',
				'error',
				__( 'Post has no content.', 'auditpilot-content-audit' )
			);
		}

		return null;
	}

	/**
	 * Check whether a post is missing a featured image.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_missing_featured_image( $post ) {
		if ( ! has_post_thumbnail( $post->ID ) ) {
			return $this->issue(
				'missing_featured_image',
				'warning',
				__( 'Post is missing a featured image.', 'auditpilot-content-audit' )
			);
		}

		return null;
	}

	/**
	 * Check whether a post is missing a manual excerpt.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_missing_excerpt( $post ) {
		if ( post_type_supports( $post->post_type, 'excerpt' ) && empty( trim( $post->post_excerpt ) ) ) {
			return $this->issue(
				'missing_excerpt',
				'info',
				__( 'Post is missing a manual excerpt.', 'auditpilot-content-audit' )
			);
		}

		return null;
	}

	/**
	 * Check whether a post's content is below the configured word-count threshold.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_short_content( $post ) {
		$threshold = (int) $this->settings['short_content_threshold'];
		$content   = trim( wp_strip_all_tags( $post->post_content ) );

		if ( empty( $content ) ) {
			return null;
		}

		$word_count = str_word_count( $content );

		if ( $word_count < $threshold ) {
			return $this->issue(
				'short_content',
				'warning',
				sprintf(
					/* translators: 1: word count, 2: threshold */
					__( 'Content is short: %1$d words (minimum recommended: %2$d).', 'auditpilot-content-audit' ),
					$word_count,
					$threshold
				),
				array(
					'word_count' => $word_count,
					'threshold'  => $threshold,
				)
			);
		}

		return null;
	}

	/**
	 * Check whether a draft post has gone untouched past the configured age threshold.
	 *
	 * @param WP_Post $post Post to check.
	 * @return array|null Issue entry, or null if there's no issue.
	 */
	private function check_old_draft( $post ) {
		if ( 'draft' !== $post->post_status ) {
			return null;
		}

		$days     = (int) $this->settings['old_draft_days'];
		$modified = strtotime( $post->post_modified );
		$cutoff   = strtotime( "-{$days} days" );

		if ( $modified < $cutoff ) {
			$days_old = (int) round( ( time() - $modified ) / DAY_IN_SECONDS );
			return $this->issue(
				'old_draft',
				'info',
				sprintf(
					/* translators: 1: number of days old */
					__( 'Draft has not been updated in %d days.', 'auditpilot-content-audit' ),
					$days_old
				),
				array( 'days_old' => $days_old )
			);
		}

		return null;
	}
}
