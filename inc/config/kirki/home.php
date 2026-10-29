<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Hero 可见张数选项（0.5 步进，1–4）。
 *
 * @return array<string, string>
 */
function tailwindscore_kirki_home_hero_column_choices(): array {
	return array(
		'1'   => '1',
		'1.5' => '1.5',
		'2'   => '2',
		'2.5' => '2.5',
		'3'   => '3',
		'3.5' => '3.5',
		'4'   => '4',
	);
}

/**
 * @param string                $cfg
 * @param array<string, string> $targets
 */
function tailwindscore_kirki_register_home( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$panel   = $targets['home'] ?? 'tailwindscore_home';
	$section = $targets['home_hero'] ?? 'tailwindscore_home_hero';

	Kirki::add_section(
		$section,
		array(
			'title'       => esc_html__( 'Hero Banner', 'tailwindscore' ),
			'description' => esc_html__( '首页 Hero 轮播（短代码 [ts_home_hero]）。', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'home_hero_enable',
			'label'    => esc_html__( '启用 Hero 轮播', 'tailwindscore' ),
			'section'  => $section,
			'default'  => true,
			'priority' => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'home_hero_autoplay',
			'label'    => esc_html__( '自动播放', 'tailwindscore' ),
			'section'  => $section,
			'default'  => true,
			'priority' => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'home_hero_autoplay_delay',
			'label'    => esc_html__( '自动播放间隔（毫秒）', 'tailwindscore' ),
			'section'  => $section,
			'default'  => 5000,
			'priority' => 30,
			'choices'  => array(
				'min'  => 2000,
				'max'  => 10000,
				'step' => 500,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'home_hero_layout',
			'label'       => esc_html__( '布局模式', 'tailwindscore' ),
			'description' => esc_html__( 'full＝一屏；peek＝居中多张，两侧露出邻张。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => 'full',
			'priority'    => 35,
			'choices'     => array(
				'full' => esc_html__( '一屏（full）', 'tailwindscore' ),
				'peek' => esc_html__( 'Peek（居中）', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'home_hero_columns_mobile',
			'label'       => esc_html__( '手机可见张数', 'tailwindscore' ),
			'description' => esc_html__( 'slidesPerView；仅 peek 模式生效。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '1',
			'priority'    => 40,
			'choices'     => tailwindscore_kirki_home_hero_column_choices(),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'home_hero_columns_desktop',
			'label'       => esc_html__( '桌面可见张数', 'tailwindscore' ),
			'description' => esc_html__( 'md+（48rem）slidesPerView；仅 peek 模式生效。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '1',
			'priority'    => 50,
			'choices'     => tailwindscore_kirki_home_hero_column_choices(),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'home_hero_gap',
			'label'       => esc_html__( '幻灯片间距（px）', 'tailwindscore' ),
			'description' => esc_html__( '一屏时常为 0；peek 时建议 8–16。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => 0,
			'priority'    => 60,
			'choices'     => array(
				'min'  => 0,
				'max'  => 48,
				'step' => 1,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'         => 'repeater',
			'settings'     => 'home_hero_slides',
			'label'        => esc_html__( '幻灯片', 'tailwindscore' ),
			'description'  => esc_html__( '每行一张；无图的行前台会略过。', 'tailwindscore' ),
			'section'      => $section,
			'priority'     => 70,
			'row_label'    => array(
				'type'  => 'field',
				'value' => esc_html__( '幻灯片', 'tailwindscore' ),
				'field' => 'title',
			),
			'button_label' => esc_html__( '新增幻灯片', 'tailwindscore' ),
			'default'      => array(),
			'fields'       => array(
				'image' => array(
					'type'    => 'image',
					'label'   => esc_html__( '桌面图', 'tailwindscore' ),
					'default' => '',
				),
				'image_mobile' => array(
					'type'    => 'image',
					'label'   => esc_html__( '手机图（可选）', 'tailwindscore' ),
					'default' => '',
				),
				'title' => array(
					'type'    => 'text',
					'label'   => esc_html__( '标题（可选，兼作无障碍名称）', 'tailwindscore' ),
					'default' => '',
				),
				'link_url' => array(
					'type'    => 'text',
					'label'   => esc_html__( '链接 URL', 'tailwindscore' ),
					'default' => '',
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_kirki_register_home', 10, 2 );

/**
 * 将 WP 核心「首页设置」并入主题「首页」panel。
 *
 * @param WP_Customize_Manager $wp_customize
 */
function tailwindscore_customize_merge_home_sections( $wp_customize ): void {
	$section = $wp_customize->get_section( 'static_front_page' );

	if ( ! $section ) {
		return;
	}

	$section->panel    = tailwindscore_kirki_panel_home();
	$section->priority = 10;
}
add_action( 'customize_register', 'tailwindscore_customize_merge_home_sections', 100 );

/**
 * 分栏 Hero（短代码 [ts_home_split_hero]）。
 *
 * @param string                $cfg
 * @param array<string, string> $targets
 */
function tailwindscore_kirki_register_home_split_hero( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$panel   = $targets['home'] ?? 'tailwindscore_home';
	$section = 'tailwindscore_home_split_hero';

	Kirki::add_section(
		$section,
		array(
			'title'       => esc_html__( '分栏 Hero', 'tailwindscore' ),
			'description' => esc_html__( '左右分栏 Hero（短代码 [ts_home_split_hero]）。用于 Spoutini 首页；全图轮播仍用 Hero Banner。', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 25,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'home_split_hero_enable',
			'label'    => esc_html__( '启用分栏 Hero', 'tailwindscore' ),
			'section'  => $section,
			'default'  => true,
			'priority' => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'home_split_hero_eyebrow',
			'label'    => esc_html__( '眉题（可选）', 'tailwindscore' ),
			'section'  => $section,
			'default'  => 'Outdoor & Adventure Gear',
			'priority' => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'home_split_hero_title',
			'label'    => esc_html__( '主标题', 'tailwindscore' ),
			'section'  => $section,
			'default'  => 'Gear up. Get out.',
			'priority' => 30,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'home_split_hero_text',
			'label'    => esc_html__( '副文', 'tailwindscore' ),
			'section'  => $section,
			'default'  => 'Everyday essentials for wherever adventure takes you.',
			'priority' => 40,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'home_split_hero_cta_label',
			'label'    => esc_html__( 'CTA 文案', 'tailwindscore' ),
			'section'  => $section,
			'default'  => 'Shop all gear',
			'priority' => 50,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'text',
			'settings'    => 'home_split_hero_cta_url',
			'label'       => esc_html__( 'CTA 链接', 'tailwindscore' ),
			'description' => esc_html__( '空则回退到商店页或首页。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '',
			'priority'    => 60,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'home_split_hero_image',
			'label'       => esc_html__( '右侧图片', 'tailwindscore' ),
			'description' => esc_html__( '未上传时显示柔和占位，不输出破损图。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '',
			'priority'    => 70,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_kirki_register_home_split_hero', 11, 2 );
