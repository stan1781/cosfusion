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



	if ( class_exists( 'WooCommerce' ) ) {

		Kirki::add_section(

			'tailwindscore_shop_archive',

			array(

				'title'       => esc_html__( 'Shop & Archive', 'tailwindscore' ),

				'description' => esc_html__( 'Archive hero backgrounds and related shop listing options.', 'tailwindscore' ),

				'panel'       => $targets['woocommerce'],

				'priority'    => 15,

			)

		);



		tailwindscore_kirki_register_section_placeholder(

			$cfg,

			'tailwindscore_checkout_settings',

			$targets['woocommerce'],

			esc_html__( 'Checkout layout', 'tailwindscore' ),

			esc_html__( 'Checkout shipping rules and other checkout theme options.', 'tailwindscore' ),

			25

		);

	}



	// -------------------------------------------------------------------------
	// Site Identity – custom logo fields (PC + mobile)
	// Added to the native "title_tagline" section so they appear alongside
	// the built-in Site Icon and Tagline controls.
	// -------------------------------------------------------------------------
	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'tailwindscore_logo_pc',
			'label'       => esc_html__( 'Desktop Logo', 'tailwindscore' ),
			'description' => esc_html__( 'Displayed on desktop / tablet screens. Recommended size: 200 × 60 px.', 'tailwindscore' ),
			'section'     => 'title_tagline',
			'default'     => '',
			'priority'    => 8,
			'choices'     => array(
				'save_as' => 'url',
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'tailwindscore_logo_mobile',
			'label'       => esc_html__( 'Mobile Logo', 'tailwindscore' ),
			'description' => esc_html__( 'Displayed on mobile screens. Recommended size: 120 × 40 px.', 'tailwindscore' ),
			'section'     => 'title_tagline',
			'default'     => '',
			'priority'    => 9,
			'choices'     => array(
				'save_as' => 'url',
			),
		)
	);

	/**

	 * Modules register Customizer fields here.

	 *

	 * @param string               $cfg     Config id.

	 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.

	 */

	do_action( 'tailwindscore/kirki/register', $cfg, $targets );

}

add_action( 'after_setup_theme', 'tailwindscore_kirki_register', 20 );

