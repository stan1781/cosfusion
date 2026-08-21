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
		$targets['design'],
		array(
				'title'       => esc_html__( '设计', 'tailwindscore' ),
				'description' => esc_html__( '排版、联络方式等。品牌色等设计令牌仍以 SCSS（--ts-*）为真源，Customizer 颜色覆盖尚未接入。', 'tailwindscore' ),
			'priority'    => tailwindscore_kirki_root_priority( 'design' ),
		)
	);

	Kirki::add_panel(
		$targets['header'],
		array(
			'title'       => esc_html__( '页头', 'tailwindscore' ),
			'description' => esc_html__( '页头区域设置（顶栏、搜索弹层等）。', 'tailwindscore' ),
			'priority'    => tailwindscore_kirki_root_priority( 'header' ),
		)
	);

	Kirki::add_panel(
		$targets['home'],
		array(
			'title'       => esc_html__( '首页', 'tailwindscore' ),
			'description' => esc_html__( '静态首页设置与 Hero Banner。其余区块请用页面短代码与内容区块。', 'tailwindscore' ),
			'priority'    => tailwindscore_kirki_root_priority( 'home' ),
		)
	);

	if ( class_exists( 'WooCommerce' ) ) {
		Kirki::add_panel(
			$targets['shop'],
			array(
				'title'       => esc_html__( '商店/归档', 'tailwindscore' ),
				'description' => esc_html__( '商店与分类归档页主题选项（含快速查看）。', 'tailwindscore' ),
				'priority'    => tailwindscore_kirki_root_priority( 'shop' ),
			)
		);

		Kirki::add_panel(
			$targets['product'],
			array(
				'title'       => esc_html__( '商品详情页', 'tailwindscore' ),
				'description' => esc_html__( '单品页画廊、变体、尺码、保障等主题选项。', 'tailwindscore' ),
				'priority'    => tailwindscore_kirki_root_priority( 'product' ),
			)
		);

		Kirki::add_section(
			'tailwindscore_shop_archive',
			array(
				'title'       => esc_html__( '归档 Hero', 'tailwindscore' ),
				'description' => esc_html__( '商店与分类归档页顶部 Hero 背景。', 'tailwindscore' ),
				'panel'       => $targets['shop'],
				'priority'    => 15,
			)
		);

		Kirki::add_section(
			$targets['checkout'],
			array(
				'title'       => esc_html__( '结账页面', 'tailwindscore' ),
				'description' => esc_html__( '结账配送规则及其他结账主题选项。', 'tailwindscore' ),
				'priority'    => tailwindscore_kirki_root_priority( 'checkout' ),
			)
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
			'label'       => esc_html__( '桌面 Logo', 'tailwindscore' ),
			'description' => esc_html__( '用于桌面 / 平板。建议尺寸：200 × 60 px。', 'tailwindscore' ),
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
			'label'       => esc_html__( '手机 Logo', 'tailwindscore' ),
			'description' => esc_html__( '用于手机屏幕。建议尺寸：120 × 40 px。', 'tailwindscore' ),
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
	 * @param string                $cfg     配置 ID。
	 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
	 */
	do_action( 'tailwindscore/kirki/register', $cfg, $targets );
}

add_action( 'after_setup_theme', 'tailwindscore_kirki_register', 20 );

/**
 * 将「站点身份」置于 Customizer 根级第一位。
 *
 * @param WP_Customize_Manager $wp_customize
 */
function tailwindscore_customize_site_identity_first( $wp_customize ): void {
	$section = $wp_customize->get_section( 'title_tagline' );

	if ( ! $section ) {
		return;
	}

	$section->priority = tailwindscore_kirki_root_priority( 'title_tagline' );
}
add_action( 'customize_register', 'tailwindscore_customize_site_identity_first', 100 );
