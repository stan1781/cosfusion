<?php
declare(strict_types=1);

/**
 * Kirki: review images module.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_review_images_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_review_images',
		array(
			'title'       => esc_html__( 'Review images', 'tailwindscore' ),
			'description' => esc_html__( 'Customer review photo uploads on single product pages.', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 68,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_review_images_enabled',
			'label'    => esc_html__( 'Enable review images', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'product_review_images_max_images',
			'label'    => esc_html__( 'Maximum images per review', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => 6,
			'choices'  => array(
				'min'  => 1,
				'max'  => 10,
				'step' => 1,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_review_images_max_file_size_mb',
			'label'       => esc_html__( 'Maximum file size (MB)', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_review_images',
			'default'     => 5,
			'choices'     => array(
				'min'  => 1,
				'max'  => 20,
				'step' => 1,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'product_review_images_max_width',
			'label'    => esc_html__( 'Max image width (px)', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => 1000,
			'choices'  => array(
				'min'  => 200,
				'max'  => 2000,
				'step' => 50,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'product_review_images_max_height',
			'label'    => esc_html__( 'Max image height (px)', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => 1000,
			'choices'  => array(
				'min'  => 200,
				'max'  => 2000,
				'step' => 50,
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_review_images_kirki_register', 10, 2 );
