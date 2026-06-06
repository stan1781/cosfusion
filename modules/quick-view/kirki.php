<?php
declare(strict_types=1);

/**
 * Kirki: Quick View module.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_quick_view_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_quick_view',
		array(
			'title'       => esc_html__( 'Quick view', 'tailwindscore' ),
			'description' => esc_html__( 'Archive and homepage quick view modal for variable products.', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 68,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_quick_view_enabled',
			'label'    => esc_html__( 'Enable quick view', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_quick_view',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'checkbox',
			'settings' => 'product_quick_view_target_pages',
			'label'    => esc_html__( 'Active on pages', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_quick_view',
			'default'  => array( 'shop_archive', 'homepage', 'single_product' ),
			'choices'  => array(
				'shop_archive'   => esc_html__( 'Shop / category / tag archives', 'tailwindscore' ),
				'homepage'       => esc_html__( 'Homepage', 'tailwindscore' ),
				'single_product' => esc_html__( 'Single product (related products loops)', 'tailwindscore' ),
			),
			'active_callback' => array(
				array(
					'setting'  => 'product_quick_view_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_quick_view_kirki_register', 10, 2 );
