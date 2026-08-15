<?php
declare(strict_types=1);

/**
 * Mini cart (drawer) coupon field visibility.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the coupon form should appear in the cart drawer.
 *
 * Cart page and checkout keep their own coupon UIs regardless of this setting.
 */
function tailwindscore_cart_drawer_coupon_is_enabled(): bool {
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return false;
	}

	$enabled = (bool) tailwindscore_mod( 'cart_drawer_coupon_enable', true );

	/**
	 * Filter whether the mini cart drawer shows a coupon input.
	 *
	 * @param bool $enabled Theme setting value.
	 */
	return (bool) apply_filters( 'tailwindscore_cart_drawer_show_coupon', $enabled );
}

/**
 * Whether purchase-flow coupon handlers should initialize on this request.
 *
 * Used to skip coupon event bindings on pages that never render a coupon form.
 * Note: coupon logic still ships in the main app bundle unless code-split separately.
 */
function tailwindscore_purchase_flow_needs_coupon_js(): bool {
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return false;
	}

	if ( tailwindscore_cart_drawer_coupon_is_enabled() ) {
		return true;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	/**
	 * Filter whether coupon JS should initialize on this request.
	 *
	 * @param bool $needs Default false when no coupon UI is rendered.
	 */
	return (bool) apply_filters( 'tailwindscore_purchase_flow_needs_coupon_js', false );
}

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_cart_drawer_coupon_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_cart_settings',
		array(
			'title'       => esc_html__( '购物车', 'tailwindscore' ),
			'description' => esc_html__( '迷你购物车抽屉与购物车页选项。', 'tailwindscore' ),
			'priority'    => function_exists( 'tailwindscore_kirki_root_priority' ) ? tailwindscore_kirki_root_priority( 'cart' ) : 70,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'cart_drawer_coupon_enable',
			'label'       => esc_html__( 'Show coupon field in mini cart', 'tailwindscore' ),
			'description' => esc_html__( 'Hides the discount code input in the cart drawer. Cart page and checkout coupon fields are not affected.', 'tailwindscore' ),
			'section'     => 'tailwindscore_cart_settings',
			'default'     => true,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_cart_drawer_coupon_kirki_register', 10, 2 );
