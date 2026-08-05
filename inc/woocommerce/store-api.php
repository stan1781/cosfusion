<?php
declare(strict_types=1);

/**
 * WooCommerce Store API 扩展。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Store API 扩展：购物车免运进度。
 */
function tailwindscore_register_store_api_cart_data(): void {
	if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
		return;
	}

	woocommerce_store_api_register_endpoint_data(
		array(
			'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
			'namespace'       => 'tailwindscore',
			'data_callback'   => static function (): array {
				return array(
					'freeShippingProgress' => tailwindscore_get_cart_free_shipping_progress(),
				);
			},
			'schema_callback' => static function (): array {
				return array(
					'freeShippingProgress' => array(
						'description' => __( 'Free shipping progress for cart drawer and cart page.', 'tailwindscore' ),
						'type'        => array( 'object', 'null' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'qualified' => array(
								'type' => 'boolean',
							),
							'percent'   => array(
								'type' => 'integer',
							),
							'message'   => array(
								'type' => 'string',
							),
						),
					),
				);
			},
		)
	);
}

/**
 * Blocks 可能在主题加载前已触发 woocommerce_blocks_loaded，需立即补注册。
 */
function tailwindscore_bootstrap_store_api_cart_data(): void {
	if ( did_action( 'woocommerce_blocks_loaded' ) ) {
		tailwindscore_register_store_api_cart_data();
		return;
	}

	add_action(
		'woocommerce_blocks_loaded',
		static function (): void {
			tailwindscore_register_store_api_cart_data();
		}
	);
}
tailwindscore_bootstrap_store_api_cart_data();
