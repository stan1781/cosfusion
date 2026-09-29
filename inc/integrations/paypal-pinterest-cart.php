<?php
/**
 * PayPal Payments × Pinterest：避免 ppc-simulate-cart 清空访客购物车。
 *
 * PayPal 单品页智能按钮会请求 `?wc-ajax=ppc-simulate-cart`：克隆购物车、empty_cart()
 * 后计价，再 `remove_all_actions( 'shutdown' )` 以免把模拟结果写入 session。
 * Pinterest for WooCommerce Tracking 在 `woocommerce_add_to_cart` 中会立刻
 * `$session->save_data()`；此时 session 里 cart 往往已被 destroy，空车落库，
 * 随后打开 cart / checkout 即显示空车或 “session has expired”。
 *
 * Pinterest 1.5.1+ 通过滤镜 `woocommerce_paypal_payments_is_simulating_cart`
 * 跳过模拟加购上报，但 PayPal 插件未置位该滤镜。此处在 simulate 请求中补 true。
 *
 * @package TailwindScore
 */
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 当前请求是否为 PayPal 购物车模拟（非真实改车）。
 */
function tailwindscore_is_ppcp_simulate_cart_request(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读路由识别，无状态变更。
	$wc_ajax = isset( $_REQUEST['wc-ajax'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['wc-ajax'] ) ) : '';

	return 'ppc-simulate-cart' === $wc_ajax;
}

/**
 * 标记 simulate 请求（主题在 plugins_loaded 之后加载，故在此直接执行）。
 */
function tailwindscore_boot_ppcp_simulate_cart_compat(): void {
	if ( ! tailwindscore_is_ppcp_simulate_cart_request() ) {
		return;
	}

	if ( ! defined( 'TAILWINDSCORE_PPCP_SIMULATING_CART' ) ) {
		define( 'TAILWINDSCORE_PPCP_SIMULATING_CART', true );
	}
}
tailwindscore_boot_ppcp_simulate_cart_compat();

/**
 * 供 Pinterest Tracking（及同类扩展）识别 PayPal 模拟加购。
 *
 * @param bool $simulating 是否正在模拟购物车。
 */
function tailwindscore_filter_ppcp_is_simulating_cart( bool $simulating ): bool {
	if ( $simulating || ( defined( 'TAILWINDSCORE_PPCP_SIMULATING_CART' ) && TAILWINDSCORE_PPCP_SIMULATING_CART ) ) {
		return true;
	}

	return tailwindscore_is_ppcp_simulate_cart_request();
}
add_filter( 'woocommerce_paypal_payments_is_simulating_cart', 'tailwindscore_filter_ppcp_is_simulating_cart', 5 );
