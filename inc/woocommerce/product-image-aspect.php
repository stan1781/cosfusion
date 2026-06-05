<?php
declare(strict_types=1);

/**
 * Product image aspect ratio from WooCommerce > Customize > Product Images.
 *
 * Mirrors catalog thumbnail cropping (1:1, custom e.g. 3:4, or uncropped).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * CSS aspect-ratio value for product media containers, or null when uncropped.
 */
function tailwindscore_wc_product_image_aspect_ratio_css(): ?string {
	if ( ! function_exists( 'wc_get_image_size' ) ) {
		return '1 / 1';
	}

	$size = wc_get_image_size( 'thumbnail' );

	if ( empty( $size['crop'] ) || empty( $size['height'] ) || empty( $size['width'] ) ) {
		return null;
	}

	$width  = max( 1, (int) $size['width'] );
	$height = max( 1, (int) $size['height'] );

	/**
	 * @param string $css_value e.g. "3 / 4".
	 * @param array{width:int,height:int,crop:int} $size WooCommerce thumbnail size.
	 */
	return (string) apply_filters(
		'tailwindscore_wc_product_image_aspect_ratio_css',
		sprintf( '%d / %d', $width, $height ),
		$size
	);
}

/**
 * Output --ts-product-image-aspect-ratio on :root from WooCommerce customizer settings.
 */
function tailwindscore_enqueue_product_image_aspect_ratio(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$ratio = tailwindscore_wc_product_image_aspect_ratio_css();

	if ( null === $ratio ) {
		$inline = ':root{--ts-product-image-aspect-ratio:auto;}';
	} else {
		$inline = sprintf( ':root{--ts-product-image-aspect-ratio:%s;}', $ratio );
	}

	wp_add_inline_style( 'tailwindscore-app', $inline );
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_enqueue_product_image_aspect_ratio', 25 );
