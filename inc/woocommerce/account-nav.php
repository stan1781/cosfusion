<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Icon map for WooCommerce account menu endpoints.
 *
 * @return array<string, string>
 */
function tailwindscore_account_nav_icons(): array {
	return array(
		'dashboard'       => 'package',
		'orders'          => 'package',
		'edit-address'    => 'map-pin',
		'edit-account'    => 'user',
		'customer-logout' => 'log-out',
	);
}

/**
 * Short labels for account menu endpoints.
 *
 * @return array<string, string>
 */
function tailwindscore_account_nav_short_labels(): array {
	return array(
		'dashboard'       => __( 'Orders', 'tailwindscore' ),
		'orders'          => __( 'Orders', 'tailwindscore' ),
		'edit-address'    => __( 'Address', 'tailwindscore' ),
		'edit-account'    => __( 'Profile', 'tailwindscore' ),
		'customer-logout' => __( 'Log out', 'tailwindscore' ),
	);
}
