<?php
declare(strict_types=1);

/**
 * 评论图片模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_REVIEW_IMAGES_VERSION', '0.1.1' );

$tailwindscore_review_images_dir = __DIR__;

require_once $tailwindscore_review_images_dir . '/kirki.php';
require_once $tailwindscore_review_images_dir . '/includes/class-review-images-settings.php';
require_once $tailwindscore_review_images_dir . '/includes/class-review-images-upload.php';
require_once $tailwindscore_review_images_dir . '/includes/class-review-images-frontend.php';
require_once $tailwindscore_review_images_dir . '/includes/class-showcase-gallery.php';
require_once $tailwindscore_review_images_dir . '/includes/class-review-images-admin.php';

/**
 * @return void
 */
function tailwindscore_review_images_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Review_Images_Upload();
	new Tailwindscore_Review_Images_Frontend();
	new Tailwindscore_Showcase_Gallery();
	new Tailwindscore_Review_Images_Admin();
}
add_action( 'after_setup_theme', 'tailwindscore_review_images_module_init', 25 );
