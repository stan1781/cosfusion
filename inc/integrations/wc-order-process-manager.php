<?php
declare(strict_types=1);

/**
 * wc-order-process-manager 主题集成（档 B）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 当前请求是否应加载 order-process 前台资源。
 */
function tailwindscore_should_load_order_process(): bool {
	return function_exists( 'is_account_page' )
		&& is_account_page()
		&& is_user_logged_in()
		&& class_exists( 'WC_Order_Process_Customer_View' );
}

add_filter( 'wc_order_process_manager_enqueue_frontend_assets', '__return_false' );
add_filter( 'wc_order_process_manager_use_plugin_templates', '__return_false' );

add_action(
	'init',
	static function (): void {
		if ( ! class_exists( 'WC_Order_Process_Frontend' ) ) {
			return;
		}

		remove_action(
			'woocommerce_order_details_after_order_table',
			array( 'WC_Order_Process_Frontend', 'display_order_process_info' ),
			10
		);
	},
	20
);

add_filter(
	'tailwindscore_runtime_config_parts',
	static function ( array $parts ): array {
		if ( ! tailwindscore_should_load_order_process() || ! function_exists( 'wc_order_process_frontend_config' ) ) {
			return $parts;
		}

		$parts['orderProcess'] = wc_order_process_frontend_config();

		return $parts;
	}
);

add_filter(
	'woocommerce_account_menu_items',
	static function ( array $items ): array {
		unset( $items['downloads'] );

		if ( ! class_exists( 'WC_Order_Process_Customer_View' ) ) {
			return $items;
		}

		if ( isset( $items['dashboard'] ) ) {
			$items['dashboard'] = __( 'My Orders', 'tailwindscore' );
		}

		unset( $items['orders'] );

		return $items;
	},
	20
);

/**
 * 来自 query string 的当前 bucket 筛选。
 */
function tailwindscore_account_current_bucket(): string {
	if ( ! class_exists( 'WC_Order_Process_Customer_View' ) ) {
		return '';
	}

	$bucket = isset( $_GET['bucket'] ) ? sanitize_key( wp_unslash( (string) $_GET['bucket'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return WC_Order_Process_Customer_View::sanitize_bucket_param( $bucket );
}

/**
 * 账户 bucket 筛选 URL。
 *
 * @param string $bucket Bucket key，空表示 active。
 * @param string $base   dashboard|orders endpoint 基址。
 */
function tailwindscore_account_bucket_url( string $bucket, string $base = 'dashboard' ): string {
	$url = wc_get_account_endpoint_url( $base );

	if ( '' !== $bucket ) {
		$url = add_query_arg( 'bucket', $bucket, $url );
	}

	return $url;
}

add_action(
	'woocommerce_account_dashboard',
	static function (): void {
		if ( ! tailwindscore_should_load_order_process() ) {
			return;
		}

		get_template_part( 'template-parts/woocommerce/confirm-receipt-dialog' );
	},
	100
);

add_action(
	'woocommerce_view_order',
	static function (): void {
		if ( ! tailwindscore_should_load_order_process() ) {
			return;
		}

		get_template_part( 'template-parts/woocommerce/confirm-receipt-dialog' );
	},
	100
);
