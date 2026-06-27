<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SI_Builder_Audit extends SI_Abstract_Audit {

	const CATEGORY = 'builder';

	public function run( $post ) {
		$issues = array();

		foreach ( $this->check_empty_gutenberg_blocks( $post ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		foreach ( $this->check_empty_elementor( $post ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		return $issues;
	}

	private function check_empty_gutenberg_blocks( $post ) {
		$issues  = array();
		$content = $post->post_content;

		if ( ! has_blocks( $content ) ) {
			return $issues;
		}

		$blocks = parse_blocks( $content );
		$count  = $this->count_empty_blocks( $blocks );

		if ( $count > 0 ) {
			$issues[] = $this->issue(
				'empty_gutenberg_block',
				'info',
				sprintf(
					_n(
						'%d empty Gutenberg block found.',
						'%d empty Gutenberg blocks found.',
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

	private function count_empty_blocks( $blocks ) {
		$count = 0;

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$inner_html = trim( $block['innerHTML'] ?? '' );
			$inner_content = trim( implode( '', $block['innerContent'] ?? array() ) );

			$text = wp_strip_all_tags( $inner_html ?: $inner_content );
			$text = trim( $text );

			$skip_blocks = array(
				'core/spacer',
				'core/separator',
				'core/more',
				'core/nextpage',
				'core/read-more',
			);

			if ( in_array( $block['blockName'], $skip_blocks, true ) ) {
				continue;
			}

			if ( empty( $text ) && empty( $block['innerBlocks'] ) ) {
				$has_media = isset( $block['attrs']['id'] ) || isset( $block['attrs']['url'] ) || isset( $block['attrs']['src'] );
				if ( ! $has_media ) {
					$count++;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$count += $this->count_empty_blocks( $block['innerBlocks'] );
			}
		}

		return $count;
	}

	private function check_empty_elementor( $post ) {
		$issues = array();

		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );

		if ( empty( $elementor_data ) ) {
			return $issues;
		}

		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		if ( ! is_array( $elementor_data ) ) {
			return $issues;
		}

		$empty_count = $this->count_empty_elementor_widgets( $elementor_data );

		if ( $empty_count > 0 ) {
			$issues[] = $this->issue(
				'empty_elementor_widget',
				'info',
				sprintf(
					_n(
						'%d empty Elementor widget found.',
						'%d empty Elementor widgets found.',
						$empty_count,
						'site-inspector'
					),
					$empty_count
				),
				array( 'count' => $empty_count )
			);
		}

		return $issues;
	}

	private function count_empty_elementor_widgets( $elements ) {
		$count = 0;

		foreach ( $elements as $element ) {
			$type        = $element['elType'] ?? '';
			$widget_type = $element['widgetType'] ?? '';
			$settings    = $element['settings'] ?? array();
			$children    = $element['elements'] ?? array();

			if ( 'widget' === $type ) {
				$is_empty = false;

				$text_widgets = array( 'text-editor', 'heading', 'text' );
				if ( in_array( $widget_type, $text_widgets, true ) ) {
					$text = '';
					if ( isset( $settings['editor'] ) ) {
						$text = wp_strip_all_tags( $settings['editor'] );
					} elseif ( isset( $settings['title'] ) ) {
						$text = $settings['title'];
					} elseif ( isset( $settings['text'] ) ) {
						$text = $settings['text'];
					}
					if ( empty( trim( $text ) ) ) {
						$is_empty = true;
					}
				}

				if ( $is_empty ) {
					$count++;
				}
			}

			if ( ! empty( $children ) ) {
				$count += $this->count_empty_elementor_widgets( $children );
			}
		}

		return $count;
	}
}
