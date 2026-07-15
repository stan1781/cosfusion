<?php
declare(strict_types=1);

/**
 * Iconic Woo Linked Variations — 仅在单品页加载前台资源。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 当前请求是否应加载 Iconic Linked Variations 前台 CSS/JS。
 */
function tailwindscore_should_load_iconic_wlv_assets(): bool {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return false;
	}

	return (bool) apply_filters( 'tailwindscore_should_load_iconic_wlv_assets', true );
}

/**
 * 移除 Iconic Linked Variations 前台资源。
 */
function tailwindscore_dequeue_iconic_wlv_assets(): void {
	wp_dequeue_style( 'iconic-wlv' );
	wp_dequeue_script( 'iconic-wlv' );
	wp_deregister_style( 'iconic-wlv' );
	wp_deregister_script( 'iconic-wlv' );
}

/**
 * 在插件运行前阻止其 enqueue 资源（及 jQuery）。
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		remove_action( 'wp_enqueue_scripts', array( 'Iconic_WLV_Assets', 'enqueue_frontend_assets' ) );
		tailwindscore_dequeue_iconic_wlv_assets();
	},
	1
);

/**
 * 同一请求中若其他回调稍后重新 enqueue 时的兜底。
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		tailwindscore_dequeue_iconic_wlv_assets();
	},
	999
);

add_action(
	'wp_print_styles',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		tailwindscore_dequeue_iconic_wlv_assets();
	},
	100
);

add_action(
	'wp_print_scripts',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		tailwindscore_dequeue_iconic_wlv_assets();
	},
	100
);
