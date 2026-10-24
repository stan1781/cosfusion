<?php
declare(strict_types=1);

/**
 * Kirki section + 自定义字体控件（optgroup）与推荐组合。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 面板/分区 ID。
 */
function tailwindscore_typography_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_typography',
		array(
			'title'       => esc_html__( '排版', 'tailwindscore' ),
			'description' => esc_html__( '字体文件放在 uploads/tailwindscore-fonts/{slug}/，文件名保持 Google Webfonts Helper 下载原名（如 open-sans-v40-latin-regular.woff2）。仅已放入文件的字体会出现在下列选项中；latin + woff2，字重一般为 400+600。', 'tailwindscore' ),
			'panel'       => $targets['design'],
			'priority'    => 10,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_typography_kirki_register', 10, 2 );

/**
 * 磁盘上双方均可用的推荐组合。
 *
 * @return list<array{id: string, group: string, label: string, detail: string, heading: string, body: string}>
 */
function tailwindscore_typography_available_font_presets(): array {
	$manifest = Tailwindscore_Typography_Fonts::get_manifest();
	$available = array();

	foreach ( tailwindscore_typography_font_presets() as $preset ) {
		$heading = isset( $preset['heading'] ) ? (string) $preset['heading'] : '';
		$body    = isset( $preset['body'] ) ? (string) $preset['body'] : '';
		if ( '' === $heading || '' === $body ) {
			continue;
		}
		if ( ! isset( $manifest[ $heading ], $manifest[ $body ] ) ) {
			continue;
		}
		$available[] = $preset;
	}

	return $available;
}

/**
 * 注册 theme_mod 设置与带 optgroup 的控件。
 *
 * @param WP_Customize_Manager $wp_customize Customizer。
 */
function tailwindscore_typography_customize_register( WP_Customize_Manager $wp_customize ): void {
	// WP_Customize_Control 仅在 Customizer 中加载，勿在模块引导时 require。
	require_once get_template_directory() . '/modules/typography/includes/class-typography-font-control.php';
	require_once get_template_directory() . '/modules/typography/includes/class-typography-presets-control.php';

	$choices  = Tailwindscore_Typography_Fonts::get_choices_grouped();
	$sanitize = array( 'Tailwindscore_Typography_Settings', 'sanitize_slug' );

	$wp_customize->add_setting(
		'typography_heading_font',
		array(
			'default'           => 'system',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new Tailwindscore_Typography_Font_Control(
			$wp_customize,
			'typography_heading_font',
			array(
				'label'    => esc_html__( '标题字体', 'tailwindscore' ),
				'section'  => 'tailwindscore_typography',
				'choices'  => $choices,
				'priority' => 10,
			)
		)
	);

	$wp_customize->add_setting(
		'typography_body_font',
		array(
			'default'           => 'system',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new Tailwindscore_Typography_Font_Control(
			$wp_customize,
			'typography_body_font',
			array(
				'label'    => esc_html__( '正文字体', 'tailwindscore' ),
				'section'  => 'tailwindscore_typography',
				'choices'  => $choices,
				'priority' => 20,
			)
		)
	);

	$presets = tailwindscore_typography_available_font_presets();
	if ( array() === $presets ) {
		return;
	}

	// 绑定已有 setting，仅用于挂载控件；界面由按钮驱动。
	$wp_customize->add_control(
		new Tailwindscore_Typography_Presets_Control(
			$wp_customize,
			'typography_font_presets',
			array(
				'label'       => esc_html__( '常用推荐组合', 'tailwindscore' ),
				'description' => esc_html__( '点击即同时应用标题与正文字体。', 'tailwindscore' ),
				'section'     => 'tailwindscore_typography',
				'settings'    => 'typography_body_font',
				'priority'    => 30,
				'presets'     => $presets,
			)
		)
	);
}
add_action( 'customize_register', 'tailwindscore_typography_customize_register', 20 );

/**
 * Customizer 控件脚本：一点应用预设。
 */
function tailwindscore_typography_customize_controls_enqueue(): void {
	$path = get_template_directory() . '/modules/typography/assets/customize-presets.js';
	if ( ! is_readable( $path ) ) {
		return;
	}

	wp_enqueue_script(
		'tailwindscore-typography-presets',
		get_template_directory_uri() . '/modules/typography/assets/customize-presets.js',
		array( 'customize-controls' ),
		(string) filemtime( $path ),
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'tailwindscore_typography_customize_controls_enqueue' );
