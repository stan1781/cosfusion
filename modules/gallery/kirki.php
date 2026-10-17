<?php
declare(strict_types=1);

/**
 * Kirki：商品画廊模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_gallery_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_gallery',
		array(
			'title'       => esc_html__( '商品画廊', 'tailwindscore' ),
			'description' => esc_html__( '单品页 Swiper 画廊。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 65,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_gallery_thumb_limit',
			'label'       => esc_html__( '缩略图均分下限张数（桌面）', 'tailwindscore' ),
			'description' => esc_html__( '用于推导缩略图最小边长：列高按此张数均分。实际张数更少时放大铺满；更多时用该下限尺寸并滚动居中。', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_gallery',
			'default'     => 6,
			'choices'     => array(
				'min'  => 3,
				'max'  => 12,
				'step' => 1,
			),
			'sanitize_callback' => 'absint',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'product_gallery_show_counter',
			'label'       => esc_html__( '显示幻灯片计数', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_gallery',
			'default'     => true,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_gallery_kirki_register', 10, 2 );
