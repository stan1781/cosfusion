<?php
declare(strict_types=1);

/**
 * 产品 Swiper 轮播：短代码、查询与壳层渲染。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 将 slide 数量限制在 1.0–6.0，0.5 步进。
 *
 * @param mixed $value 原始值。
 */
function tailwindscore_sanitize_products_carousel_slide_count( $value ): float {
	$number = is_numeric( $value ) ? (float) $value : 1.0;
	$number = max( 1.0, min( 6.0, $number ) );

	return round( $number * 2.0 ) / 2.0;
}

/**
 * 轮播 slide 间距（px，0–48）。
 *
 * @param mixed $value 原始值。
 */
function tailwindscore_sanitize_products_carousel_gap( $value ): int {
	return max( 0, min( 48, absint( $value ) ) );
}

/**
 * 解析可选 gap：空 / 未设 → null（响应式 token 默认）；数字含 0 → px。
 *
 * @param mixed $value 原始值。
 */
function tailwindscore_parse_products_carousel_gap_optional( $value ): ?int {
	if ( null === $value ) {
		return null;
	}

	if ( is_string( $value ) && '' === trim( $value ) ) {
		return null;
	}

	if ( ! is_numeric( $value ) ) {
		return null;
	}

	return tailwindscore_sanitize_products_carousel_gap( $value );
}

/**
 * Kirki / 短代码列数选项（1–6，0.5 步进）。
 *
 * @return array<string, string>
 */
function tailwindscore_products_carousel_column_choices(): array {
	$choices = array();

	for ( $n = 2; $n <= 12; $n++ ) {
		$value           = (string) ( $n / 2 );
		$choices[ $value ] = $value;
	}

	return $choices;
}

/**
 * 宽屏列数选项：空 = 跟随桌面（不启用 lg 断点）。
 *
 * @return array<string, string>
 */
function tailwindscore_products_carousel_wide_column_choices(): array {
	// 用 + 而非 array_merge，避免 "1"/"2" 等数字键被重排。
	return array(
		'' => esc_html__( '跟随桌面', 'tailwindscore' ),
	) + tailwindscore_products_carousel_column_choices();
}

/**
 * 全局默认：手机可见 slide 数（theme_mod）。
 */
function tailwindscore_get_products_carousel_columns_mobile(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		tailwindscore_mod( 'products_carousel_columns_mobile', 1.5 )
	);
}

/**
 * 全局默认：桌面 md+ 可见 slide 数（theme_mod）。
 */
function tailwindscore_get_products_carousel_columns_desktop(): float {
	return tailwindscore_sanitize_products_carousel_slide_count(
		tailwindscore_mod( 'products_carousel_columns_desktop', 3.0 )
	);
}

/**
 * 全局默认：大屏 lg+ 可见 slide 数；空 theme_mod → null（跟随桌面）。
 */
function tailwindscore_get_products_carousel_columns_wide(): ?float {
	$raw = tailwindscore_mod( 'products_carousel_columns_wide', '4' );

	if ( null === $raw || '' === (string) $raw ) {
		return null;
	}

	return tailwindscore_sanitize_products_carousel_slide_count( $raw );
}

/**
 * 归一化卡形态与是否轮播；list 强制关 Swiper。
 *
 * @param mixed $card     grid|list。
 * @param mixed $carousel 1|0。
 * @return array{card: string, carousel: string}
 */
function tailwindscore_normalize_products_carousel_display( $card, $carousel ): array {
	$card = sanitize_key( (string) $card );
	if ( ! in_array( $card, array( 'grid', 'list' ), true ) ) {
		$card = 'grid';
	}

	$carousel_raw = is_numeric( $carousel ) ? (string) absint( $carousel ) : sanitize_key( (string) $carousel );
	$carousel     = in_array( $carousel_raw, array( '0', '1' ), true ) ? $carousel_raw : '1';

	if ( 'list' === $card ) {
		$carousel = '0';
	}

	return array(
		'card'     => $card,
		'carousel' => $carousel,
	);
}

/**
 * 静态网格列数（取整后夹在 1–6）。
 *
 * @param mixed $value 原始列数（可含小数）。
 */
