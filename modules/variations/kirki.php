<?php
declare(strict_types=1);

/**
 * Kirki: variation selector module.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg   Kirki config id.
 * @param string $panel Kirki panel id.
 */
function tailwindscore_variations_kirki_register( string $cfg, string $panel ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_variations',
		array(
			'title'       => esc_html__( 'Variation selector', 'tailwindscore' ),
			'description' => esc_html__( 'Custom swatches and layout on single product variable forms.', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 66,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'variation_selector_enabled',
			'label'    => esc_html__( 'Enable custom variation selector', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'variation_color_attributes',
			'label'    => esc_html__( 'Color attribute names', 'tailwindscore' ),
			'description' => esc_html__( 'Comma-separated. Shown as image swatches.', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'pa_color,color,pa_style,style',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'variation_size_attributes',
			'label'    => esc_html__( 'Size attribute names', 'tailwindscore' ),
			'description' => esc_html__( 'Comma-separated. Used for row styling; Size Guide hooks later.', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'pa_size,size',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'radio-buttonset',
			'settings' => 'variation_color_default_mode',
			'label'    => esc_html__( 'Color selector default layout', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'grid',
			'choices'  => array(
				'grid' => esc_html__( 'Grid', 'tailwindscore' ),
				'list' => esc_html__( 'List', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'radio-buttonset',
			'settings' => 'variation_color_shape',
			'label'    => esc_html__( 'Color swatch shape', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'square',
			'choices'  => array(
				'square' => esc_html__( 'Square', 'tailwindscore' ),
				'round'  => esc_html__( 'Round', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'variation_grid_show_text',
			'label'    => esc_html__( 'Show labels in grid mode', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_variations_kirki_register', 10, 2 );
