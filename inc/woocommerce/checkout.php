<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

add_action( 'woocommerce_after_checkout_form', 'tailwindscore_checkout_login_dialog', 10 );

/**
 * 为访客输出结账登录对话框（替代默认页面顶部登录表单）。
 */
function tailwindscore_checkout_login_dialog(): void {
	if ( is_user_logged_in() ) {
		return;
	}

	get_template_part(
		'template-parts/header/account-login-dialog',
		null,
		array(
			'redirect' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' ),
		)
	);
}

add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

/**
 * 在购物车 / 结账页移除非必要插件资源以提升性能。
 */
function tailwindscore_dequeue_plugin_assets_on_checkout_cart(): void {
	if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() ) ) {
		return;
	}

	wp_dequeue_style( 'wc-enhanced-ajax-search' );
	wp_dequeue_script( 'wc-enhanced-ajax-search' );
	wp_deregister_style( 'wc-enhanced-ajax-search' );
	wp_deregister_script( 'wc-enhanced-ajax-search' );

	if ( function_exists( 'tailwindscore_dequeue_mailnest_assets' ) ) {
		tailwindscore_dequeue_mailnest_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_dequeue_plugin_assets_on_checkout_cart', 999 );

/**
 * 在结账页移除非必要运行时配置全局变量（减小内联脚本，不初始化 Ajax 搜索）。
 *
 * @param array<string, mixed> $parts 运行时配置片段。
 * @return array<string, mixed>
 */
function tailwindscore_checkout_runtime_config_parts( array $parts ): array {
	if ( ! tailwindscore_is_checkout_page() ) {
		return $parts;
	}

	unset( $parts['ajaxSearch'], $parts['quickView'] );

	return $parts;
}
add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_checkout_runtime_config_parts', 1000 );

/**
 * 防止缓存结账 / 购物车 HTML（过期 AJAX nonce 会导致 update_order_review 失败）。
 */
function tailwindscore_nocache_woocommerce_flow_pages(): void {
	if ( ! function_exists( 'is_cart' ) ) {
		return;
	}

	if ( is_cart() || is_checkout() ) {
		nocache_headers();
	}
}
add_action( 'template_redirect', 'tailwindscore_nocache_woocommerce_flow_pages', 0 );
