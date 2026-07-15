<?php
declare(strict_types=1);

/**
 * Kirki：尺码指南模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_size_guide_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_size_guide',
		array(
			'title'       => esc_html__( '尺码指南', 'tailwindscore' ),
			'description' => esc_html__( '可变商品上的尺码表弹层与按尺码测量数据。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 67,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'size_guide_enabled',
			'label'    => esc_html__( '启用尺码指南', 'tailwindscore' ),
			'section'  => 'tailwindscore_size_guide',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'radio-buttonset',
			'settings' => 'size_guide_display_mode',
			'label'    => esc_html__( '按钮显示', 'tailwindscore' ),
			'section'  => 'tailwindscore_size_guide',
			'default'  => 'conditional',
			'choices'  => array(
				'conditional' => esc_html__( '有指南时显示', 'tailwindscore' ),
				'always'      => esc_html__( '始终显示', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'size_guide_inline_enabled',
			'label'    => esc_html__( '在所选尺码下方显示行', 'tailwindscore' ),
			'section'  => 'tailwindscore_size_guide',
			'default'  => true,
		)
	);
}

add_action( 'tailwindscore/kirki/register', 'tailwindscore_size_guide_kirki_register', 10, 2 );