function tailwindscore_products_carousel_static_columns( $value ): int {
	return max( 1, min( 6, (int) round( (float) $value ) ) );
}

/**
 * 解析短代码属性。
 *
 * @param array<string, string>|string $atts 短代码属性。
 * @return array<string, mixed>
 */
function tailwindscore_parse_products_carousel_atts( $atts ): array {
	$default_wide = tailwindscore_get_products_carousel_columns_wide();

	$parsed = shortcode_atts(
		array(
			'category'        => '',
			'limit'           => '8',
			'columns_mobile'  => (string) tailwindscore_get_products_carousel_columns_mobile(),
			'columns_desktop' => (string) tailwindscore_get_products_carousel_columns_desktop(),
			'columns_wide'    => null !== $default_wide ? (string) $default_wide : '',
			'gap'             => '',
			'orderby'         => 'date',
			'order'           => 'DESC',
			'title'           => '',
			'card'            => 'grid',
			'carousel'        => '1',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_products_carousel'
	);

	$limit   = max( 1, min( 24, absint( $parsed['limit'] ) ) );
	$orderby = sanitize_key( (string) $parsed['orderby'] );
	$allowed = array( 'date', 'popularity', 'menu_order', 'title' );

	if ( ! in_array( $orderby, $allowed, true ) ) {
		$orderby = 'date';
	}

	$order = strtoupper( sanitize_key( (string) $parsed['order'] ) );
	if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
		$order = 'DESC';
	}

	$categories = array_values(
		array_filter(
			array_map(
				static function ( string $part ): string {
					$part = trim( $part );
					if ( '' === $part ) {
						return '';
					}
					return is_numeric( $part ) ? (string) absint( $part ) : sanitize_title( $part );
				},
				explode( ',', (string) $parsed['category'] )
			)
		)
	);

	$raw_atts = is_array( $atts ) ? $atts : array();
	$display  = tailwindscore_normalize_products_carousel_display( $parsed['card'], $parsed['carousel'] );

	$columns_mobile  = tailwindscore_sanitize_products_carousel_slide_count( $parsed['columns_mobile'] );
	$columns_desktop = tailwindscore_sanitize_products_carousel_slide_count( $parsed['columns_desktop'] );

	$columns_wide_raw = trim( (string) $parsed['columns_wide'] );
	if ( array_key_exists( 'columns_wide', $raw_atts ) ) {
		$columns_wide = '' !== $columns_wide_raw
			? tailwindscore_sanitize_products_carousel_slide_count( $columns_wide_raw )
			: null;
	} else {
		$columns_wide = $default_wide;
	}

	// list：未显式指定列数时用 1 / 2；不套用全局 wide。
	if ( 'list' === $display['card'] ) {
		if ( ! array_key_exists( 'columns_mobile', $raw_atts ) ) {
			$columns_mobile = 1.0;
		}
		if ( ! array_key_exists( 'columns_desktop', $raw_atts ) ) {
			$columns_desktop = 2.0;
		}
		if ( ! array_key_exists( 'columns_wide', $raw_atts ) ) {
			$columns_wide = null;
		}
	}

	$gap = tailwindscore_parse_products_carousel_gap_optional( $parsed['gap'] );

	return array(
		'category'        => $categories,
		'limit'           => $limit,
		'columns_mobile'  => $columns_mobile,
		'columns_desktop' => $columns_desktop,
		'columns_wide'    => $columns_wide,
		'gap'             => $gap,
		'orderby'         => $orderby,
		'order'           => $order,
		'title'           => sanitize_text_field( (string) $parsed['title'] ),
		'card'            => $display['card'],
		'carousel'        => $display['carousel'],
	);
}

/**
 * 构建产品查询参数。
 *
 * @param array<string, mixed> $atts 已解析属性。
 * @return array<string, mixed>
 */
