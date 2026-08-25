<?php
declare(strict_types=1);

/**
 * Kirki：Quick View 模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_quick_view_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_quick_view',
		array(
			'title'       => esc_html__( '快速查看', 'tailwindscore' ),
			'description' => esc_html__( '商店归档、首页、单品相关产品、购物车与 404 产品轮播中的可变商品快速查看弹层。', 'tailwindscore' ),
			'panel'       => $targets['shop'],
			'priority'    => 68,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_quick_view_enabled',
			'label'    => esc_html__( '启用快速查看', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_quick_view',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'multicheck',
			'settings'          => 'product_quick_view_target_pages',
			'label'             => esc_html__( '生效页面', 'tailwindscore' ),
			'section'           => 'tailwindscore_product_quick_view',
			'default'           => Tailwindscore_Quick_View_Settings::default_target_pages(),
			'choices'           => array(
				'shop_archive'   => esc_html__( '商店 / 分类 / 标签归档', 'tailwindscore' ),
				'homepage'       => esc_html__( '首页', 'tailwindscore' ),
				'single_product' => esc_html__( '单品页（相关产品循环）', 'tailwindscore' ),
				'cart'           => esc_html__( '购物车页（商品推荐轮播）', 'tailwindscore' ),
				'not_found'      => esc_html__( '404 页面（人气商品轮播）', 'tailwindscore' ),
			),
			'sanitize_callback' => array( 'Tailwindscore_Quick_View_Settings', 'sanitize_target_pages' ),
			'active_callback'   => array(
				array(
					'setting'  => 'product_quick_view_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_quick_view_kirki_register', 10, 2 );
