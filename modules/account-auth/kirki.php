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

	// 根级区块（与「页脚」同级），不挂在 WooCommerce panel 下。
	Kirki::add_section(
		$targets['account'],
		array(
			'title'       => esc_html__( '我的账户', 'tailwindscore' ),
			'description' => esc_html__( '登录与注册页布局及文案。', 'tailwindscore' ),
			'priority'    => tailwindscore_kirki_root_priority( 'account' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'image',
			'settings' => 'account_auth_hero_image',
			'label'    => esc_html__( '主图', 'tailwindscore' ),
			'section'  => $targets['account'],
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
			'label'    => esc_html__( '主图标题', 'tailwindscore' ),
			'section'  => $targets['account'],
			'default'  => esc_html__( 'Shop with confidence', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'account_auth_hero_text',
			'label'    => esc_html__( '主图说明', 'tailwindscore' ),
			'section'  => $targets['account'],
			'default'  => esc_html__( 'Sign in to track orders, save details, and checkout faster.', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'color',
			'settings'    => 'account_auth_hero_bg',
			'label'       => esc_html__( '主图背景', 'tailwindscore' ),
			'description' => esc_html__( '留空则使用主题主色令牌。', 'tailwindscore' ),
			'section'     => $targets['account'],
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
			'label'    => esc_html__( '登录欢迎语', 'tailwindscore' ),
			'section'  => $targets['account'],
			'default'  => esc_html__( 'Welcome to %site_name%', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'account_auth_register_welcome',
			'label'    => esc_html__( '注册欢迎语', 'tailwindscore' ),
			'section'  => $targets['account'],
			'default'  => esc_html__( 'Create your account', 'tailwindscore' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'text',
			'settings'    => 'account_auth_register_hero_heading',
			'label'       => esc_html__( '注册页主图标题', 'tailwindscore' ),
			'description' => esc_html__( '留空则复用登录页主图标题。', 'tailwindscore' ),
			'section'     => $targets['account'],
			'default'     => '',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'textarea',
			'settings'    => 'account_auth_register_hero_text',
			'label'       => esc_html__( '注册页主图说明', 'tailwindscore' ),
			'description' => esc_html__( '留空则复用登录页主图说明。', 'tailwindscore' ),
			'section'     => $targets['account'],
			'default'     => '',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'account_auth_show_remember_me',
			'label'    => esc_html__( '登录页显示「记住我」', 'tailwindscore' ),
			'section'  => $targets['account'],
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'account_auth_google_enabled',
			'label'       => esc_html__( '登录页显示 Google 登录（Site Kit）', 'tailwindscore' ),
			'description' => esc_html__( '需要 Site Kit 的 Sign in with Google。按钮显示在登录表单下方。', 'tailwindscore' ),
			'section'     => $targets['account'],
			'default'     => false,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'account_auth_register_show_google',
			'label'       => esc_html__( '注册页显示 Google 登录（Site Kit）', 'tailwindscore' ),
			'description' => esc_html__( '按钮显示在注册表单上方。', 'tailwindscore' ),
			'section'     => $targets['account'],
			'default'     => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_account_auth_kirki_register', 10, 2 );
