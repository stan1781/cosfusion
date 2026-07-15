<?php
declare(strict_types=1);

/**
 * Kirki：评论图片模块。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板/分区 ID。
 */
function tailwindscore_review_images_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_review_images',
		array(
			'title'       => esc_html__( '评论图片', 'tailwindscore' ),
			'description' => esc_html__( '单品页客户评论图片上传。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 68,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_review_images_enabled',
			'label'    => esc_html__( '启用评论图片', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'product_review_images_max_images',
			'label'    => esc_html__( '每条评论最大图片数', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => 6,
			'choices'  => array(
				'min'  => 1,
				'max'  => 10,
				'step' => 1,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'slider',
			'settings'    => 'product_review_images_max_file_size_mb',
			'label'       => esc_html__( '最大文件大小（MB）', 'tailwindscore' ),
			'section'     => 'tailwindscore_product_review_images',
			'default'     => 5,
			'choices'     => array(
				'min'  => 1,
				'max'  => 20,
				'step' => 1,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'product_review_images_max_width',
			'label'    => esc_html__( '最大图片宽度（px）', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => 1000,
			'choices'  => array(
				'min'  => 200,
				'max'  => 2000,
				'step' => 50,
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'slider',
			'settings' => 'product_review_images_max_height',
			'label'    => esc_html__( '最大图片高度（px）', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_review_images',
			'default'  => 1000,
			'choices'  => array(
				'min'  => 200,
				'max'  => 2000,
				'step' => 50,
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_review_images_kirki_register', 10, 2 );
