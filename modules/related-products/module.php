<?php
declare(strict_types=1);

/**
 * 相关产品模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_RELATED_PRODUCTS_VERSION', '0.2.0' );

$tailwindscore_related_products_dir = __DIR__;

require_once $tailwindscore_related_products_dir . '/kirki.php';
require_once $tailwindscore_related_products_dir . '/includes/class-related-products-settings.php';
require_once $tailwindscore_related_products_dir . '/includes/class-related-products-engine.php';
require_once $tailwindscore_related_products_dir . '/includes/class-related-products-hooks.php';

/**
 * @return void
 */
function tailwindscore_related_products_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Related_Products_Hooks();
}
add_action( 'after_setup_theme', 'tailwindscore_related_products_module_init', 25 );