function tailwindscore_get_products_carousel_query_args( array $atts ): array {
	$args = array(
		'status'  => 'publish',
		'limit'   => (int) $atts['limit'],
		'orderby' => (string) $atts['orderby'],
		'order'   => (string) $atts['order'],
	);

	$categories = $atts['category'] ?? array();
	if ( is_array( $categories ) && array() !== $categories ) {
		$term_ids = array();
		$slugs    = array();

		foreach ( $categories as $category ) {
			if ( is_numeric( $category ) ) {
				$term_ids[] = (int) $category;
				continue;
			}

			$slug = sanitize_title( (string) $category );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		if ( array() !== $term_ids ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => array_values( array_unique( array_filter( $term_ids ) ) ),
				),
			);
		} elseif ( array() !== $slugs ) {
			$args['category'] = $slugs;
		}
	}

	/**
	 * 过滤产品轮播查询参数。
	 *
	 * @param array<string, mixed> $args 查询参数。
	 * @param array<string, mixed> $atts 已解析属性。
	 */
	return (array) apply_filters( 'tailwindscore_products_carousel_query_args', $args, $atts );
}

/**
 * @param array<string, mixed> $atts 已解析属性。
 * @return int[]
 */
function tailwindscore_get_products_carousel_ids( array $atts ): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$query_args = tailwindscore_get_products_carousel_query_args( $atts );
	$products   = wc_get_products( $query_args );

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

	return $ids;
}

/**
 * @param int[] $product_ids 按展示顺序的产品 ID。
 */
function tailwindscore_render_products_carousel_loop( array $product_ids ): string {
	if ( array() === $product_ids ) {
		return '';
	}

	$columns = min( 6, max( 1, count( $product_ids ) ) );
	wc_set_loop_prop( 'columns', $columns );

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post__in'               => $product_ids,
			'orderby'                => 'post__in',
			'posts_per_page'         => count( $product_ids ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		wc_reset_loop();

		return '';
	}

	ob_start();
	woocommerce_product_loop_start();

	while ( $query->have_posts() ) {
		$query->the_post();
		wc_get_template_part( 'content', 'product' );
	}

	wp_reset_postdata();
	woocommerce_product_loop_end();
	wc_reset_loop();

	return (string) ob_get_clean();
}

/**
 * 渲染产品轮播壳层（可传入已查询的 product IDs 或现成 loop HTML）。
 *
 * @param array<string, mixed> $config title / loop_html 或 product_ids / columns_* / gap / card / carousel / section_class / section_data_attrs。
 */
function tailwindscore_render_products_carousel_shell( array $config ): string {
	$product_ids = $config['product_ids'] ?? array();
	$loop_html   = isset( $config['loop_html'] ) ? (string) $config['loop_html'] : '';

	if ( '' === trim( $loop_html ) ) {
		if ( ! is_array( $product_ids ) || array() === $product_ids ) {
			return '';
		}
		$loop_html = tailwindscore_render_products_carousel_loop( $product_ids );
	}

	if ( '' === trim( $loop_html ) ) {
		return '';
	}

	$section_id = isset( $config['section_id'] )
		? (string) $config['section_id']
		: wp_unique_id( 'ts-product-carousel-' );

	if ( array_key_exists( 'columns_wide', $config ) ) {
		$columns_wide = $config['columns_wide'];
		if ( null !== $columns_wide && '' !== $columns_wide ) {
			$columns_wide = tailwindscore_sanitize_products_carousel_slide_count( $columns_wide );
		} else {
			$columns_wide = null;
		}
	} else {
		$columns_wide = tailwindscore_get_products_carousel_columns_wide();
	}

	$display  = tailwindscore_normalize_products_carousel_display(
		$config['card'] ?? 'grid',
		$config['carousel'] ?? '1'
	);
	$card     = $display['card'];
	$carousel = $display['carousel'];

	$section_class = trim( (string) ( $config['section_class'] ?? '' ) );
	if ( '0' === $carousel ) {
		$section_class = trim( $section_class . ' product-carousel--static' );
	}
	if ( 'list' === $card ) {
		$section_class = trim( $section_class . ' product-carousel--card-list' );
	}

	$section_data_attrs = is_array( $config['section_data_attrs'] ?? null )
		? $config['section_data_attrs']
		: array();
	$section_data_attrs['data-ts-card']     = $card;
	$section_data_attrs['data-ts-carousel'] = $carousel;

	$gap = array_key_exists( 'gap', $config )
		? tailwindscore_parse_products_carousel_gap_optional( $config['gap'] )
		: null;

	ob_start();
	get_template_part(
		'template-parts/woocommerce/product-carousel',
		null,
		array(
			'section_id'         => $section_id,
			'title'              => (string) ( $config['title'] ?? '' ),
			'loop_html'          => $loop_html,
			'columns_mobile'     => (float) ( $config['columns_mobile'] ?? tailwindscore_get_products_carousel_columns_mobile() ),
			'columns_desktop'    => (float) ( $config['columns_desktop'] ?? tailwindscore_get_products_carousel_columns_desktop() ),
			'columns_wide'       => $columns_wide,
			'gap'                => $gap,
			'card'               => $card,
			'carousel'           => $carousel,
			'section_class'      => $section_class,
			'section_data_attrs' => $section_data_attrs,
			'title_class'        => (string) ( $config['title_class'] ?? 'section-title--inline' ),
		)
	);

	return (string) ob_get_clean();
}

