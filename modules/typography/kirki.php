<?php
declare(strict_types=1);

/**
 * Kirki section + 自定义字体控件（optgroup）。
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
 * 注册 theme_mod 设置与带 optgroup 的控件。
 *
 * @param WP_Customize_Manager $wp_customize Customizer。
 */
function tailwindscore_typography_customize_register( WP_Customize_Manager $wp_customize ): void {
	// WP_Customize_Control 仅在 Customizer 中加载，勿在模块引导时 require。
	require_once get_template_directory() . '/modules/typography/includes/class-typography-font-control.php';

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
				'label'   => esc_html__( '标题字体', 'tailwindscore' ),
				'section' => 'tailwindscore_typography',
				'choices' => $choices,
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
				'label'   => esc_html__( '正文字体', 'tailwindscore' ),
				'section' => 'tailwindscore_typography',
				'choices' => $choices,
			)
		)
	);
}
add_action( 'customize_register', 'tailwindscore_typography_customize_register', 20 );
