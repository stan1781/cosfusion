<?php
declare(strict_types=1);

/**
 * 购物车产品推荐轮播。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 是否启用购物车推荐。
 */
function tailwindscore_cart_product_suggestions_are_enabled(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	/**
	 * 过滤购物车推荐是否启用。
	 *
	 * @param bool $enabled theme_mod 值。
	 */
	return (bool) apply_filters(
		'tailwindscore_cart_product_suggestions_enabled',
		(bool) tailwindscore_mod( 'cart_product_suggestions_enable', true )
	);
}

/**
 * 购物车推荐区块标题。
 */
function tailwindscore_get_cart_product_suggestions_title(): string {
	/**
	 * 过滤购物车推荐标题。
	 *
	 * @param string $title 标题。
	 */
	return (string) apply_filters(
		'tailwindscore_cart_product_suggestions_title',
		(string) tailwindscore_mod(
			'cart_product_suggestions_title',
			__( 'You may also like', 'tailwindscore' )
		)
	);
}

/**
 * 购物车推荐产品数量上限。
 */
function tailwindscore_get_cart_product_suggestions_limit(): int {
	$limit = (int) tailwindscore_mod( 'cart_product_suggestions_limit', 8 );

	/**
	 * 过滤购物车推荐数量上限。
	 *
	 * @param int $limit 数量。
	 */
	return (int) apply_filters(
		'tailwindscore_cart_product_suggestions_limit',
		max( 1, min( 12, $limit ) )
	);
}

/**
 * 购物车推荐排序字段。
 */
function tailwindscore_get_cart_product_suggestions_orderby(): string {
	$orderby = sanitize_key( (string) tailwindscore_mod( 'cart_product_suggestions_orderby', 'popularity' ) );
	$allowed = array( 'popularity', 'date', 'menu_order', 'title' );

	return in_array( $orderby, $allowed, true ) ? $orderby : 'popularity';
}

/**
 * 购物车推荐所属 product_cat term ID（0 = 不限分类）。
 */
function tailwindscore_get_cart_product_suggestions_category_id(): int {
	/**
	 * 过滤购物车推荐分类 ID。
	 *
	 * @param int $category_id 分类 ID。
	 */
	$category_id = (int) apply_filters(
		'tailwindscore_cart_product_suggestions_category_id',
		(int) tailwindscore_mod( 'cart_product_suggestions_category_id', 0 )
	);

	return max( 0, $category_id );
}

/**
 * 购物车推荐轮播移动端可见 slide 数。
 */
function tailwindscore_get_cart_product_suggestions_columns_mobile(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		apply_filters( 'tailwindscore_cart_product_suggestions_columns_mobile', 2.5 )
	);
}

/**
 * 购物车推荐轮播桌面端可见 slide 数。
 */
function tailwindscore_get_cart_product_suggestions_columns_desktop(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		apply_filters( 'tailwindscore_cart_product_suggestions_columns_desktop', 3.0 )
	);
}

/**
 * 是否应展示购物车推荐。
 */
function tailwindscore_should_show_cart_product_suggestions(): bool {
	if ( ! tailwindscore_cart_product_suggestions_are_enabled() ) {
		return false;
	}

	return array() !== tailwindscore_get_cart_product_suggestion_ids();
}

/**
 * @return int[]
 */
function tailwindscore_get_cart_product_suggestion_ids(): array {
	if ( ! tailwindscore_cart_product_suggestions_are_enabled() || ! function_exists( 'wc_get_products' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$limit   = tailwindscore_get_cart_product_suggestions_limit();
	$exclude = array();

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = (int) ( $cart_item['product_id'] ?? 0 );
		if ( $product_id > 0 ) {
			$exclude[] = $product_id;
		}

		$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
		if ( $variation_id > 0 ) {
			$exclude[] = $variation_id;
		}
	}

	$exclude = array_values( array_unique( array_filter( $exclude ) ) );

	$query_args = array(
		'status'  => 'publish',
		'type'    => array( 'simple', 'variable' ),
		'limit'   => $limit + count( $exclude ),
		'orderby' => tailwindscore_get_cart_product_suggestions_orderby(),
		'order'   => 'DESC',
		'exclude' => $exclude,
	);

	$category_id = tailwindscore_get_cart_product_suggestions_category_id();
	if ( $category_id > 0 ) {
		$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => array( $category_id ),
			),
		);
	}

	/**
	 * 过滤购物车推荐查询参数。
	 *
	 * @param array<string, mixed> $query_args 查询参数。
	 */
	$query_args = (array) apply_filters( 'tailwindscore_cart_product_suggestions_query_args', $query_args );

	$products = wc_get_products( $query_args );

	if ( ! is_array( $products ) ) {
		return array();
	}

	$ids = array();

	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			continue;
		}

		$ids[] = (int) $product->get_id();

		if ( count( $ids ) >= $limit ) {
			break;
		}
	}

	/**
	 * 过滤购物车推荐产品 ID。
	 *
	 * @param int[] $ids 产品 ID。
	 */
	return (array) apply_filters( 'tailwindscore_cart_product_suggestion_ids', $ids );
}

