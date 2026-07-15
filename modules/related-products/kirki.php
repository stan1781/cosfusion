<?php
declare(strict_types=1);

/**
 * Kirki：相关产品模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_related_products_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_related',
		array(
			'title'       => esc_html__( '相关产品', 'tailwindscore' ),
			'description' => esc_html__( '单品页相关推荐（标签、分类、标题关键词）。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 67,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_related_enabled',
			'label'    => esc_html__( '启用相关产品优化', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'select',
			'settings' => 'product_related_algorithm',
			'label'    => esc_html__( '推荐算法', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => 'hybrid',
			'choices'  => array(
				'hybrid'         => esc_html__( '混合（标签 + 分类 + 标题）', 'tailwindscore' ),
				'tag_based'      => esc_html__( '标签优先', 'tailwindscore' ),
				'category_based' => esc_html__( '分类优先', 'tailwindscore' ),
				'simple'         => esc_html__( '简单（先标签后分类）', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_related_max_products',
			'label'       => esc_html__( '相关产品最大数量', 'tailwindscore' ),
			'description' => esc_html__( '子分类不足时从父分类补齐。', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_related',
			'default'     => 12,
			'choices'     => array(
				'min'  => 1,
				'max'  => 50,
				'step' => 1,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_related_exclude_out_of_stock',
			'label'    => esc_html__( '排除缺货商品', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_related_randomize_results',
			'label'    => esc_html__( '随机显示顺序', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'product_related_title_match_enabled',
			'label'       => esc_html__( '按商品标题关键词匹配', 'tailwindscore' ),
			'description' => esc_html__( '在随机回退前，用标题词元补足缺口。', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_related',
			'default'     => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_related_title_min_token_length',
			'label'       => esc_html__( '标题词元最小长度', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_related',
			'default'     => 3,
			'choices'     => array(
				'min'  => 2,
				'max'  => 10,
				'step' => 1,
			),
			'active_callback' => array(
				array(
					'setting'  => 'product_related_title_match_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_related_products_kirki_register', 10, 2 );
