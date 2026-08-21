<?php
declare(strict_types=1);

/**
 * 结账区域限制（策略 B：账单或收货任一落在屏蔽州即拒绝下单）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 默认屏蔽的州：国家代码 => 州代码列表。
 *
 * @return array<string, string[]>
 */
function tailwindscore_get_restricted_checkout_states(): array {
	$states = array(
		'US' => array( 'IL' ),
	);

	/**
	 * 过滤结账屏蔽州列表。
	 *
	 * @param array<string, string[]> $states 国家 => 州代码。
	 */
	return (array) apply_filters( 'tailwindscore_restricted_checkout_states', $states );
}

/**
 * 国家 + 州是否属于屏蔽区域。
 */
function tailwindscore_is_restricted_us_state( string $country, string $state ): bool {
	$country = strtoupper( trim( $country ) );
	$state   = strtoupper( trim( $state ) );

	if ( '' === $country || '' === $state ) {
		return false;
	}

	$map = tailwindscore_get_restricted_checkout_states();

	if ( empty( $map[ $country ] ) || ! is_array( $map[ $country ] ) ) {
		return false;
	}

	$blocked = array_map(
		static function ( $code ): string {
			return strtoupper( trim( (string) $code ) );
		},
		$map[ $country ]
	);

	return in_array( $state, $blocked, true );
}

/**
 * 结账字段数组是否命中区域限制（账单或收货任一命中即 true）。
 *
 * @param array<string, mixed> $data 结账字段（billing_* / shipping_*）。
 */
function tailwindscore_checkout_address_hits_restriction( array $data ): bool {
	$billing_country = isset( $data['billing_country'] ) ? (string) $data['billing_country'] : '';
	$billing_state   = isset( $data['billing_state'] ) ? (string) $data['billing_state'] : '';

	if ( tailwindscore_is_restricted_us_state( $billing_country, $billing_state ) ) {
		return true;
	}

	$shipping_country = isset( $data['shipping_country'] ) ? (string) $data['shipping_country'] : '';
	$shipping_state   = isset( $data['shipping_state'] ) ? (string) $data['shipping_state'] : '';

	return tailwindscore_is_restricted_us_state( $shipping_country, $shipping_state );
}

/**
 * 前台结账错误文案。
 */
function tailwindscore_get_checkout_region_restriction_message(): string {
	$message = __(
		'Sorry, we are unable to accept orders with an Illinois billing or shipping address.',
		'tailwindscore'
	);

	/**
	 * 过滤区域限制错误文案。
	 *
	 * @param string $message 英文默认文案。
	 */
	return (string) apply_filters( 'tailwindscore_checkout_region_restriction_message', $message );
}

/**
 * 经典结账：Place order 校验。
 *
 * @param array<string, mixed> $data   提交的结账字段。
 * @param WP_Error             $errors 校验错误容器。
 */
function tailwindscore_validate_checkout_region_restrictions( array $data, WP_Error $errors ): void {
	if ( ! tailwindscore_checkout_address_hits_restriction( $data ) ) {
		return;
	}

	$errors->add(
		'tailwindscore_region_restricted',
		tailwindscore_get_checkout_region_restriction_message()
	);
}
add_action( 'woocommerce_after_checkout_validation', 'tailwindscore_validate_checkout_region_restrictions', 10, 2 );

/**
 * Store API / Blocks / 部分钱包结账：从订单地址拦截。
 *
 * @param WC_Order        $order   草稿或结账订单。
 * @param WP_REST_Request $request Store API 请求。
 *
 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException 命中屏蔽区域时。
 */
function tailwindscore_store_api_reject_restricted_regions( WC_Order $order, $request ): void {
	unset( $request );

	$data = array(
		'billing_country'  => $order->get_billing_country(),
		'billing_state'    => $order->get_billing_state(),
		'shipping_country' => $order->get_shipping_country(),
		'shipping_state'   => $order->get_shipping_state(),
	);

	if ( ! tailwindscore_checkout_address_hits_restriction( $data ) ) {
		return;
	}

	if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
		throw new Exception( tailwindscore_get_checkout_region_restriction_message() );
	}

	throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
		'tailwindscore_region_restricted',
		esc_html( tailwindscore_get_checkout_region_restriction_message() ),
		400
	);
}
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'tailwindscore_store_api_reject_restricted_regions', 10, 2 );

/**
 * 前台州下拉去掉屏蔽州；后台改单仍保留完整列表。
 *
 * @param array<string, array<string, string>> $states 国家 => 州代码 => 名称。
 * @return array<string, array<string, string>>
 */
function tailwindscore_filter_restricted_states_from_frontend( array $states ): array {
	// 后台（含 admin-ajax 改单）保留完整州列表。
	if ( is_admin() ) {
		return $states;
	}

	$map = tailwindscore_get_restricted_checkout_states();

	foreach ( $map as $country => $codes ) {
		if ( empty( $states[ $country ] ) || ! is_array( $codes ) ) {
			continue;
		}

		foreach ( $codes as $code ) {
			$code = strtoupper( trim( (string) $code ) );
			unset( $states[ $country ][ $code ] );
		}
	}

	return $states;
}
add_filter( 'woocommerce_states', 'tailwindscore_filter_restricted_states_from_frontend' );