/**
 * 渲染购物车推荐产品轮播。
 */
function tailwindscore_render_cart_product_suggestions(): void {
	if ( ! tailwindscore_should_show_cart_product_suggestions() ) {
		return;
	}

	$product_ids = tailwindscore_get_cart_product_suggestion_ids();

	if ( array() === $product_ids ) {
		return;
	}

	$markup = tailwindscore_render_products_carousel_shell(
		array(
			'product_ids'        => $product_ids,
			'title'              => tailwindscore_get_cart_product_suggestions_title(),
			'columns_mobile'     => tailwindscore_get_cart_product_suggestions_columns_mobile(),
			'columns_desktop'    => tailwindscore_get_cart_product_suggestions_columns_desktop(),
			'gap'                => 16,
			'section_class'      => 'cart-suggestions',
			'section_data_attrs' => array(
				'data-ts-cart-suggestions' => '',
			),
			'section_id'         => 'ts-cart-suggestions-title',
			'title_class'        => 'heading',
		)
	);

	if ( '' === trim( $markup ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Carousel shell markup from theme template.
	echo $markup;
}

/**
 * 购物车推荐区块完整 HTML（供 Store API 刷新后 TS 重绘）。
 */
function tailwindscore_get_cart_product_suggestions_html(): string {
	if ( ! tailwindscore_cart_product_suggestions_are_enabled() ) {
		return '';
	}

	ob_start();
	tailwindscore_render_cart_product_suggestions();

	return (string) ob_get_clean();
}

/**
 * @param string               $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_cart_product_suggestions_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'cart_product_suggestions_enable',
			'label'       => esc_html__( '在购物车页显示商品推荐', 'tailwindscore' ),
			'description' => esc_html__( '仅在购物车页商品行下方显示商品轮播（迷你购物车抽屉中不显示）。', 'tailwindscore' ),
			'section'     => 'tailwindscore_cart_settings',
			'default'     => true,
			'priority'    => 40,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'cart_product_suggestions_title',
			'label'           => esc_html__( '推荐标题', 'tailwindscore' ),
			'section'         => 'tailwindscore_cart_settings',
			'default'         => __( 'You may also like', 'tailwindscore' ),
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'cart_product_suggestions_enable', true );
			},
			'priority'        => 41,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'slider',
			'settings'        => 'cart_product_suggestions_limit',
			'label'           => esc_html__( '推荐数量', 'tailwindscore' ),
			'section'         => 'tailwindscore_cart_settings',
			'default'         => 8,
			'choices'         => array(
				'min'  => 1,
				'max'  => 12,
				'step' => 1,
			),
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'cart_product_suggestions_enable', true );
			},
			'priority'        => 42,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'select',
			'settings'        => 'cart_product_suggestions_orderby',
			'label'           => esc_html__( '推荐排序', 'tailwindscore' ),
			'section'         => 'tailwindscore_cart_settings',
			'default'         => 'popularity',
			'choices'         => array(
				'popularity' => esc_html__( '热度', 'tailwindscore' ),
				'date'       => esc_html__( '最新', 'tailwindscore' ),
				'menu_order' => esc_html__( '菜单顺序', 'tailwindscore' ),
				'title'      => esc_html__( '标题', 'tailwindscore' ),
			),
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'cart_product_suggestions_enable', true );
			},
			'priority'        => 43,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'number',
			'settings'        => 'cart_product_suggestions_category_id',
			'label'           => esc_html__( '推荐分类 ID', 'tailwindscore' ),
			'description'     => esc_html__( '限制推荐到指定商品分类 term ID。填 0 表示不限分类。', 'tailwindscore' ),
			'section'         => 'tailwindscore_cart_settings',
			'default'         => 0,
			'choices'         => array(
				'min'  => 0,
				'step' => 1,
			),
			'active_callback' => static function (): bool {
				return (bool) tailwindscore_mod( 'cart_product_suggestions_enable', true );
			},
			'priority'        => 44,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_cart_product_suggestions_kirki_register', 20, 2 );
