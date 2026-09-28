<?php
declare(strict_types=1);

/**
 * 产品组模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_PRODUCT_GROUPS_VERSION', '0.1.0' );

$tailwindscore_product_groups_dir = __DIR__;

require_once $tailwindscore_product_groups_dir . '/includes/class-product-group-cpt.php';
require_once $tailwindscore_product_groups_dir . '/includes/class-product-group-repository.php';
require_once $tailwindscore_product_groups_dir . '/includes/class-product-group-frontend.php';
require_once $tailwindscore_product_groups_dir . '/includes/class-product-group-migrate.php';
require_once $tailwindscore_product_groups_dir . '/includes/class-product-group-csv.php';

/**
 * @return void
 */
function tailwindscore_product_groups_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Tailwindscore_Product_Group_Cpt::init();
	Tailwindscore_Product_Group_Frontend::init();
	Tailwindscore_Product_Group_Migrate::init();
	Tailwindscore_Product_Group_Csv::init();

	if ( is_admin() ) {
		require_once __DIR__ . '/includes/class-product-group-admin.php';
		Tailwindscore_Product_Group_Admin::init();
	}
}
add_action( 'after_setup_theme', 'tailwindscore_product_groups_module_init', 25 );
