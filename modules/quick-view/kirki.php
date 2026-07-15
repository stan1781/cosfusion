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
			'description' => esc_html__( '归档与首页可变商品的快速查看弹层。', 'tailwindscore' ),
			'panel'       => $targets['product'],
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
			'type'     => 'checkbox',
			'settings' => 'product_quick_view_target_pages',
			'label'    => esc_html__( '生效页面', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_quick_view',
			'default'  => array( 'shop_archive', 'homepage', 'single_product' ),
			'choices'  => array(
				'shop_archive'   => esc_html__( '商店 / 分类 / 标签归档', 'tailwindscore' ),
				'homepage'       => esc_html__( '首页', 'tailwindscore' ),
				'single_product' => esc_html__( '单品页（相关产品循环）', 'tailwindscore' ),
			),
			'active_callback' => array(
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
