<?php
declare(strict_types=1);

/**
 * Review image upload AJAX handlers.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles review image upload, delete, and cleanup.
 */
class Tailwindscore_Review_Images_Upload {

	/**
	 * @return void
	 */
	public function __construct() {
		if ( ! Tailwindscore_Review_Images_Settings::is_enabled() ) {
			return;
		}

		add_action( 'wp_ajax_upload_review_image', array( $this, 'ajax_upload_image' ) );
		add_action( 'wp_ajax_nopriv_upload_review_image', array( $this, 'ajax_upload_image' ) );
		add_action( 'wp_ajax_delete_review_image', array( $this, 'ajax_delete_review_image' ) );
		add_action( 'wp_ajax_nopriv_delete_review_image', array( $this, 'ajax_delete_review_image' ) );
		add_action( 'wp_ajax_cleanup_review_images', array( $this, 'ajax_cleanup_review_images' ) );
		add_action( 'wp_ajax_nopriv_cleanup_review_images', array( $this, 'ajax_cleanup_review_images' ) );
		add_action( 'wp_ajax_reset_staged_review_images', array( $this, 'ajax_reset_staged_review_images' ) );
		add_action( 'wp_ajax_nopriv_reset_staged_review_images', array( $this, 'ajax_reset_staged_review_images' ) );
	}

