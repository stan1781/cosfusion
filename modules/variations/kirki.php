<?php
declare(strict_types=1);

/**
 * Kirki：变体选择器模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_variations_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_variations',
		array(
			'title'       => esc_html__( '变体选择器', 'tailwindscore' ),
			'description' => esc_html__( '可变商品表单上的自定义色板与布局。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 66,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'variation_selector_enabled',
			'label'    => esc_html__( '启用自定义变体选择器', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'variation_color_attributes',
			'label'    => esc_html__( '颜色属性名称', 'tailwindscore' ),
			'description' => esc_html__( '逗号分隔。以图片色板展示。', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'pa_color,color,pa_style,style',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'variation_size_attributes',
			'label'    => esc_html__( '尺码属性名称', 'tailwindscore' ),
			'description' => esc_html__( '逗号分隔。用于行样式；尺码指南后续挂钩。', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'pa_size,size',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'radio-buttonset',
			'settings' => 'variation_color_default_mode',
			'label'    => esc_html__( '颜色选择器默认布局', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'grid',
			'choices'  => array(
				'grid' => esc_html__( '网格', 'tailwindscore' ),
				'list' => esc_html__( '列表', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'radio-buttonset',
			'settings' => 'variation_color_shape',
			'label'    => esc_html__( '颜色色板形状', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => 'square',
			'choices'  => array(
				'square' => esc_html__( '方形', 'tailwindscore' ),
				'round'  => esc_html__( '圆形', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'variation_grid_show_text',
			'label'    => esc_html__( '网格模式下显示文字标签', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_variations',
			'default'  => false,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_variations_kirki_register', 10, 2 );
