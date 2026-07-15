<?php

declare(strict_types=1);



/**

 * Kirki 入口：config、panel、模块字段注册。

 *

 * 需要 Kirki Framework 插件。未安装时 tailwindscore_mod() 仅使用默认值。

 *

 * @package TailwindScore

 */



defined( 'ABSPATH' ) || exit;



require_once __DIR__ . '/kirki/helpers.php';



/**

 * 注册 Kirki config 与 panel；模块挂载 tailwindscore/kirki/register。

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
	// 站点身份 – 自定义 logo 字段（PC + 移动端）
	// 添加到原生 "title_tagline" 区块，与
	// 内置站点图标与副标题控件并列显示。
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

	 * 模块在此注册 Customizer 字段。

	 *

	 * @param string               $cfg     配置 ID。

	 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。

	 */

	do_action( 'tailwindscore/kirki/register', $cfg, $targets );

}

add_action( 'after_setup_theme', 'tailwindscore_kirki_register', 20 );

