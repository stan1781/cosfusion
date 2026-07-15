<?php
declare(strict_types=1);

/**
 * 评论图片设置与前台配置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 评论图片配置访问器。
 */
class Tailwindscore_Review_Images_Settings {

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$value = tailwindscore_mod( 'product_review_images_enabled', true );
		if ( null === $value || '' === $value ) {
			return true;
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @return int
	 */
	public static function get_max_images(): int {
		return max( 1, (int) tailwindscore_mod( 'product_review_images_max_images', 6 ) );
	}

	/**
	 * @return int 字节数。
	 */
	public static function get_max_file_size(): int {
		$mb = (int) tailwindscore_mod( 'product_review_images_max_file_size_mb', 5 );
		$mb = max( 1, min( 20, $mb ) );
		return $mb * 1048576;
	}

	/**
	 * @return int
	 */
	public static function get_max_width(): int {
		return max( 100, (int) tailwindscore_mod( 'product_review_images_max_width', 1000 ) );
	}

	/**
	 * @return int
	 */
	public static function get_max_height(): int {
		return max( 100, (int) tailwindscore_mod( 'product_review_images_max_height', 1000 ) );
	}
}

/**
 * 评论图片的前台配置 payload。
 *
 * @return array{reviewImages: array<string, mixed>, i18n: array<string, string>}
 */
function tailwindscore_review_images_config(): array {
	return array(
		'reviewImages' => array(
			'enabled'     => Tailwindscore_Review_Images_Settings::is_enabled(),
			'maxImages'   => Tailwindscore_Review_Images_Settings::get_max_images(),
			'maxFileSize' => Tailwindscore_Review_Images_Settings::get_max_file_size(),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'icons'       => array(
				'close' => tailwindscore_icon( 'close' ),
			),
		),
		'i18n'         => array(
			'uploading'          => __( 'Uploading…', 'tailwindscore' ),
			'uploadFailed'       => __( 'Upload failed', 'tailwindscore' ),
			'maxImagesReached'   => sprintf(
				/* translators: %d: max images */
				__( 'You can upload up to %d images', 'tailwindscore' ),
				Tailwindscore_Review_Images_Settings::get_max_images()
			),
			'invalidFileType'    => __( 'Unsupported file type', 'tailwindscore' ),
			'fileTooLarge'       => __( 'File too large', 'tailwindscore' ),
			'remove'             => __( 'Remove', 'tailwindscore' ),
			'addPhotos'          => __( 'Add photos', 'tailwindscore' ),
			'selectImages'       => __( 'Click to select images', 'tailwindscore' ),
			'dragHere'           => __( 'or drag files here', 'tailwindscore' ),
			'closeLightbox'      => __( 'Close', 'tailwindscore' ),
			'previousImage'      => __( 'Previous image', 'tailwindscore' ),
			'nextImage'          => __( 'Next image', 'tailwindscore' ),
			'verifiedOwner'      => __( 'Verified owner', 'tailwindscore' ),
		),
	);
}

if ( ! function_exists( 'wc_review_get_country_info' ) ) {
	/**
	 * 国家信息辅助函数（与插件模板向后兼容）。
	 *
	 * @param string $country_code ISO 3166-1 alpha-2。
	 * @return array{code: string, name: string, flag_url: string}|false
	 */
	function wc_review_get_country_info( string $country_code ) {
		if ( ! class_exists( 'Tailwindscore_Review_Images_Frontend' ) ) {
			return false;
		}
		return Tailwindscore_Review_Images_Frontend::get_country_info( $country_code );
	}
}
