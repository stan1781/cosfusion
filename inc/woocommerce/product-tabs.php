<?php
declare(strict_types=1);

/**
 * Single product tabs (Additional information visibility).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the Additional information product tab should render.
 */
function tailwindscore_product_show_additional_information_tab(): bool {
	return (bool) tailwindscore_mod( 'product_show_additional_information', false );
}

/**
 * @param array<string, array<string, mixed>> $tabs Product tabs.
 * @return array<string, array<string, mixed>>
 */
function tailwindscore_filter_product_tabs( array $tabs ): array {
	if ( tailwindscore_product_show_additional_information_tab() ) {
		return $tabs;
	}

	unset( $tabs['additional_information'] );

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'tailwindscore_filter_product_tabs', 98 );

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_product_tabs_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_tabs',
		array(
			'title'       => esc_html__( 'Product tabs', 'tailwindscore' ),
			'description' => esc_html__( 'Control which tabs appear below the product summary.', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 66,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'product_show_additional_information',
			'label'       => esc_html__( 'Show Additional information tab', 'tailwindscore' ),
			'description' => esc_html__( 'Displays product attributes in a table. Hidden by default because most stores rarely use it.', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_tabs',
			'default'     => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_product_tabs_kirki_register', 10, 2 );
