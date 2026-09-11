<?php
declare(strict_types=1);

/**
 * 变体选择器模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_VARIATIONS_VERSION', '0.1.7' );

$tailwindscore_variations_dir = __DIR__;

require_once $tailwindscore_variations_dir . '/kirki.php';
require_once $tailwindscore_variations_dir . '/includes/class-variation-settings.php';
require_once $tailwindscore_variations_dir . '/includes/class-variation-admin.php';
require_once $tailwindscore_variations_dir . '/includes/class-variation-assets.php';

/**
 * @return void
 */
function tailwindscore_variations_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Variation_Admin();
	new Tailwindscore_Variation_Assets();
}
add_action( 'after_setup_theme', 'tailwindscore_variations_module_init', 25 );
