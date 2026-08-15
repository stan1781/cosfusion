<?php
declare(strict_types=1);

/**
 * 404 页面：配置读取、快捷导航、人气商品轮播与 Kirki 登记。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 404 分类卡片可选图标（Kirki repeater select）。
 *
 * @return array<string, string>
 */
function tailwindscore_not_found_category_icon_choices(): array {
	return array(
		'sparkles' => esc_html__( '闪耀', 'tailwindscore' ),
		'package'  => esc_html__( '包裹', 'tailwindscore' ),
		'cart'     => esc_html__( '购物车', 'tailwindscore' ),
		'tag'      => esc_html__( '标签', 'tailwindscore' ),
		'home'     => esc_html__( '首页', 'tailwindscore' ),
		'truck'    => esc_html__( '配送', 'tailwindscore' ),
		'search'   => esc_html__( '搜索', 'tailwindscore' ),
		'user'     => esc_html__( '用户', 'tailwindscore' ),
	);
}

/**
 * Shop 页 URL 回退。
 */
function tailwindscore_not_found_default_shop_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( '/' );
}

/**
 * 404 默认插图本地路径（主题静态资源）。
 */
function tailwindscore_get_not_found_default_illustration_path(): string {
	return get_template_directory() . '/assets/img/not-found.png';
}

/**
 * 404 默认插图 URL（文件存在时）。
 */
function tailwindscore_get_not_found_default_illustration_url(): string {
	$path = tailwindscore_get_not_found_default_illustration_path();

	if ( ! is_readable( $path ) ) {
		return '';
	}

	return get_template_directory_uri() . '/assets/img/not-found.png';
}

/**
 * 404 分类卡片 repeater 默认行。
 *
 * @return array<int, array<string, string>>
 */
function tailwindscore_not_found_category_repeater_defaults(): array {
	$shop_url = tailwindscore_not_found_default_shop_url();

	return array(
		array(
			'title'    => __( 'New Arrivals', 'tailwindscore' ),
			'subtitle' => __( 'Fresh styles just in', 'tailwindscore' ),
			'url'      => $shop_url,
			'icon'     => 'sparkles',
		),
		array(
			'title'    => __( 'Best Sellers', 'tailwindscore' ),
			'subtitle' => __( 'Customer favorites', 'tailwindscore' ),
			'url'      => $shop_url,
			'icon'     => 'package',
		),
		array(
			'title'    => __( 'Dresses', 'tailwindscore' ),
			'subtitle' => __( 'Shop the edit', 'tailwindscore' ),
			'url'      => $shop_url,
			'icon'     => 'cart',
		),
		array(
			'title'    => __( 'Sale', 'tailwindscore' ),
			'subtitle' => __( 'Limited-time deals', 'tailwindscore' ),
			'url'      => $shop_url,
			'icon'     => 'tag',
		),
	);
}

/**
 * 404 Hero 插图 URL（Customizer 优先；空则回退主题 PNG；皆无则空串）。
 */
function tailwindscore_get_not_found_illustration_url(): string {
	$url = trim( (string) tailwindscore_mod( 'not_found_illustration', '' ) );

	if ( '' !== $url ) {
		return $url;
	}

	return tailwindscore_get_not_found_default_illustration_url();
}

/**
 * 是否启用快捷导航卡片。
 */
function tailwindscore_not_found_categories_are_enabled(): bool {
	return (bool) tailwindscore_mod( 'not_found_categories_enable', true );
}

/**
 * @return array<int, array{id: string, title: string, subtitle: string, url: string, icon: string}>
 */
