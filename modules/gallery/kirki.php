<?php
declare(strict_types=1);

/**
 * Kirki: product gallery module.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg   Kirki config id.
 * @param string $panel Kirki panel id.
 */
function tailwindscore_gallery_kirki_register( string $cfg, string $panel ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_gallery',
		array(
			'title'       => esc_html__( 'Product gallery', 'tailwindscore' ),
			'description' => esc_html__( 'Single product Swiper gallery.', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 65,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_gallery_thumb_limit',
			'label'       => esc_html__( 'Thumbnail strip limit (desktop)', 'tailwindscore' ),
			'description' => esc_html__( 'Max visible thumbnails before scrolling.', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_gallery',
			'default'     => 6,
			'choices'     => array(
				'min'  => 3,
				'max'  => 12,
				'step' => 1,
			),
			'sanitize_callback' => 'absint',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'product_gallery_show_counter',
			'label'       => esc_html__( 'Show slide counter', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_gallery',
			'default'     => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_gallery_variation_max_images',
			'label'       => esc_html__( 'Max images per variation (admin)', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_gallery',
			'default'     => 10,
			'choices'     => array(
				'min'  => 1,
				'max'  => 20,
				'step' => 1,
			),
			'sanitize_callback' => 'absint',
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_gallery_kirki_register', 10, 2 );
