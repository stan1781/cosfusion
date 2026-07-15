<?php
declare(strict_types=1);

/**
 * 商品画廊模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_GALLERY_VERSION', '0.2.0' );

$tailwindscore_gallery_dir = __DIR__;

require_once $tailwindscore_gallery_dir . '/kirki.php';
require_once $tailwindscore_gallery_dir . '/includes/class-gallery-data.php';
require_once $tailwindscore_gallery_dir . '/includes/class-variation-images.php';
require_once $tailwindscore_gallery_dir . '/includes/class-gallery-assets.php';

/**
 * @return void
 */
function tailwindscore_gallery_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Gallery_Variation_Images();
	new Tailwindscore_Gallery_Assets();
}
add_action( 'after_setup_theme', 'tailwindscore_gallery_module_init', 25 );
