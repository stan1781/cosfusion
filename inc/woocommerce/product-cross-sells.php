<?php
declare(strict_types=1);

/**
 * 单品页 Cross-sells（配件 / 搭配）迷你轮播。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 是否启用单品 Cross-sells 区块。
 */
function tailwindscore_product_cross_sells_are_enabled(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	/**
	 * @param bool $enabled theme_mod 值。
	 */
	return (bool) apply_filters(
		'tailwindscore/product/cross_sells_enabled',
		(bool) tailwindscore_mod( 'product_cross_sells_enable', true )
	);
}

/**
 * 单品 Cross-sells 区块标题。
 */
function tailwindscore_get_product_cross_sells_title(): string {
	/**
	 * @param string $title 标题。
	 */
	return (string) apply_filters(
		'tailwindscore/product/cross_sells_title',
		(string) tailwindscore_mod(
			'product_cross_sells_title',
			__( 'Complete the look', 'tailwindscore' )
		)
	);
}

/**
 * 单品 Cross-sells 数量上限。
 */
function tailwindscore_get_product_cross_sells_limit(): int {
	$limit = (int) tailwindscore_mod( 'product_cross_sells_limit', 6 );

	/**
	 * @param int $limit 数量。
	 */
	return (int) apply_filters(
		'tailwindscore/product/cross_sells_limit',
		max( 1, min( 12, $limit ) )
	);
}

/**
 * 单品 Cross-sells 移动端可见 slide 数。
 */
function tailwindscore_get_product_cross_sells_columns_mobile(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		apply_filters( 'tailwindscore/product/cross_sells_columns_mobile', 2.5 )
	);
}

/**
 * 单品 Cross-sells 桌面端可见 slide 数。
 */
function tailwindscore_get_product_cross_sells_columns_desktop(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		apply_filters( 'tailwindscore/product/cross_sells_columns_desktop', 3.0 )
	);
}

/**
 * @return int[]
 */
function tailwindscore_get_product_cross_sell_ids_for_current(): array {
	if ( ! tailwindscore_product_cross_sells_are_enabled() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return array();
	}

	$product_id = 0;
	global $product;
	if ( $product instanceof WC_Product ) {
		$product_id = (int) $product->get_id();
	}

	if ( $product_id < 1 ) {
		$product_id = (int) get_queried_object_id();
	}

	if ( $product_id < 1 ) {
		return array();
	}

	return tailwindscore_get_pdp_cross_sell_suggestion_ids(
		$product_id,
		tailwindscore_get_product_cross_sells_limit()
	);
}

/**
 * 是否应展示单品 Cross-sells。
 */
function tailwindscore_should_show_product_cross_sells(): bool {
	if ( ! tailwindscore_product_cross_sells_are_enabled() ) {
		return false;
	}

	return array() !== tailwindscore_get_product_cross_sell_ids_for_current();
}

/**
 * 渲染单品 Cross-sells 迷你轮播。
 */
function tailwindscore_render_product_cross_sells(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! tailwindscore_should_show_product_cross_sells() ) {
		return;
	}

	$product_ids = tailwindscore_get_product_cross_sell_ids_for_current();
	if ( array() === $product_ids ) {
		return;
	}

	$title_tag_filter = static function (): string {
		return 'h3';
	};
	add_filter( 'woocommerce_loop_product_title_tag', $title_tag_filter );

	$markup = tailwindscore_render_products_carousel_shell(
		array(
			'product_ids'        => $product_ids,
			'title'              => tailwindscore_get_product_cross_sells_title(),
			'columns_mobile'     => tailwindscore_get_product_cross_sells_columns_mobile(),
			'columns_desktop'    => tailwindscore_get_product_cross_sells_columns_desktop(),
			'gap'                => 12,
			'section_class'      => 'product-cross-sells',
			'section_data_attrs' => array(
				'data-ts-product-cross-sells' => '',
			),
			'section_id'         => 'ts-product-cross-sells-title',
			'title_class'        => 'heading',
		)
	);

	remove_filter( 'woocommerce_loop_product_title_tag', $title_tag_filter );

	if ( '' === trim( $markup ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Carousel shell markup from theme template.
	echo $markup;
}

/**
 * 注册临时挂载点（PDP 改版时可通过 filter 改 hook / priority）。
 */
function tailwindscore_boot_product_cross_sells_hook(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$defaults = array(
		'hook'     => 'woocommerce_single_product_summary',
		'priority' => 32,
	);

	/**
	 * 过滤单品 Cross-sells 挂载点。
	 *
	 * @param array{hook: string, priority: int} $defaults 默认挂载。
	 */
	$config   = (array) apply_filters( 'tailwindscore/product/cross_sells_hook', $defaults );
	$hook     = isset( $config['hook'] ) ? (string) $config['hook'] : $defaults['hook'];
	$priority = isset( $config['priority'] ) ? (int) $config['priority'] : $defaults['priority'];

	if ( '' === $hook ) {
		return;
	}

	add_action( $hook, 'tailwindscore_render_product_cross_sells', $priority );
}
add_action( 'wp', 'tailwindscore_boot_product_cross_sells_hook' );

/**
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_product_cross_sells_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_product_cross_sells',
		array(
			'title'       => esc_html__( '配件搭配（Cross-sells）', 'tailwindscore' ),
			'description' => esc_html__( '单品页加购附近的 Cross-sells 迷你轮播（弱推荐，不与主品合并提交）。', 'tailwindscore' ),
			'panel'       => $targets['product'],
			'priority'    => 70,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'product_cross_sells_enable',
			'label'    => esc_html__( '显示配件搭配', 'tailwindscore' ),
			'section'  => 'tailwindscore_product_cross_sells',
			'default'  => true,
			'priority' => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'product_cross_sells_title',
			'label'           => esc_html__( '区块标题', 'tailwindscore' ),
			'section'         => 'tailwindscore_product_cross_sells',
			'default'         => __( 'Complete the look', 'tailwindscore' ),
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'product_cross_sells_enable', true );
			},
			'priority'        => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'slider',
			'settings'        => 'product_cross_sells_limit',
			'label'           => esc_html__( '显示数量', 'tailwindscore' ),
			'section'         => 'tailwindscore_product_cross_sells',
			'default'         => 6,
			'choices'         => array(
				'min'  => 1,
				'max'  => 12,
				'step' => 1,
			),
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'product_cross_sells_enable', true );
			},
			'priority'        => 30,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_product_cross_sells_kirki_register', 20, 2 );
