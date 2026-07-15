<?php
declare(strict_types=1);

/**
 * 来自 WooCommerce > 自定义 > 产品图片 的图片宽高比。
 *
 * 与目录缩略图裁剪一致（1:1、自定义如 3:4，或未裁剪）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 商品媒体容器的 CSS aspect-ratio 值；未裁剪时为 null。
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
	 * @param string $css_value 例如 "3 / 4"。
	 * @param array{width:int,height:int,crop:int} $size WooCommerce 缩略图尺寸。
	 */
	return (string) apply_filters(
		'tailwindscore_wc_product_image_aspect_ratio_css',
		sprintf( '%d / %d', $width, $height ),
		$size
	);
}

/**
 * 根据 WooCommerce 自定义设置向 :root 输出 --ts-product-image-aspect-ratio。
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