function tailwindscore_get_not_found_categories(): array {
	if ( ! tailwindscore_not_found_categories_are_enabled() ) {
		return array();
	}

	$stored = tailwindscore_mod( 'not_found_categories', tailwindscore_not_found_category_repeater_defaults() );
	$rows   = is_array( $stored ) && array() !== $stored
		? $stored
		: tailwindscore_not_found_category_repeater_defaults();

	$icon_keys = array_keys( tailwindscore_not_found_category_icon_choices() );
	$items     = array();

	foreach ( $rows as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$url = trim( (string) ( $row['url'] ?? '' ) );
		if ( '' === $url ) {
			continue;
		}

		$icon = sanitize_key( (string) ( $row['icon'] ?? '' ) );
		if ( ! in_array( $icon, $icon_keys, true ) ) {
			$icon = 'sparkles';
		}

		$title = trim( (string) ( $row['title'] ?? '' ) );
		if ( '' === $title ) {
			continue;
		}

		$items[] = array(
			'id'       => 'not-found-cat-' . (int) $index,
			'title'    => $title,
			'subtitle' => trim( (string) ( $row['subtitle'] ?? '' ) ),
			'url'      => $url,
			'icon'     => $icon,
		);
	}

	/**
	 * 过滤 404 快捷导航卡片。
	 *
	 * @param array<int, array<string, string>> $items 卡片列表。
	 */
	return (array) apply_filters( 'tailwindscore_not_found_categories', $items );
}

/**
 * 快捷导航区块标题。
 */
function tailwindscore_get_not_found_categories_title(): string {
	return (string) tailwindscore_mod(
		'not_found_categories_title',
		__( 'Looking for something?', 'tailwindscore' )
	);
}

/**
 * Hero 标题。
 */
function tailwindscore_get_not_found_title(): string {
	return (string) tailwindscore_mod(
		'not_found_title',
		__( "Sorry, we can't find that page", 'tailwindscore' )
	);
}

/**
 * Hero 说明。
 */
function tailwindscore_get_not_found_description(): string {
	return (string) tailwindscore_mod(
		'not_found_description',
		__( 'The page may have been moved or no longer exists. Try the links below or head back home.', 'tailwindscore' )
	);
}

/**
 * 返回首页按钮文案。
 */
function tailwindscore_get_not_found_home_button_label(): string {
	return (string) tailwindscore_mod(
		'not_found_home_button_label',
		__( 'Back to homepage', 'tailwindscore' )
	);
}

/**
 * 人气商品区块标题。
 */
function tailwindscore_get_not_found_products_title(): string {
	return (string) tailwindscore_mod(
		'not_found_products_title',
		__( 'Popular right now', 'tailwindscore' )
	);
}

/**
 * 人气商品数量上限。
 */
function tailwindscore_get_not_found_products_limit(): int {
	$limit = (int) tailwindscore_mod( 'not_found_products_limit', 8 );

	/**
	 * 过滤 404 人气商品数量上限。
	 *
	 * @param int $limit 数量。
	 */
	return (int) apply_filters( 'tailwindscore_not_found_products_limit', max( 1, min( 12, $limit ) ) );
}

/**
 * 人气商品所属 product_cat term ID（0 = 不限分类）。
 */
function tailwindscore_get_not_found_products_category_id(): int {
	/**
	 * 过滤 404 人气商品分类 ID。
	 *
	 * @param int $category_id 分类 ID。
	 */
	$category_id = (int) apply_filters(
		'tailwindscore_not_found_products_category_id',
		(int) tailwindscore_mod( 'not_found_products_category_id', 0 )
	);

	return max( 0, $category_id );
}

/**
 * 人气商品轮播移动端可见 slide 数。
 */
function tailwindscore_get_not_found_products_columns_mobile(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		apply_filters( 'tailwindscore_not_found_products_columns_mobile', 2.5 )
	);
}

/**
 * 人气商品轮播桌面端可见 slide 数。
 */
function tailwindscore_get_not_found_products_columns_desktop(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		apply_filters( 'tailwindscore_not_found_products_columns_desktop', 4.0 )
	);
}

/**
 * 人气商品排序字段。
 */
function tailwindscore_get_not_found_products_orderby(): string {
	$orderby = sanitize_key( (string) tailwindscore_mod( 'not_found_products_orderby', 'popularity' ) );
	$allowed = array( 'popularity', 'date', 'menu_order', 'title' );

	return in_array( $orderby, $allowed, true ) ? $orderby : 'popularity';
}

/**
 * Kirki active_callback：快捷导航相关字段是否显示（与 tailwindscore_not_found_categories_are_enabled() 逻辑一致，
 * 供多个字段的 active_callback 复用，避免重复闭包）。
 */
function tailwindscore_not_found_categories_active_callback(): bool {
	return tailwindscore_not_found_categories_are_enabled();
}

