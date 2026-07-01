<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APCA_Media_Audit extends APCA_Abstract_Audit {

	const CATEGORY = 'media';

	private $settings;

	public function __construct( $settings = array() ) {
		$this->settings = wp_parse_args( $settings, array(
			'large_image_kb' => 500,
		) );
	}

	public function run( $post ) {
		$issues  = array();
		$content = $post->post_content;

		foreach ( $this->check_missing_alt_text( $content ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		foreach ( $this->check_broken_media( $post ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		foreach ( $this->check_large_images( $post ) as $issue ) {
			$issue['category'] = self::CATEGORY;
			$issues[]          = $issue;
		}

		return $issues;
	}

	private function check_missing_alt_text( $content ) {
		$issues = array();
		$images = $this->get_images_from_content( $content );
		$count  = 0;

		foreach ( $images as $img_tag ) {
			$alt = $this->extract_attr( $img_tag, 'alt' );
			if ( '' === trim( $alt ) ) {
				$count++;
			}
		}

		if ( $count > 0 ) {
			$issues[] = $this->issue(
				'missing_alt_text',
				'warning',
				sprintf(
					/* translators: number of images missing alt text */
					_n(
						'%d image is missing alt text.',
						'%d images are missing alt text.',
						$count,
						'auditpilot-content-audit'
					),
					$count
				),
				array( 'count' => $count )
			);
		}

		return $issues;
	}

	private function check_broken_media( $post ) {
		$issues = array();

		$attachment_ids = $this->get_attachment_ids_in_content( $post->post_content );

		foreach ( $attachment_ids as $attachment_id ) {
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				$issues[] = $this->issue(
					'broken_media',
					'error',
					sprintf(
						/* translators: attachment ID */
						__( 'Media reference (ID: %d) no longer exists.', 'auditpilot-content-audit' ),
						$attachment_id
					),
					array( 'attachment_id' => $attachment_id )
				);
				continue;
			}

			$file = get_attached_file( $attachment_id );
			if ( $file && ! file_exists( $file ) ) {
				$issues[] = $this->issue(
					'broken_media',
					'error',
					sprintf(
						/* translators: attachment ID */
						__( 'Media file (ID: %d) is missing from the server.', 'auditpilot-content-audit' ),
						$attachment_id
					),
					array( 'attachment_id' => $attachment_id, 'file' => basename( $file ) )
				);
			}
		}

		return $issues;
	}

	private function check_large_images( $post ) {
		$issues    = array();
		$threshold = (int) $this->settings['large_image_kb'] * 1024;

		$attachment_ids = $this->get_attachment_ids_in_content( $post->post_content );

		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$attachment_ids[] = (int) $thumbnail_id;
		}

		$attachment_ids = array_unique( $attachment_ids );

		foreach ( $attachment_ids as $attachment_id ) {
			$file = get_attached_file( $attachment_id );
			if ( ! $file || ! file_exists( $file ) ) {
				continue;
			}

			$mime = get_post_mime_type( $attachment_id );
			if ( ! $mime || strpos( $mime, 'image/' ) !== 0 ) {
				continue;
			}

			$size = filesize( $file );
			if ( $size > $threshold ) {
				$size_kb = round( $size / 1024 );
				$issues[] = $this->issue(
					'large_image',
					'warning',
					sprintf(
						/* translators: 1: image name, 2: size in KB, 3: threshold in KB */
						__( 'Image "%1$s" is large (%2$d KB). Consider optimizing (recommended: under %3$d KB).', 'auditpilot-content-audit' ),
						basename( $file ),
						$size_kb,
						(int) $this->settings['large_image_kb']
					),
					array(
						'attachment_id' => $attachment_id,
						'size_kb'       => $size_kb,
						'threshold_kb'  => (int) $this->settings['large_image_kb'],
					)
				);
			}
		}

		return $issues;
	}

	private function get_attachment_ids_in_content( $content ) {
		$ids = array();

		if ( preg_match_all( '/wp-image-(\d+)/', $content, $matches ) ) {
			foreach ( $matches[1] as $id ) {
				$ids[] = (int) $id;
			}
		}

		if ( preg_match_all( '/data-id=["\'](\d+)["\']/', $content, $matches ) ) {
			foreach ( $matches[1] as $id ) {
				$ids[] = (int) $id;
			}
		}

		return array_unique( $ids );
	}
}
