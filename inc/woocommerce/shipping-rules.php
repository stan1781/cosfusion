<?php
declare(strict_types=1);

/**
 * 条件性运费可见性（例如购物车满足免邮时隐藏付费 flat rate）。
 *
 * 门槛金额读取当前配送 zone 内条件免运（Free shipping）实例的 Minimum order amount，
 * 与 Starcore `sc_shipping_tiered_threshold()` 同源逻辑，不在 Customizer 手填。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 购物车达到免运门槛时是否隐藏已配置的付费运费。
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
 * 当前 package 对应 zone 内、带最低订单金额条件的免运实例。
 *
 * @param array<string, mixed> $package
 * @return WC_Shipping_Free_Shipping[]
 */
function tailwindscore_get_delivery_free_shipping_instances( array $package ): array {
	if ( ! class_exists( 'WC_Shipping_Zones' ) || ! class_exists( 'WC_Shipping_Free_Shipping' ) ) {
		return array();
	}

	$zone      = WC_Shipping_Zones::get_zone_matching_package( $package );
	$methods   = $zone->get_shipping_methods( true );
	$instances = array();

	foreach ( $methods as $method ) {
		if ( ! $method instanceof WC_Shipping_Free_Shipping ) {
			continue;
		}

		if ( ! in_array( $method->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
			continue;
		}

		if ( (float) $method->min_amount <= 0 ) {
			continue;
		}

		$instances[] = $method;
	}

	/**
	 * @param WC_Shipping_Free_Shipping[] $instances
	 * @param array<string, mixed>        $package
	 */
	return (array) apply_filters( 'tailwindscore_delivery_free_shipping_instances', $instances, $package );
}

/**
 * 用作门槛参考的免运实例（默认取 zone 内第一个条件免运）。
 *
 * @param array<string, mixed> $package
 */
function tailwindscore_get_free_shipping_threshold_instance( array $package ): ?WC_Shipping_Free_Shipping {
	$instances = tailwindscore_get_delivery_free_shipping_instances( $package );

	if ( array() === $instances ) {
		return null;
	}

	$instance = $instances[0];

	/**
	 * @param WC_Shipping_Free_Shipping   $instance
	 * @param WC_Shipping_Free_Shipping[] $instances
	 * @param array<string, mixed>        $package
	 */
	return apply_filters( 'tailwindscore_free_shipping_threshold_instance', $instance, $instances, $package );
}

/**
 * 免运门槛金额（来自 WooCommerce 免运实例 min_amount / Minimum order amount）。
 *
 * @param array<string, mixed> $package
 */
function tailwindscore_free_shipping_threshold( array $package = array() ): float {
	$instance = tailwindscore_get_free_shipping_threshold_instance( $package );
	if ( ! $instance ) {
		return 0.0;
	}

	$threshold = (float) $instance->min_amount;

	/**
	 * 过滤隐藏已配置运费时的购物车小计阈值。
	 *
	 * @param float                     $threshold 商店货币金额。
	 * @param WC_Shipping_Free_Shipping $instance  参考免运实例。
	 * @param array<string, mixed>      $package   配送包裹。
	 */
	return (float) apply_filters( 'tailwindscore_free_shipping_threshold', $threshold, $instance, $package );
}

/**
 * 当前购物车第一个配送包裹（免运门槛计算用）。
 *
 * @return array<string, mixed>
 */
function tailwindscore_get_cart_shipping_package(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$packages = WC()->cart->get_shipping_packages();

	return isset( $packages[0] ) && is_array( $packages[0] ) ? $packages[0] : array();
}

/**
 * 免运门槛金额的格式化标签（纯文本，已解码实体）。
 *
 * @param array<string, mixed> $package
 */
function tailwindscore_free_shipping_threshold_label( array $package = array() ): string {
	$threshold = tailwindscore_free_shipping_threshold( $package );

	if ( $threshold <= 0 ) {
		return '';
	}

	return tailwindscore_format_price_plain( $threshold );
}

/**
 * 将 wc_price() 输出转为可再 esc_html / escapeHtml 的纯文本金额。
 *
 * wc_price 含 HTML 与 &#36; 等实体；去标签后须解码，否则二次转义会显示字面 &#36;。
 */
function tailwindscore_format_price_plain( float $amount ): string {
	$plain = wp_strip_all_tags( wc_price( $amount ) );

	return html_entity_decode( $plain, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * 距免运门槛还差的金额（不低于 0）。
 *
 * @param array<string, mixed> $package
 */
function tailwindscore_free_shipping_remaining_amount( array $package = array() ): float {
	$instance = tailwindscore_get_free_shipping_threshold_instance( $package );
	if ( ! $instance ) {
		return 0.0;
	}

	$remaining = tailwindscore_free_shipping_threshold( $package ) - tailwindscore_cart_subtotal_for_free_shipping( $instance );

	return max( 0.0, (float) wc_format_decimal( $remaining, wc_get_price_decimals() ) );
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
 * 与 WooCommerce 免运 is_available() 一致的小计算法。
 */
function tailwindscore_cart_subtotal_for_free_shipping( WC_Shipping_Free_Shipping $instance ): float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$total = (float) WC()->cart->get_displayed_subtotal();

	if ( 'no' === $instance->ignore_discounts ) {
		$total -= (float) WC()->cart->get_discount_total();

		if ( WC()->cart->display_prices_including_tax() ) {
			$total -= (float) WC()->cart->get_discount_tax();
		}
	}

	return (float) wc_format_decimal( $total, wc_get_price_decimals() );
}

/**
 * 折后购物车小计（兼容旧调用；新逻辑请用 tailwindscore_cart_subtotal_for_free_shipping）。
 *
 * @deprecated 使用 tailwindscore_cart_subtotal_for_free_shipping()
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
 * 购物车小计是否达到参考免运的 Minimum order amount。
 *
 * @param array<string, mixed> $package
 */
function tailwindscore_cart_meets_free_shipping_threshold( array $package = array() ): bool {
	$instance = tailwindscore_get_free_shipping_threshold_instance( $package );
	if ( ! $instance ) {
		return false;
	}

	return tailwindscore_cart_subtotal_for_free_shipping( $instance ) >= (float) $instance->min_amount;
}

/**
 * @param array<int|string, WC_Shipping_Rate> $rates   包裹运费列表。
 * @param array<string, mixed>                $package 配送包裹。
 * @return array<int|string, WC_Shipping_Rate>
 */
function tailwindscore_filter_shipping_rates_by_cart_total( array $rates, array $package ): array {
	if ( ! tailwindscore_cart_meets_free_shipping_threshold( $package ) ) {
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
			'label'       => esc_html__( '达到门槛时隐藏付费配送', 'tailwindscore' ),
			'description' => esc_html__( '购物车小计达到当前配送区域中条件免运（Free shipping）的 Minimum order amount 后，移除下方所选付费配送方式。门槛金额以 WooCommerce 免运设置为准，无需在此手填。', 'tailwindscore' ),
			'section'     => 'tailwindscore_checkout_settings',
			'default'     => true,
			'priority'    => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'text',
			'settings'          => 'shipping_hide_rates_ids',
			'label'             => esc_html__( '要隐藏的配送费率 ID', 'tailwindscore' ),
			'description'       => esc_html__( '逗号分隔的 WooCommerce 费率 ID，例如 flat_rate:2。可在配送方式 input 名称中找到：shipping_method_0_flat_rate2 → flat_rate:2。', 'tailwindscore' ),
			'section'           => 'tailwindscore_checkout_settings',
			'default'           => 'flat_rate:2',
			'priority'          => 20,
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
