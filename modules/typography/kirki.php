<?php
declare(strict_types=1);

/**
 * Kirki：排版（标题 + 正文字体）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_typography_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$choices = Tailwindscore_Typography_Fonts::get_choices();

	Kirki::add_section(
		'tailwindscore_typography',
		array(
			'title'       => esc_html__( '排版', 'tailwindscore' ),
			'description' => esc_html__( '从主题内置字体（assets/fonts）中选择正文与标题字体。通过主题清单添加字体；可从 Google Webfonts Helper 下载 woff2。', 'tailwindscore' ),
				'panel'       => $targets['design'],
			'priority'    => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'select',
			'settings' => 'typography_heading_font',
			'label'    => esc_html__( '标题字体', 'tailwindscore' ),
			'section'  => 'tailwindscore_typography',
			'default'  => 'system',
			'choices'  => $choices,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'select',
			'settings' => 'typography_body_font',
			'label'    => esc_html__( '正文字体', 'tailwindscore' ),
			'section'  => 'tailwindscore_typography',
			'default'  => 'system',
			'choices'  => $choices,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_typography_kirki_register', 10, 2 );