/**
 * Kirki active_callback：人气商品相关字段是否显示（仅看未过滤的 theme_mod 原始值，
 * 供多个字段的 active_callback 复用；与 tailwindscore_not_found_products_are_enabled() 的
 * 过滤后判断不同，勿混用）。
 */
function tailwindscore_not_found_products_active_callback(): bool {
	return (bool) tailwindscore_mod( 'not_found_products_enable', true );
}

/**
 * 是否启用人气商品区块。
 */
function tailwindscore_not_found_products_are_enabled(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	/**
	 * 过滤 404 人气商品是否启用。
	 *
	 * @param bool $enabled theme_mod 值。
	 */
	return (bool) apply_filters(
		'tailwindscore_not_found_products_enabled',
		(bool) tailwindscore_mod( 'not_found_products_enable', true )
	);
}

/**
 * 是否应展示人气商品。
 */
function tailwindscore_not_found_should_show_products(): bool {
	if ( ! tailwindscore_not_found_products_are_enabled() ) {
		return false;
	}

	return array() !== tailwindscore_get_not_found_product_ids();
}

/**
 * @return int[]
 */
function tailwindscore_get_not_found_product_ids(): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$query_args = array(
		'status'  => 'publish',
		'type'    => array( 'simple', 'variable' ),
		'limit'   => tailwindscore_get_not_found_products_limit(),
		'orderby' => tailwindscore_get_not_found_products_orderby(),
		'order'   => 'DESC',
	);

	$category_id = tailwindscore_get_not_found_products_category_id();
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
	 * 过滤 404 人气商品查询参数。
	 *
	 * @param array<string, mixed> $query_args 查询参数。
	 */
	$query_args = (array) apply_filters( 'tailwindscore_not_found_products_query_args', $query_args );

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
	}

	/**
	 * 过滤 404 人气商品 ID。
	 *
	 * @param int[] $ids 产品 ID。
	 */
	return (array) apply_filters( 'tailwindscore_not_found_product_ids', $ids );
}

/**
 * 渲染 404 人气商品 Swiper 轮播。
 */
