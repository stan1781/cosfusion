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
			'title'       => esc_html__( 'Typography', 'tailwindscore' ),
			'description' => esc_html__( 'Choose body and heading fonts from bundled theme fonts (assets/fonts). Add fonts via the theme manifest; download woff2 from Google Webfonts Helper.', 'tailwindscore' ),
			'panel'       => $targets['header'],
			'priority'    => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'select',
			'settings' => 'typography_heading_font',
			'label'    => esc_html__( 'Heading font', 'tailwindscore' ),
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
			'label'    => esc_html__( 'Body font', 'tailwindscore' ),
			'section'  => 'tailwindscore_typography',
			'default'  => 'system',
			'choices'  => $choices,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_typography_kirki_register', 10, 2 );
