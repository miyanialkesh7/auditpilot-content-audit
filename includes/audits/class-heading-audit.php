<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SI_Heading_Audit extends SI_Abstract_Audit {

	const CATEGORY = 'headings';

	public function run( $post ) {
		$issues  = array();
		$content = $post->post_content;

		foreach ( $this->check_multiple_h1( $content ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		foreach ( $this->check_heading_structure( $content ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		$issue = $this->check_missing_headings( $post, $content );
		if ( $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		return $issues;
	}

	private function check_multiple_h1( $content ) {
		$issues   = array();
		$headings = $this->get_headings_from_content( $content );
		$h1s      = array_filter( $headings, function( $h ) {
			return 'h1' === $h['tag'];
		} );

		if ( count( $h1s ) > 1 ) {
			$issues[] = $this->issue(
				'multiple_h1',
				'error',
				sprintf(
					/* translators: number of H1 tags found */
					__( 'Multiple H1 tags found (%d). Each page should have only one H1.', 'site-inspector' ),
					count( $h1s )
				),
				array( 'count' => count( $h1s ) )
			);
		} elseif ( count( $h1s ) === 1 ) {
			$issues[] = $this->issue(
				'multiple_h1',
				'warning',
				__( 'An H1 tag was found in the content. The page title is typically rendered as H1 by the theme; this may cause duplicate H1s.', 'site-inspector' )
			);
		}

		return $issues;
	}

	private function check_heading_structure( $content ) {
		$issues   = array();
		$headings = $this->get_headings_from_content( $content );

		$heading_levels = array_map( function( $h ) {
			return (int) substr( $h['tag'], 1 );
		}, $headings );

		$heading_levels = array_filter( $heading_levels, function( $l ) {
			return $l >= 2;
		} );

		$heading_levels = array_values( $heading_levels );

		for ( $i = 1; $i < count( $heading_levels ); $i++ ) {
			$prev = $heading_levels[ $i - 1 ];
			$curr = $heading_levels[ $i ];

			if ( $curr > $prev + 1 ) {
				$issues[] = $this->issue(
					'heading_structure',
					'warning',
					sprintf(
						/* translators: 1: heading that was skipped from, 2: heading skipped to */
						__( 'Heading structure skips from H%1$d to H%2$d. Headings should not skip levels.', 'site-inspector' ),
						$prev,
						$curr
					),
					array( 'from' => $prev, 'to' => $curr )
				);
				break;
			}
		}

		return $issues;
	}

	private function check_missing_headings( $post, $content ) {
		$word_count = str_word_count( wp_strip_all_tags( $content ) );

		if ( $word_count < 300 ) {
			return null;
		}

		$headings = $this->get_headings_from_content( $content );

		$has_subheadings = false;
		foreach ( $headings as $h ) {
			if ( in_array( $h['tag'], array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
				$has_subheadings = true;
				break;
			}
		}

		if ( ! $has_subheadings ) {
			return $this->issue(
				'missing_headings',
				'warning',
				sprintf(
					/* translators: word count */
					__( 'Content has %d words but no subheadings (H2–H6). Add headings to improve readability.', 'site-inspector' ),
					$word_count
				),
				array( 'word_count' => $word_count )
			);
		}

		return null;
	}
}
