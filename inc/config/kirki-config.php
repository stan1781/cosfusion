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
 * Register Kirki config and panels; modules hook tailwindscore/kirki/register.
 */
function tailwindscore_kirki_register(): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$cfg     = tailwindscore_kirki_config_id();
	$targets = tailwindscore_kirki_customizer_targets();

	Kirki::add_config(
		$cfg,
		array(
			'capability'  => 'edit_theme_options',
			'option_type' => 'theme_mod',
		)
	);

	Kirki::add_panel(
		$targets['header'],
		array(
			'title'       => esc_html__( 'Header', 'tailwindscore' ),
			'description' => esc_html__( 'Header area settings (logo, navigation, top bar, typography).', 'tailwindscore' ),
			'priority'    => 10,
		)
	);

	Kirki::add_panel(
		$targets['footer'],
		array(
			'title'       => esc_html__( 'Footer', 'tailwindscore' ),
			'description' => esc_html__( 'Footer area settings (copyright, widgets, social links).', 'tailwindscore' ),
			'priority'    => 20,
		)
	);

	tailwindscore_kirki_register_section_placeholder(
		$cfg,
		'tailwindscore_footer_placeholder',
		$targets['footer'],
		esc_html__( 'Footer Settings', 'tailwindscore' ),
		esc_html__( 'Footer customization options will appear here.', 'tailwindscore' )
	);

	tailwindscore_kirki_register_section_placeholder(
		$cfg,
		'tailwindscore_homepage_display',
		null,
		esc_html__( 'Homepage display', 'tailwindscore' ),
		esc_html__( 'Homepage layout and category options will appear here. Use Homepage Settings above to choose which page is the front page.', 'tailwindscore' ),
		125
	);

	if ( class_exists( 'WooCommerce' ) ) {
		tailwindscore_kirki_register_section_placeholder(
			$cfg,
			'tailwindscore_shop_archive',
			$targets['woocommerce'],
			esc_html__( 'Shop & Archive', 'tailwindscore' ),
			esc_html__( 'Shop and archive page theme options will appear here.', 'tailwindscore' ),
			15
		);

		tailwindscore_kirki_register_section_placeholder(
			$cfg,
			'tailwindscore_checkout_settings',
			$targets['woocommerce'],
			esc_html__( 'Checkout layout', 'tailwindscore' ),
			esc_html__( 'Checkout page theme options will appear here.', 'tailwindscore' ),
			25
		);
	}

	/**
	 * Modules register Customizer fields here.
	 *
	 * @param string               $cfg     Config id.
	 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
	 */
	do_action( 'tailwindscore/kirki/register', $cfg, $targets );
}
add_action( 'after_setup_theme', 'tailwindscore_kirki_register', 20 );
