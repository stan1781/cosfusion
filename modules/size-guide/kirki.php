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
			'title'       => esc_html__( 'Size guide', 'tailwindscore' ),
			'description' => esc_html__( 'Size chart modal and per-size measurements on variable products.', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 67,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'size_guide_enabled',
			'label'    => esc_html__( 'Enable size guide', 'tailwindscore' ),
			'section'  => 'tailwindscore_size_guide',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'radio-buttonset',
			'settings' => 'size_guide_display_mode',
			'label'    => esc_html__( 'Button display', 'tailwindscore' ),
			'section'  => 'tailwindscore_size_guide',
			'default'  => 'conditional',
			'choices'  => array(
				'conditional' => esc_html__( 'When guide exists', 'tailwindscore' ),
				'always'      => esc_html__( 'Always', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'size_guide_inline_enabled',
			'label'    => esc_html__( 'Show row under selected size', 'tailwindscore' ),
			'section'  => 'tailwindscore_size_guide',
			'default'  => true,
		)
	);
}

add_action( 'tailwindscore/kirki/register', 'tailwindscore_size_guide_kirki_register', 10, 2 );
