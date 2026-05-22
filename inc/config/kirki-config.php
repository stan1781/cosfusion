<?php
declare(strict_types=1);

/**
 * Kirki entry: config, panel, module field registration.
 *
 * Requires the Kirki Framework plugin. When absent, tailwindscore_mod() uses defaults only.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/kirki/helpers.php';

/**
 * Register Kirki config and panel; modules hook tailwindscore/kirki/register.
 */
function tailwindscore_kirki_register(): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$cfg   = tailwindscore_kirki_config_id();
	$panel = tailwindscore_kirki_panel_id();

	Kirki::add_config(
		$cfg,
		array(
			'capability'  => 'edit_theme_options',
			'option_type' => 'theme_mod',
		)
	);

	Kirki::add_panel(
		$panel,
		array(
			'title'       => esc_html__( 'TailwindScore', 'tailwindscore' ),
			'description' => esc_html__( 'Theme customization.', 'tailwindscore' ),
			'priority'    => 30,
		)
	);

	/**
	 * Modules register Customizer fields here.
	 *
	 * @param string $cfg   Config id.
	 * @param string $panel Panel id.
	 */
	do_action( 'tailwindscore/kirki/register', $cfg, $panel );
}
add_action( 'after_setup_theme', 'tailwindscore_kirki_register', 20 );
