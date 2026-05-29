<?php
declare(strict_types=1);

/**
 * Typography module bootstrap.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_MODULE_TYPOGRAPHY_VERSION', '0.1.0' );

$tailwindscore_typography_dir = __DIR__;

require_once $tailwindscore_typography_dir . '/includes/class-typography-fonts.php';
require_once $tailwindscore_typography_dir . '/includes/class-typography-settings.php';
require_once $tailwindscore_typography_dir . '/includes/class-typography-frontend.php';
require_once $tailwindscore_typography_dir . '/kirki.php';

/**
 * @return void
 */
function tailwindscore_typography_module_init(): void {
	new Tailwindscore_Typography_Frontend();
}
add_action( 'after_setup_theme', 'tailwindscore_typography_module_init', 25 );