/**
 * @param array<string, mixed> $atts 已解析属性。
 */
function tailwindscore_render_products_carousel( array $atts ): string {
	$product_ids = tailwindscore_get_products_carousel_ids( $atts );

	if ( array() === $product_ids ) {
		return '';
	}

	$display = tailwindscore_normalize_products_carousel_display(
		$atts['card'] ?? 'grid',
		$atts['carousel'] ?? '1'
	);

	return tailwindscore_render_products_carousel_shell(
		array(
			'product_ids'     => $product_ids,
			'title'           => (string) ( $atts['title'] ?? '' ),
			'columns_mobile'  => (float) ( $atts['columns_mobile'] ?? tailwindscore_get_products_carousel_columns_mobile() ),
			'columns_desktop' => (float) ( $atts['columns_desktop'] ?? tailwindscore_get_products_carousel_columns_desktop() ),
			'columns_wide'    => array_key_exists( 'columns_wide', $atts ) ? $atts['columns_wide'] : tailwindscore_get_products_carousel_columns_wide(),
			'gap'             => array_key_exists( 'gap', $atts ) ? $atts['gap'] : null,
			'card'            => $display['card'],
			'carousel'        => $display['carousel'],
		)
	);
}

/**
 * @param array<string, string>|string $atts 短代码属性。
 */
function tailwindscore_products_carousel_shortcode( $atts ): string {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	return tailwindscore_render_products_carousel( tailwindscore_parse_products_carousel_atts( $atts ) );
}

add_shortcode( 'ts_products_carousel', 'tailwindscore_products_carousel_shortcode' );

/**
 * Customizer：商店 panel → 产品轮播全局列数。
 *
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_products_carousel_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$section = $targets['products_carousel'] ?? 'tailwindscore_products_carousel';
	$panel   = $targets['shop'] ?? tailwindscore_kirki_panel_shop();

	Kirki::add_section(
		$section,
		array(
			'title'       => esc_html__( '产品轮播', 'tailwindscore' ),
			'description' => esc_html__( '短代码未指定列数、Related、404 等共享默认；购物车推荐在「购物车」中单独设置。短代码显式属性优先。', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 40,
		)
	);

	$column_choices = tailwindscore_products_carousel_column_choices();

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'products_carousel_columns_mobile',
			'label'       => esc_html__( '手机可见列数', 'tailwindscore' ),
			'description' => esc_html__( 'md 以下 slidesPerView（可含小数，如 1.5）。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '1.5',
			'priority'    => 10,
			'choices'     => $column_choices,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'products_carousel_columns_desktop',
			'label'       => esc_html__( '桌面可见列数（md）', 'tailwindscore' ),
			'description' => esc_html__( 'md–lg 窄屏桌面 slidesPerView。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '3',
			'priority'    => 20,
			'choices'     => $column_choices,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'products_carousel_columns_wide',
			'label'       => esc_html__( '大屏可见列数（lg）', 'tailwindscore' ),
			'description' => esc_html__( 'lg+；选「跟随桌面」则不启用宽屏断点。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '4',
			'priority'    => 30,
			'choices'     => tailwindscore_products_carousel_wide_column_choices(),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_products_carousel_kirki_register', 20, 2 );
