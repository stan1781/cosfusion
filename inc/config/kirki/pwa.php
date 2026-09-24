<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Kirki：PWA Level 1（无专属券）。
 *
 * @param string                $cfg
 * @param array<string, string> $targets
 */
function tailwindscore_kirki_register_pwa( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$section = $targets['pwa'] ?? 'tailwindscore_pwa';

	Kirki::add_section(
		$section,
		array(
			'title'       => esc_html__( 'PWA', 'tailwindscore' ),
			'description' => esc_html__( 'Installable PWA (Level 1): toggle, icons, and install prompt. No exclusive coupon.', 'tailwindscore' ),
			'priority'    => 42,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'pwa_enabled',
			'label'       => esc_html__( 'Enable PWA', 'tailwindscore' ),
			'description' => esc_html__( 'When off: no Service Worker registration and no manifest output.', 'tailwindscore' ),
			'section'     => $section,
			'default'     => false,
			'priority'    => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'pwa_icon',
			'label'       => esc_html__( 'PWA icon source', 'tailwindscore' ),
			'description' => esc_html__( 'Upload a square PNG/JPG (at least 512×512). On save, generates 192 / 512 / maskable icons into uploads/ts-pwa/.', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '',
			'priority'    => 20,
			'choices'     => array(
				'save_as' => 'url',
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'pwa_install_prompt_enable',
			'label'       => esc_html__( 'Show install prompt', 'tailwindscore' ),
			'description' => esc_html__( 'When off: hide the bottom install bar (and iOS hint). Default off.', 'tailwindscore' ),
			'section'     => $section,
			'default'     => false,
			'priority'    => 25,
		)
	);

	$install_prompt_active = array(
		array(
			'setting'  => 'pwa_install_prompt_enable',
			'operator' => '==',
			'value'    => true,
		),
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'pwa_install_title',
			'label'           => esc_html__( 'Install prompt title', 'tailwindscore' ),
			'section'         => $section,
			'default'         => __( 'Add to Home Screen', 'tailwindscore' ),
			'priority'        => 30,
			'active_callback' => $install_prompt_active,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'pwa_install_body',
			'label'           => esc_html__( 'Install prompt body (Android)', 'tailwindscore' ),
			'section'         => $section,
			'default'         => __( 'Install for quick access like an app.', 'tailwindscore' ),
			'priority'        => 40,
			'active_callback' => $install_prompt_active,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'pwa_install_ios_hint',
			'label'           => esc_html__( 'iOS install hint', 'tailwindscore' ),
			'section'         => $section,
			'default'         => __( 'In Safari, tap Share, then Add to Home Screen.', 'tailwindscore' ),
			'priority'        => 50,
			'active_callback' => $install_prompt_active,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'pwa_install_action',
			'label'           => esc_html__( 'Install button label', 'tailwindscore' ),
			'section'         => $section,
			'default'         => __( 'Install', 'tailwindscore' ),
			'priority'        => 60,
			'active_callback' => $install_prompt_active,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'pwa_install_dismiss',
			'label'           => esc_html__( 'Dismiss button label', 'tailwindscore' ),
			'section'         => $section,
			'default'         => __( 'Not now', 'tailwindscore' ),
			'priority'        => 70,
			'active_callback' => $install_prompt_active,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_kirki_register_pwa', 10, 2 );
