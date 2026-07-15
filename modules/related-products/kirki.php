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
			'title'       => esc_html__( 'Related products', 'tailwindscore' ),
			'description' => esc_html__( 'Single product related recommendations (tags, categories, title keywords).', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 67,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_related_enabled',
			'label'    => esc_html__( 'Enable related products optimization', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'select',
			'settings' => 'product_related_algorithm',
			'label'    => esc_html__( 'Recommendation algorithm', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => 'hybrid',
			'choices'  => array(
				'hybrid'          => esc_html__( 'Hybrid (tags + categories + title)', 'tailwindscore' ),
				'tag_based'       => esc_html__( 'Tags first', 'tailwindscore' ),
				'category_based'  => esc_html__( 'Categories first', 'tailwindscore' ),
				'simple'          => esc_html__( 'Simple (tags then categories)', 'tailwindscore' ),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_related_max_products',
			'label'       => esc_html__( 'Maximum related products', 'tailwindscore' ),
			'description' => esc_html__( 'Child categories fill from parent when needed.', 'tailwindscore' ),
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
			'label'    => esc_html__( 'Exclude out-of-stock products', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_related_randomize_results',
			'label'    => esc_html__( 'Randomize display order', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_related_title_match_enabled',
			'label'    => esc_html__( 'Match by product title keywords', 'tailwindscore' ),
			'description' => esc_html__( 'Fills gaps using tokens from the product title (before random fallback).', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_related',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_related_title_min_token_length',
			'label'       => esc_html__( 'Minimum title token length', 'tailwindscore' ),
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
