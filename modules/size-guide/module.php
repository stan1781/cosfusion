<?php
declare(strict_types=1);

/**
 * 尺码指南模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_SIZE_GUIDE_VERSION', '0.1.0' );

$tailwindscore_size_guide_dir = __DIR__;

require_once $tailwindscore_size_guide_dir . '/kirki.php';
require_once $tailwindscore_size_guide_dir . '/includes/class-size-guide-settings.php';
require_once $tailwindscore_size_guide_dir . '/includes/class-size-guide-frontend.php';

/**
 * @return void
 */
function tailwindscore_size_guide_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Size_Guide_Frontend();
}
add_action( 'after_setup_theme', 'tailwindscore_size_guide_module_init', 25 );
