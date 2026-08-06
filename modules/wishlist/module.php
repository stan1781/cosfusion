<?php
declare(strict_types=1);

/**
 * 心愿单模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_WISHLIST_VERSION', '0.1.3' );

$tailwindscore_wishlist_dir = __DIR__;

require_once $tailwindscore_wishlist_dir . '/includes/class-wishlist-repository.php';
require_once $tailwindscore_wishlist_dir . '/includes/class-wishlist-rest.php';
require_once $tailwindscore_wishlist_dir . '/includes/class-wishlist-frontend.php';
require_once $tailwindscore_wishlist_dir . '/includes/class-wishlist-account.php';

/**
 * @return void
 */
function tailwindscore_wishlist_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Wishlist_Rest();
	new Tailwindscore_Wishlist_Frontend();
	new Tailwindscore_Wishlist_Account();
}
add_action( 'after_setup_theme', 'tailwindscore_wishlist_module_init', 25 );
