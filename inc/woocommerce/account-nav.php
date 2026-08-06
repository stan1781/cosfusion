<?php
declare(strict_types=1);

/**
 * 账户菜单 endpoint 图标与短标签。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce 账户菜单 endpoint 图标映射。
 *
 * @return array<string, string>
 */
function tailwindscore_account_nav_icons(): array {
	return array(
		'dashboard'       => 'package',
		'orders'          => 'package',
		'wishlist'        => 'heart',
		'edit-address'    => 'map-pin',
		'edit-account'    => 'user',
		'customer-logout' => 'log-out',
	);
}

/**
 * 账户菜单 endpoint 的短标签。
 *
 * @return array<string, string>
 */
function tailwindscore_account_nav_short_labels(): array {
	return array(
		'dashboard'       => __( 'Orders', 'tailwindscore' ),
		'orders'          => __( 'Orders', 'tailwindscore' ),
		'wishlist'        => __( 'Wishlist', 'tailwindscore' ),
		'edit-address'    => __( 'Address', 'tailwindscore' ),
		'edit-account'    => __( 'Profile', 'tailwindscore' ),
		'customer-logout' => __( 'Log out', 'tailwindscore' ),
	);
}

/**
 * 账户菜单不展示支付方式入口。
 *
 * @param array<string, string> $items 菜单项。
 * @return array<string, string>
 */
function tailwindscore_account_nav_filter_menu_items( array $items ): array {
	unset( $items['payment-methods'] );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'tailwindscore_account_nav_filter_menu_items', 20 );
