<?php
declare(strict_types=1);

/**
 * 条件性运费可见性（例如购物车满足免邮时隐藏付费 flat rate）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 满足条件的最低折后小计（不含运费 / 税费）。
 */
function tailwindscore_shipping_hide_rates_is_enabled(): bool {
	$enabled = (bool) tailwindscore_mod( 'shipping_hide_rates_enable', true );

	/**
	 * 过滤购物车达到阈值时是否隐藏付费运费。
	 *
	 * @param bool $enabled 主题设置值。
	 */
	return (bool) apply_filters( 'tailwindscore_shipping_hide_rates_enable', $enabled );
}

/**
 * 解析逗号或换行分隔的 WooCommerce 运费 rate ID 列表。
 *
 * @param string $raw 原始设置值。
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
 * @param mixed $value 未消毒的 Customizer 值。
 */
function tailwindscore_sanitize_shipping_rate_id_list( $value ): string {
	return implode( ', ', tailwindscore_parse_shipping_rate_id_list( (string) $value ) );
}

/**
 * 满足条件的最低折后小计（不含运费 / 税费）。
 */
function tailwindscore_free_shipping_threshold(): float {
	$threshold = (float) tailwindscore_mod( 'shipping_hide_rates_threshold', 39.99 );

	/**
	 * 过滤隐藏已配置运费时的购物车小计阈值。
	 *
	 * @param float $threshold 商店货币金额。
	 */
	return (float) apply_filters( 'tailwindscore_free_shipping_threshold', $threshold );
}

/**
 * 折后小计达到阈值时要移除的运费 rate ID。
 *
 * 输入名 `shipping_method_0_flat_rate2` 对应 rate ID `flat_rate:2`。
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
	 * 过滤购物车满足条件时隐藏的运费 rate ID。
	 *
	 * @param string[] $rate_ids WooCommerce rate ID（例如 flat_rate:2）。
	 */
	return array_values( array_filter( (array) apply_filters( 'tailwindscore_hidden_shipping_rate_ids_when_qualified', $rate_ids ) ) );
}

/**
 * 用于阈值检查的折后购物车小计（与优惠券后小计显示一致）。
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
 * 购物车折后小计是否达到免邮阈值。
 */
function tailwindscore_cart_meets_free_shipping_threshold(): bool {
	return tailwindscore_cart_discounted_subtotal() >= tailwindscore_free_shipping_threshold();
}

/**
 * @param array<int|string, WC_Shipping_Rate> $rates   包裹运费列表。
 * @param array<string, mixed>                $package 配送包裹。
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
 * 先前所选运费被移除时的回退（例如购物车越过阈值）。
 *
 * @param string                               $chosen_method 已选 rate ID。
 * @param array<int|string, WC_Shipping_Rate> $rates         可用运费列表。
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
 * @param string               $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
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
