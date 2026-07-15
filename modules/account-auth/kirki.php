<?php
declare(strict_types=1);

/**
 * Kirki：账户认证模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_account_auth_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_account_auth',
		array(
			'title'       => esc_html__( 'My Account', 'tailwindscore' ),
			'description' => esc_html__( 'Login and registration page layout and copy.', 'tailwindscore' ),
			'panel'       => $targets['account'],
			'priority'    => 75,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'image',
			'settings' => 'account_auth_hero_image',
			'label'    => esc_html__( 'Hero image', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => '',
			'choices'  => array(
				'save_as' => 'url',
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'account_auth_hero_heading',
			'label'    => esc_html__( 'Hero heading', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => esc_html__( 'Shop with confidence', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'account_auth_hero_text',
			'label'    => esc_html__( 'Hero description', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => esc_html__( 'Sign in to track orders, save details, and checkout faster.', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'color',
			'settings'    => 'account_auth_hero_bg',
			'label'       => esc_html__( 'Hero background', 'tailwindscore' ),
			'description' => esc_html__( 'Leave empty to use theme primary color token.', 'tailwindscore' ),
			'section'     => 'tailwindscore_account_auth',
			'default'     => '',
			'choices'     => array(
				'alpha' => false,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'account_auth_login_welcome',
			'label'    => esc_html__( 'Login welcome line', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => esc_html__( 'Welcome to %site_name%', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'account_auth_register_welcome',
			'label'    => esc_html__( 'Register welcome line', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => esc_html__( 'Create your account', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'account_auth_register_hero_heading',
			'label'    => esc_html__( 'Register hero heading', 'tailwindscore' ),
			'description' => esc_html__( 'Leave empty to reuse login hero heading.', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => '',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'account_auth_register_hero_text',
			'label'    => esc_html__( 'Register hero description', 'tailwindscore' ),
			'description' => esc_html__( 'Leave empty to reuse login hero description.', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => '',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'account_auth_show_remember_me',
			'label'    => esc_html__( 'Show remember me on login', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'account_auth_google_enabled',
			'label'    => esc_html__( 'Show Google sign-in on login (Site Kit)', 'tailwindscore' ),
			'description' => esc_html__( 'Requires Site Kit Sign in with Google. Button appears below the login form.', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => false,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'account_auth_register_show_google',
			'label'    => esc_html__( 'Show Google sign-in on register (Site Kit)', 'tailwindscore' ),
			'description' => esc_html__( 'Button appears above the registration form.', 'tailwindscore' ),
			'section'  => 'tailwindscore_account_auth',
			'default'  => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_account_auth_kirki_register', 10, 2 );