	/**
	 * @return void
	 */
	public function ajax_upload_image(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) ), 'review_image_upload' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'tailwindscore' ) ) );
		}

		$product_id = 0;
		if ( isset( $_POST['product_id'] ) ) {
			$product_id = (int) $_POST['product_id'];
		} elseif ( is_product() ) {
			$product_id = (int) get_the_ID();
		}

		if ( $product_id > 0 ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error( array( 'message' => __( 'Invalid product', 'tailwindscore' ) ) );
			}
			if ( ! $product->get_reviews_allowed() ) {
				wp_send_json_error( array( 'message' => __( 'This product does not allow reviews', 'tailwindscore' ) ) );
			}
		}

		if ( ! isset( $_FILES['image'] ) || UPLOAD_ERR_OK !== (int) $_FILES['image']['error'] ) {
			wp_send_json_error( array( 'message' => __( 'File upload failed', 'tailwindscore' ) ) );
		}

		$file          = $_FILES['image']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/webp' );
		$mime_type     = ! empty( $file['type'] ) ? strtolower( (string) $file['type'] ) : '';
		$file_type     = wp_check_filetype( (string) $file['name'] );
		$ext_type      = ! empty( $file_type['type'] ) ? strtolower( (string) $file_type['type'] ) : '';

		$is_valid = ( $mime_type && in_array( $mime_type, $allowed_types, true ) )
			|| ( $ext_type && in_array( $ext_type, $allowed_types, true ) );

		if ( ! $is_valid ) {
			wp_send_json_error( array( 'message' => __( 'Unsupported file format. Only JPG, PNG, and WebP are allowed', 'tailwindscore' ) ) );
		}

		if ( (int) $file['size'] > Tailwindscore_Review_Images_Settings::get_max_file_size() ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: max size */
						__( 'File size exceeds the limit (max %s MB)', 'tailwindscore' ),
						round( Tailwindscore_Review_Images_Settings::get_max_file_size() / 1048576, 1 )
					),
				)
			);
		}

		$max_images = Tailwindscore_Review_Images_Settings::get_max_images();
		if ( $this->get_effective_staged_count() >= $max_images ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: max images */
						__( 'You can upload up to %d images', 'tailwindscore' ),
						$max_images
					),
				)
			);
		}

		$result = $this->process_and_upload_image( $file );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'attachment_id' => $result['attachment_id'],
				'url'           => $result['url'],
				'thumbnail_url' => $result['thumbnail_url'],
			)
		);
	}

	/**
	 * @param array<string, mixed> $file Uploaded file array.
	 * @return array<string, mixed>|WP_Error
	 */
	private function process_and_upload_image( array $file ) {
		$image_info = @getimagesize( $file['tmp_name'] );
		if ( ! $image_info ) {
			return new WP_Error( 'invalid_image', __( 'Invalid image file', 'tailwindscore' ) );
		}

		$new_dimensions = $this->calculate_new_dimensions( (int) $image_info[0], (int) $image_info[1] );
		$tmp_processed  = wp_tempnam( 'review_image_' ) . '.jpg';

		$resize_result = $this->resize_image(
			(string) $file['tmp_name'],
			$tmp_processed,
			$new_dimensions['width'],
			$new_dimensions['height']
		);

		if ( is_wp_error( $resize_result ) ) {
			@unlink( $tmp_processed );
			return $resize_result;
		}

		$webp_path         = false;
		$final_file_path   = $tmp_processed;
		$final_extension   = 'jpg';

		if ( function_exists( 'wp_enhanced_convert_image_to_webp' ) ) {
			$webp_path = wp_enhanced_convert_image_to_webp(
				$tmp_processed,
				array(
					'context'    => 'review',
					'size_check' => true,
				)
			);
		}

		if ( $webp_path && file_exists( $webp_path ) && $webp_path !== $tmp_processed ) {
			$final_file_path = $webp_path;
			$final_extension = 'webp';
			@unlink( $tmp_processed );
		}

		if ( ! file_exists( $final_file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'Processed file not found', 'tailwindscore' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $upload_dir['error'] );
		}

		$file_name        = 'review-' . gmdate( 'YmdHis' ) . '-' . wp_generate_password( 6, false, false ) . '.' . $final_extension;
		$unique_file_name = wp_unique_filename( $upload_dir['path'], $file_name );
		$destination_file = $upload_dir['path'] . '/' . $unique_file_name;

		if ( ! @copy( $final_file_path, $destination_file ) ) {
			return new WP_Error( 'file_copy_failed', __( 'Failed to copy file to upload directory', 'tailwindscore' ) );
		}

		@unlink( $final_file_path );

		$wp_filetype = wp_check_filetype( $unique_file_name, null );
		if ( empty( $wp_filetype['ext'] ) || empty( $wp_filetype['type'] ) ) {
			@unlink( $destination_file );
			return new WP_Error( 'invalid_file_type', __( 'Invalid file type', 'tailwindscore' ) );
		}

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $unique_file_name ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_author'    => get_current_user_id(),
		);

		$attachment_id = wp_insert_attachment( $attachment, $destination_file );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $destination_file );
			return $attachment_id;
		}

		$attach_data = wp_generate_attachment_metadata( $attachment_id, $destination_file );
		wp_update_attachment_metadata( $attachment_id, $attach_data );
		update_post_meta( $attachment_id, '_review_image', true );

		$attachment_url = $upload_dir['url'] . '/' . $unique_file_name;
		$thumbnail_url  = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		return array(
			'attachment_id' => $attachment_id,
			'url'           => $attachment_url,
			'thumbnail_url' => $thumbnail_url ? $thumbnail_url : $attachment_url,
		);
	}

	/**
	 * @param int $width  Original width.
	 * @param int $height Original height.
	 * @return array{width: int, height: int}
	 */
	private function calculate_new_dimensions( int $width, int $height ): array {
		$max_width  = Tailwindscore_Review_Images_Settings::get_max_width();
		$max_height = Tailwindscore_Review_Images_Settings::get_max_height();

		if ( $width <= $max_width && $height <= $max_height ) {
			return array(
				'width'  => $width,
				'height' => $height,
			);
		}

		$ratio = min( $max_width / $width, $max_height / $height );

		return array(
			'width'  => (int) round( $width * $ratio ),
			'height' => (int) round( $height * $ratio ),
		);
	}

	/**
	 * @param string $source_path      Source.
	 * @param string $destination_path Destination.
	 * @param int    $target_width     Width.
	 * @param int    $target_height    Height.
	 * @return true|WP_Error
	 */
	private function resize_image( string $source_path, string $destination_path, int $target_width, int $target_height ) {
		if ( ! extension_loaded( 'gd' ) && ! extension_loaded( 'imagick' ) ) {
			return new WP_Error( 'no_image_library', __( 'GD or Imagick extension is not installed on the server', 'tailwindscore' ) );
		}

		$image_info = @getimagesize( $source_path );
		if ( ! $image_info ) {
			return new WP_Error( 'invalid_image', __( 'Unable to read image information', 'tailwindscore' ) );
		}

		if ( extension_loaded( 'imagick' ) ) {
			return $this->resize_with_imagick( $source_path, $destination_path, $target_width, $target_height );
		}

		return $this->resize_with_gd( $source_path, $destination_path, $target_width, $target_height, (string) $image_info['mime'] );
	}

	/**
	 * @param string $source_path      Source.
	 * @param string $destination_path Destination.
	 * @param int    $target_width     Width.
	 * @param int    $target_height    Height.
	 * @return true|WP_Error
	 */
	private function resize_with_imagick( string $source_path, string $destination_path, int $target_width, int $target_height ) {
		try {
			$imagick = new Imagick( $source_path );
			$imagick->thumbnailImage( $target_width, $target_height, true, true );
			$imagick->writeImage( $destination_path );
			$imagick->destroy();
			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'resize_failed', __( 'Failed to resize image', 'tailwindscore' ) );
		}
	}

	/**
	 * @param string $source_path      Source.
	 * @param string $destination_path Destination.
	 * @param int    $target_width     Width.
	 * @param int    $target_height    Height.
	 * @param string $mime_type        MIME type.
	 * @return true|WP_Error
	 */
	private function resize_with_gd( string $source_path, string $destination_path, int $target_width, int $target_height, string $mime_type ) {
		switch ( $mime_type ) {
			case 'image/jpeg':
				$source_image = @imagecreatefromjpeg( $source_path );
				break;
			case 'image/png':
				$source_image = @imagecreatefrompng( $source_path );
				break;
			case 'image/webp':
				$source_image = @imagecreatefromwebp( $source_path );
				break;
			default:
				return new WP_Error( 'unsupported_format', __( 'Unsupported image format', 'tailwindscore' ) );
		}

		if ( ! $source_image ) {
			return new WP_Error( 'image_create_failed', __( 'Unable to create image resource', 'tailwindscore' ) );
		}

		$new_image = imagecreatetruecolor( $target_width, $target_height );
		if ( in_array( $mime_type, array( 'image/png', 'image/webp' ), true ) ) {
			imagealphablending( $new_image, false );
			imagesavealpha( $new_image, true );
			$transparent = imagecolorallocatealpha( $new_image, 0, 0, 0, 127 );
			imagefill( $new_image, 0, 0, $transparent );
		}

		imagecopyresampled(
			$new_image,
			$source_image,
			0,
			0,
			0,
			0,
			$target_width,
			$target_height,
			imagesx( $source_image ),
			imagesy( $source_image )
		);

		$result = @imagejpeg( $new_image, $destination_path, 90 );
		imagedestroy( $source_image );
		imagedestroy( $new_image );

		if ( ! $result ) {
			return new WP_Error( 'save_failed', __( 'Failed to save image', 'tailwindscore' ) );
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function ajax_delete_review_image(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) ), 'review_image_upload' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'tailwindscore' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid image ID', 'tailwindscore' ) ) );
		}

		$is_review_image   = (bool) get_post_meta( $attachment_id, '_review_image', true );
		$linked_comment_id = get_post_meta( $attachment_id, '_review_comment_id', true );

		if ( ! $is_review_image || ! empty( $linked_comment_id ) ) {
			wp_send_json_error( array( 'message' => __( 'The image is locked or not a review image', 'tailwindscore' ) ) );
		}

		if ( wp_delete_attachment( $attachment_id, true ) ) {
			wp_send_json_success();
		}

		wp_send_json_error( array( 'message' => __( 'Delete failed', 'tailwindscore' ) ) );
	}

	/**
	 * @return void
	 */
	public function ajax_cleanup_review_images(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) ), 'review_image_upload' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'tailwindscore' ) ) );
		}

		$ids_raw = isset( $_POST['ids'] ) ? wp_unslash( (string) $_POST['ids'] ) : '';
		$ids     = array();

		if ( '' !== $ids_raw ) {
			$decoded = json_decode( $ids_raw, true );
			if ( is_array( $decoded ) ) {
				$ids = array_map( 'absint', $decoded );
			}
		}

		$deleted_count = 0;
		foreach ( $ids as $attachment_id ) {
			if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			$is_review_image   = (bool) get_post_meta( $attachment_id, '_review_image', true );
			$linked_comment_id = get_post_meta( $attachment_id, '_review_comment_id', true );

			if ( $is_review_image && empty( $linked_comment_id ) && wp_delete_attachment( $attachment_id, true ) ) {
				++$deleted_count;
			}
		}

		wp_send_json_success( array( 'deleted' => $deleted_count ) );
	}

	/**
	 * Clear unlinked staged review images for the current visitor (fresh form session).
	 *
	 * @return void
	 */
	public function ajax_reset_staged_review_images(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) ), 'review_image_upload' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'tailwindscore' ) ) );
		}

		$keep_ids = $this->parse_staged_ids_from_request();
		$deleted  = $this->delete_orphan_staged_review_images( $keep_ids );

		wp_send_json_success( array( 'deleted' => $deleted ) );
	}

	/**
	 * @return int[]
	 */
	private function parse_staged_ids_from_request(): array {
		if ( ! isset( $_POST['staged_ids'] ) ) {
			return array();
		}

		$decoded = json_decode( wp_unslash( (string) $_POST['staged_ids'] ), true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $decoded ) ) );
	}

	/**
	 * @param int[] $ids Attachment IDs from the client session.
	 * @return int[]
	 */
	private function filter_valid_staged_ids( array $ids ): array {
		$user_id = get_current_user_id();
		$valid   = array();

		foreach ( $ids as $attachment_id ) {
			if ( ! $this->is_orphan_review_attachment_for_user( $attachment_id, $user_id ) ) {
				continue;
			}
			$valid[] = $attachment_id;
		}

		return array_values( array_unique( $valid ) );
	}

	/**
	 * @return int
	 */
	private function get_effective_staged_count(): int {
		$client_ids = $this->parse_staged_ids_from_request();
		if ( ! empty( $client_ids ) || isset( $_POST['staged_ids'] ) ) {
			return count( $this->filter_valid_staged_ids( $client_ids ) );
		}

		return $this->count_staged_review_images_for_user();
	}

	/**
	 * @param int $attachment_id Attachment ID.
	 * @param int $user_id       Current user ID.
	 * @return bool
	 */
	private function is_orphan_review_attachment_for_user( int $attachment_id, int $user_id ): bool {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		if ( ! get_post_meta( $attachment_id, '_review_image', true ) ) {
			return false;
		}

		if ( get_post_meta( $attachment_id, '_review_comment_id', true ) ) {
			return false;
		}

		$attachment_author = (int) get_post_field( 'post_author', $attachment_id );

		if ( $user_id > 0 ) {
			return $attachment_author === $user_id
				|| 0 === $attachment_author
				|| current_user_can( 'moderate_comments' );
		}

		return 0 === $attachment_author;
	}

	/**
	 * @param int[] $keep_ids IDs to preserve.
	 * @return int Number deleted.
	 */
	private function delete_orphan_staged_review_images( array $keep_ids = array() ): int {
		$user_id       = get_current_user_id();
		$keep_lookup   = array_fill_keys( $keep_ids, true );
		$deleted_count = 0;

		$query = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'inherit',
				'posts_per_page'         => 100,
				'fields'                 => 'ids',
				'author'                 => $user_id > 0 ? $user_id : 0,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'date_query'             => array(
					array( 'after' => '2 hours ago' ),
				),
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'   => '_review_image',
						'value' => '1',
					),
					array(
						'key'     => '_review_comment_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		foreach ( (array) $query->posts as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			if ( isset( $keep_lookup[ $attachment_id ] ) ) {
				continue;
			}

			if ( wp_delete_attachment( $attachment_id, true ) ) {
				++$deleted_count;
			}
		}

		wp_reset_postdata();

		return $deleted_count;
	}

	/**
	 * Count unlinked review images staged by the current visitor.
	 *
	 * @return int
	 */
	private function count_staged_review_images_for_user(): int {
		$user_id = get_current_user_id();
		$query   = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'inherit',
				'posts_per_page'         => Tailwindscore_Review_Images_Settings::get_max_images() + 1,
				'fields'                 => 'ids',
				'author'                 => $user_id > 0 ? $user_id : 0,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'date_query'             => array(
					array( 'after' => '2 hours ago' ),
				),
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'   => '_review_image',
						'value' => '1',
					),
					array(
						'key'     => '_review_comment_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return is_array( $query->posts ) ? count( $query->posts ) : 0;
	}
}