function tailwindscore_render_not_found_product_carousel(): void {
	$product_ids = tailwindscore_get_not_found_product_ids();

	if ( array() === $product_ids ) {
		return;
	}

	$markup = tailwindscore_render_products_carousel_shell(
		array(
			'product_ids'        => $product_ids,
			'title'              => tailwindscore_get_not_found_products_title(),
			'columns_mobile'     => tailwindscore_get_not_found_products_columns_mobile(),
			'columns_desktop'    => tailwindscore_get_not_found_products_columns_desktop(),
			'gap'                => 16,
			'section_class'      => 'not-found__products-carousel',
			'section_data_attrs' => array(
				'data-ts-not-found-products' => '',
			),
			'section_id'         => 'ts-not-found-products-title',
			'title_class'        => 'heading',
		)
	);

	if ( '' === trim( $markup ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 壳层 markup 来自主题模板。
	echo $markup;
}

/**
 * 登记 404 Customizer 字段。
 *
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_not_found_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_not_found',
		array(
			'title'    => esc_html__( '404 页面', 'tailwindscore' ),
			'priority' => tailwindscore_kirki_root_priority( 'not_found' ),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'not_found_illustration',
			'label'       => esc_html__( '插图', 'tailwindscore' ),
			'description' => esc_html__( '左侧装饰图；留空则使用主题预设 assets/img/not-found.png（若存在）。', 'tailwindscore' ),
			'section'     => 'tailwindscore_not_found',
			'default'     => '',
			'priority'    => 10,
			'choices'     => array(
				'save_as' => 'url',
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'not_found_title',
			'label'    => esc_html__( '标题', 'tailwindscore' ),
			'section'  => 'tailwindscore_not_found',
			'default'  => __( "Sorry, we can't find that page", 'tailwindscore' ),
			'priority' => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'not_found_description',
			'label'    => esc_html__( '说明', 'tailwindscore' ),
			'section'  => 'tailwindscore_not_found',
			'default'  => __( 'The page may have been moved or no longer exists. Try the links below or head back home.', 'tailwindscore' ),
			'priority' => 30,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'not_found_home_button_label',
			'label'    => esc_html__( '返回首页按钮', 'tailwindscore' ),
			'section'  => 'tailwindscore_not_found',
			'default'  => __( 'Back to homepage', 'tailwindscore' ),
			'priority' => 40,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'not_found_categories_enable',
			'label'    => esc_html__( '显示快捷导航', 'tailwindscore' ),
			'section'  => 'tailwindscore_not_found',
			'default'  => true,
			'priority' => 50,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'not_found_categories_title',
			'label'           => esc_html__( '快捷导航标题', 'tailwindscore' ),
			'section'         => 'tailwindscore_not_found',
			'default'         => __( 'Looking for something?', 'tailwindscore' ),
			'active_callback' => 'tailwindscore_not_found_categories_active_callback',
			'priority'        => 60,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'repeater',
			'settings'        => 'not_found_categories',
			'label'           => esc_html__( '导航卡片', 'tailwindscore' ),
			'description'     => esc_html__( '每行一张导航卡片；URL 可填商店或分类链接。', 'tailwindscore' ),
			'section'         => 'tailwindscore_not_found',
			'priority'        => 70,
			'row_label'       => array(
				'type'  => 'field',
				'value' => esc_html__( '卡片', 'tailwindscore' ),
				'field' => 'title',
			),
			'default'         => tailwindscore_not_found_category_repeater_defaults(),
			'active_callback' => 'tailwindscore_not_found_categories_active_callback',
			'fields'          => array(
				'title'    => array(
					'type'    => 'text',
					'label'   => esc_html__( '标题', 'tailwindscore' ),
					'default' => '',
				),
				'subtitle' => array(
					'type'    => 'text',
					'label'   => esc_html__( '副标题', 'tailwindscore' ),
					'default' => '',
				),
				'url'      => array(
					'type'    => 'text',
					'label'   => esc_html__( '链接 URL', 'tailwindscore' ),
					'default' => '',
				),
				'icon'     => array(
					'type'    => 'select',
					'label'   => esc_html__( '图标', 'tailwindscore' ),
					'default' => 'sparkles',
					'choices' => tailwindscore_not_found_category_icon_choices(),
				),
			),
		)
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'not_found_products_enable',
			'label'    => esc_html__( '显示人气商品', 'tailwindscore' ),
			'section'  => 'tailwindscore_not_found',
			'default'  => true,
			'priority' => 80,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'not_found_products_title',
			'label'           => esc_html__( '人气商品标题', 'tailwindscore' ),
			'section'         => 'tailwindscore_not_found',
			'default'         => __( 'Popular right now', 'tailwindscore' ),
			'active_callback' => 'tailwindscore_not_found_products_active_callback',
			'priority'        => 90,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'slider',
			'settings'        => 'not_found_products_limit',
			'label'           => esc_html__( '展示数量', 'tailwindscore' ),
			'section'         => 'tailwindscore_not_found',
			'default'         => 8,
			'priority'        => 100,
			'choices'         => array(
				'min'  => 1,
				'max'  => 12,
				'step' => 1,
			),
			'active_callback' => 'tailwindscore_not_found_products_active_callback',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'number',
			'settings'        => 'not_found_products_category_id',
			'label'           => esc_html__( '人气商品分类 ID', 'tailwindscore' ),
			'description'     => esc_html__( 'WooCommerce 产品分类 term ID；0 表示不限分类。', 'tailwindscore' ),
			'section'         => 'tailwindscore_not_found',
			'default'         => 0,
			'priority'        => 105,
			'choices'         => array(
				'min'  => 0,
				'max'  => 99999,
				'step' => 1,
			),
			'active_callback' => 'tailwindscore_not_found_products_active_callback',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'select',
			'settings'        => 'not_found_products_orderby',
			'label'           => esc_html__( '排序方式', 'tailwindscore' ),
			'section'         => 'tailwindscore_not_found',
			'default'         => 'popularity',
			'priority'        => 110,
			'choices'         => array(
				'popularity' => esc_html__( '热度', 'tailwindscore' ),
				'date'       => esc_html__( '最新', 'tailwindscore' ),
				'menu_order' => esc_html__( '菜单顺序', 'tailwindscore' ),
				'title'      => esc_html__( '标题', 'tailwindscore' ),
			),
			'active_callback' => 'tailwindscore_not_found_products_active_callback',
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_not_found_kirki_register', 10, 2 );
