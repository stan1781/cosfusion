<?php
declare(strict_types=1);

/**
 * Conditional shipping rate visibility (e.g. hide paid flat rate when cart qualifies for free shipping).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minimum discounted subtotal (excludes shipping/taxes) to qualify.
 */
function tailwindscore_shipping_hide_rates_is_enabled(): bool {
	$enabled = (bool) tailwindscore_mod( 'shipping_hide_rates_enable', true );

	/**
	 * Filter whether paid shipping rates are hidden when the cart meets the threshold.
	 *
	 * @param bool $enabled Theme setting value.
	 */
	return (bool) apply_filters( 'tailwindscore_shipping_hide_rates_enable', $enabled );
}

/**
 * Parse a comma- or newline-separated list of WooCommerce shipping rate IDs.
 *
 * @param string $raw Raw setting value.
 * @return string[]
 */
function tailwindscore_parse_shipping_rate_id_list( string $raw ): array {
	$parts = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $parts ) ) {
		return array();
	}

	$rate_ids = array();

	foreach ( $parts as $part ) {
		$rate_id = sanitize_text_field( (string) $part );
		if ( '' !== $rate_id ) {
			$rate_ids[] = $rate_id;
		}
	}

	return array_values( array_unique( $rate_ids ) );
}

/**
 * @param mixed $value Unsanitized Customizer value.
 */
function tailwindscore_sanitize_shipping_rate_id_list( $value ): string {
	return implode( ', ', tailwindscore_parse_shipping_rate_id_list( (string) $value ) );
}

/**
 * Minimum discounted subtotal (excludes shipping/taxes) to qualify.
 */
function tailwindscore_free_shipping_threshold(): float {
	$threshold = (float) tailwindscore_mod( 'shipping_hide_rates_threshold', 39.99 );

	/**
	 * Filter the cart subtotal threshold for hiding configured shipping rates.
	 *
	 * @param float $threshold Amount in store currency.
	 */
	return (float) apply_filters( 'tailwindscore_free_shipping_threshold', $threshold );
}

/**
 * Shipping rate IDs to remove when the discounted subtotal meets the threshold.
 *
 * Input name `shipping_method_0_flat_rate2` corresponds to rate ID `flat_rate:2`.
 *
 * @return string[]
 */
function tailwindscore_hidden_shipping_rate_ids_when_qualified(): array {
	if ( ! tailwindscore_shipping_hide_rates_is_enabled() ) {
		return array();
	}

	$raw      = (string) tailwindscore_mod( 'shipping_hide_rates_ids', 'flat_rate:2' );
	$rate_ids = tailwindscore_parse_shipping_rate_id_list( $raw );

	/**
	 * Filter shipping rate IDs hidden when the cart qualifies.
	 *
	 * @param string[] $rate_ids WooCommerce rate IDs (e.g. flat_rate:2).
	 */
	return array_values( array_filter( (array) apply_filters( 'tailwindscore_hidden_shipping_rate_ids_when_qualified', $rate_ids ) ) );
}

/**
 * Discounted cart subtotal used for threshold checks (matches post-coupon subtotal display).
 */
function tailwindscore_cart_discounted_subtotal(): float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$cart  = WC()->cart;
	$total = (float) $cart->get_displayed_subtotal();
	$total -= (float) $cart->get_discount_total();

	if ( $cart->display_prices_including_tax() ) {
		$total -= (float) $cart->get_discount_tax();
	}

	return (float) wc_format_decimal( $total, wc_get_price_decimals() );
}

/**
 * Whether the cart discounted subtotal meets the free-shipping threshold.
 */
function tailwindscore_cart_meets_free_shipping_threshold(): bool {
	return tailwindscore_cart_discounted_subtotal() >= tailwindscore_free_shipping_threshold();
}

/**
 * @param array<int|string, WC_Shipping_Rate> $rates   Package rates.
 * @param array<string, mixed>                $package Shipping package.
 * @return array<int|string, WC_Shipping_Rate>
 */
function tailwindscore_filter_shipping_rates_by_cart_total( array $rates, array $package ): array {
	if ( ! tailwindscore_cart_meets_free_shipping_threshold() ) {
		return $rates;
	}

	$hidden_ids = tailwindscore_hidden_shipping_rate_ids_when_qualified();

	if ( array() === $hidden_ids ) {
		return $rates;
	}

	foreach ( $hidden_ids as $rate_id ) {
		unset( $rates[ $rate_id ] );
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'tailwindscore_filter_shipping_rates_by_cart_total', 20, 2 );

/**
 * Fall back when a previously chosen rate was removed (e.g. cart crossed the threshold).
 *
 * @param string                               $chosen_method Chosen rate ID.
 * @param array<int|string, WC_Shipping_Rate> $rates         Available rates.
 */
function tailwindscore_fallback_chosen_shipping_method( string $chosen_method, array $rates ): string {
	if ( isset( $rates[ $chosen_method ] ) ) {
		return $chosen_method;
	}

	$first = array_key_first( $rates );

	return is_string( $first ) ? $first : $chosen_method;
}
add_filter( 'woocommerce_shipping_chosen_method', 'tailwindscore_fallback_chosen_shipping_method', 20, 2 );

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_shipping_rules_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'shipping_hide_rates_enable',
			'label'       => esc_html__( 'Hide paid shipping when threshold is met', 'tailwindscore' ),
			'description' => esc_html__( 'Removes selected shipping methods once the discounted cart subtotal reaches the threshold below.', 'tailwindscore' ),
			'section'     => 'tailwindscore_checkout_settings',
			'default'     => true,
			'priority'    => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'number',
			'settings'          => 'shipping_hide_rates_threshold',
			'label'             => esc_html__( 'Hide paid shipping threshold', 'tailwindscore' ),
			'description'       => esc_html__( 'Discounted cart subtotal (after coupons, before shipping and tax). Paid methods are hidden when subtotal is greater than or equal to this amount.', 'tailwindscore' ),
			'section'           => 'tailwindscore_checkout_settings',
			'default'           => 39.99,
			'priority'          => 20,
			'choices'           => array(
				'min'  => 0,
				'step' => 0.01,
			),
			'active_callback'   => array(
				array(
					'setting'  => 'shipping_hide_rates_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'text',
			'settings'          => 'shipping_hide_rates_ids',
			'label'             => esc_html__( 'Shipping rate IDs to hide', 'tailwindscore' ),
			'description'       => esc_html__( 'Comma-separated WooCommerce rate IDs, e.g. flat_rate:2. Find the ID in the shipping method input name: shipping_method_0_flat_rate2 → flat_rate:2.', 'tailwindscore' ),
			'section'           => 'tailwindscore_checkout_settings',
			'default'           => 'flat_rate:2',
			'priority'          => 30,
			'sanitize_callback' => 'tailwindscore_sanitize_shipping_rate_id_list',
			'active_callback'   => array(
				array(
					'setting'  => 'shipping_hide_rates_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_shipping_rules_kirki_register', 10, 2 );
