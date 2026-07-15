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
			'label'       => esc_html__( '缩略图条数量上限（桌面）', 'tailwindscore' ),
			'description' => esc_html__( '滚动前最多显示的缩略图数量。', 'tailwindscore' ),
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

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_gallery_variation_max_images',
			'label'       => esc_html__( '每个变体最大图片数（后台）', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_gallery',
			'default'     => 10,
			'choices'     => array(
				'min'  => 1,
				'max'  => 20,
				'step' => 1,
			),
			'sanitize_callback' => 'absint',
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_gallery_kirki_register', 10, 2 );
