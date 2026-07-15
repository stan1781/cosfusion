<?php
declare(strict_types=1);

/**
 * Quick View module bootstrap.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_QUICK_VIEW_VERSION', '0.1.5' );

$tailwindscore_quick_view_dir = __DIR__;

require_once $tailwindscore_quick_view_dir . '/kirki.php';
require_once $tailwindscore_quick_view_dir . '/includes/class-quick-view-settings.php';
require_once $tailwindscore_quick_view_dir . '/includes/class-quick-view-ajax.php';
require_once $tailwindscore_quick_view_dir . '/includes/class-quick-view-frontend.php';

/**
 * @return void
 */
function tailwindscore_quick_view_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Quick_View_Ajax();

	if ( Tailwindscore_Quick_View_Settings::is_enabled() ) {
		new Tailwindscore_Quick_View_Frontend();
	}
}
add_action( 'after_setup_theme', 'tailwindscore_quick_view_module_init', 25 );
