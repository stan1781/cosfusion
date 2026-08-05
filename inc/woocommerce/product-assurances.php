<?php
declare(strict_types=1);

/**
 * 单品摘要「产品保障」列表（Kirki repeater）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kirki repeater 可选图标（Customizer 中文标签）。
 *
 * @return array<string, string>
 */
function tailwindscore_product_assurances_icon_choices(): array {
	return array(
		'truck'        => esc_html__( '配送', 'tailwindscore' ),
		'box-return'   => esc_html__( '退换', 'tailwindscore' ),
		'circle-check' => esc_html__( '勾选', 'tailwindscore' ),
		'package'      => esc_html__( '包裹', 'tailwindscore' ),
		'gift'         => esc_html__( '礼品', 'tailwindscore' ),
		'lock'         => esc_html__( '安全', 'tailwindscore' ),
	);
}

/**
 * Repeater 默认条目（前台英文；须与 Kirki default 一致）。
 *
 * @return array<int, array{icon: string, text: string, link_url: string, link_label: string}>
 */
function tailwindscore_product_assurances_items_default(): array {
	return array(
		array(
			'icon'       => 'truck',
			'text'       => __( 'Enjoy free shipping', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => '',
		),
		array(
			'icon'       => 'box-return',
			'text'       => __( '30-Day Free Returns & Exchanges', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => '',
		),
		array(
			'icon'       => 'circle-check',
			'text'       => __( 'Estimated delivery in 1–2 days', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => '',
		),
	);
}

/**
 * 规范化保障行（跳过空文案；非法图标回退 truck）。
 *
 * @param mixed $raw theme_mod 原始值。
 * @return array<int, array{icon: string, text: string, link_url: string, link_label: string}>
 */
function tailwindscore_product_assurances_normalize_items( $raw ): array {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$icon_keys = array_keys( tailwindscore_product_assurances_icon_choices() );
	$items     = array();

	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$text = trim( (string) ( $row['text'] ?? '' ) );

		if ( '' === $text ) {
			continue;
		}

		$icon = sanitize_key( (string) ( $row['icon'] ?? '' ) );

		if ( ! in_array( $icon, $icon_keys, true ) ) {
			$icon = 'truck';
		}

		$items[] = array(
			'icon'       => $icon,
			'text'       => $text,
			'link_url'   => trim( (string) ( $row['link_url'] ?? '' ) ),
			'link_label' => trim( (string) ( $row['link_label'] ?? '' ) ),
		);
	}

	return $items;
}

/**
 * @return array<int, array{icon: string, text: string, link_url: string, link_label: string}>
 */
function tailwindscore_get_product_assurances_items(): array {
	$stored = tailwindscore_mod( 'product_assurances_items', tailwindscore_product_assurances_items_default() );
	$items  = tailwindscore_product_assurances_normalize_items( $stored );

	/**
	 * 过滤单品保障列表。
	 *
	 * @param array<int, array{icon: string, text: string, link_url: string, link_label: string}> $items 列表。
	 */
	return (array) apply_filters( 'tailwindscore/product/assurances_items', $items );
}

/**
 * 是否渲染产品保障区块。
 */
function tailwindscore_product_assurances_should_show(): bool {
	if ( ! (bool) tailwindscore_mod( 'product_assurances_enable', true ) ) {
		return false;
	}

	$items = tailwindscore_get_product_assurances_items();

	if ( array() === $items ) {
		return false;
	}

	/**
	 * @param bool $show 是否显示。
	 */
	return (bool) apply_filters( 'tailwindscore/product/show_assurances', true );
}

/**
 * 在单品摘要加购下方输出保障列表。
 */
function tailwindscore_render_product_assurances(): void {
	if ( ! is_product() || ! tailwindscore_product_assurances_should_show() ) {
		return;
	}

	get_template_part(
		'template-parts/woocommerce/product-assurances',
		null,
		array(
			'items' => tailwindscore_get_product_assurances_items(),
		)
	);
}
add_action( 'woocommerce_single_product_summary', 'tailwindscore_render_product_assurances', 35 );

/**
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_product_assurances_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_assurances',
		array(
			'title'       => esc_html__( '产品保障', 'tailwindscore' ),
			'description' => esc_html__( '单品加购下方的配送 / 退换等政策提示（全站统一）。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 65,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_assurances_enable',
			'label'    => esc_html__( '显示产品保障', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_assurances',
			'default'  => true,
			'priority' => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'         => 'repeater',
			'settings'     => 'product_assurances_items',
			'label'        => esc_html__( '保障条目', 'tailwindscore' ),
			'description'  => esc_html__( '每行一项；空白文案的行前台会略过。', 'tailwindscore' ),
			'section'      => 'tailwindscore_product_assurances',
			'priority'     => 20,
			'row_label'    => array(
				'type'  => 'field',
				'value' => esc_html__( '条目', 'tailwindscore' ),
				'field' => 'text',
			),
			'button_label' => esc_html__( '新增条目', 'tailwindscore' ),
			'default'      => tailwindscore_product_assurances_items_default(),
			'fields'       => array(
				'icon'       => array(
					'type'    => 'select',
					'label'   => esc_html__( '图标', 'tailwindscore' ),
					'default' => 'truck',
					'choices' => tailwindscore_product_assurances_icon_choices(),
				),
				'text'       => array(
					'type'    => 'text',
					'label'   => esc_html__( '文案', 'tailwindscore' ),
					'default' => '',
				),
				'link_url'   => array(
					'type'    => 'text',
					'label'   => esc_html__( '链接 URL', 'tailwindscore' ),
					'default' => '',
				),
				'link_label' => array(
					'type'    => 'text',
					'label'   => esc_html__( '链接文字', 'tailwindscore' ),
					'default' => '',
				),
			),
			'active_callback' => array(
				array(
					'setting'  => 'product_assurances_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_product_assurances_kirki_register', 10, 2 );
