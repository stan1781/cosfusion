<?php
declare(strict_types=1);

/**
 * 产品推荐共享引擎（PDP Cross-sells / 购物车瀑布）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 候选产品是否可作为推荐展示。
 *
 * @param WC_Product|null $product 商品。
 */
function tailwindscore_is_suggestion_product_eligible( ?WC_Product $product ): bool {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	if ( ! $product->is_visible() ) {
		return false;
	}

	if ( ! $product->is_purchasable() && ! $product->is_type( 'variable' ) ) {
		return false;
	}

	if ( $product->is_type( 'variable' ) ) {
		return $product->is_in_stock() || $product->backorders_allowed();
	}

	return $product->is_in_stock() || $product->backorders_allowed();
}

/**
 * 将原始 ID 列表过滤为可见可购的父级商品 ID（保序去重）。
 *
 * @param int[] $ids     原始 ID。
 * @param int[] $exclude 排除 ID。
 * @param int   $limit   上限（0 = 不截断）。
 * @return int[]
 */
function tailwindscore_filter_suggestion_product_ids( array $ids, array $exclude = array(), int $limit = 0 ): array {
	$exclude_lookup = array_fill_keys( array_map( 'absint', $exclude ), true );
	$result         = array();

	foreach ( $ids as $raw_id ) {
		$id = absint( $raw_id );
		if ( $id < 1 || isset( $exclude_lookup[ $id ] ) ) {
			continue;
		}

		$product = wc_get_product( $id );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		// Cross-sell 偶发写入 variation：回退到父商品。
		if ( $product->is_type( 'variation' ) ) {
			$parent_id = (int) $product->get_parent_id();
			if ( $parent_id < 1 || isset( $exclude_lookup[ $parent_id ] ) ) {
				continue;
			}
			$product = wc_get_product( $parent_id );
			$id      = $parent_id;
		}

		if ( ! tailwindscore_is_suggestion_product_eligible( $product ) ) {
			continue;
		}

		if ( isset( $exclude_lookup[ $id ] ) ) {
			continue;
		}

		// 已收录则跳过，保持首次出现顺序。
		if ( in_array( $id, $result, true ) ) {
			continue;
		}

		$result[]               = $id;
		$exclude_lookup[ $id ] = true;

		if ( $limit > 0 && count( $result ) >= $limit ) {
			break;
		}
	}

	return $result;
}

/**
 * 单品页 Cross-sells 推荐 ID。
 *
 * @param int $product_id 当前商品 ID。
 * @param int $limit      数量上限。
 * @return int[]
 */
function tailwindscore_get_pdp_cross_sell_suggestion_ids( int $product_id, int $limit = 6 ): array {
	$product_id = absint( $product_id );
	$limit      = max( 1, min( 12, $limit ) );

	if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$ids = tailwindscore_filter_suggestion_product_ids(
		$product->get_cross_sell_ids(),
		array( $product_id ),
		$limit
	);

	/**
	 * 过滤单品页 Cross-sell 推荐 ID。
	 *
	 * @param int[] $ids        推荐 ID。
	 * @param int   $product_id 当前商品。
	 * @param int   $limit      上限。
	 */
	return (array) apply_filters( 'tailwindscore_pdp_cross_sell_suggestion_ids', $ids, $product_id, $limit );
}

/**
 * 购物车内商品 ID（parent + variation）。
 *
 * @return int[]
 */
function tailwindscore_get_cart_suggestion_exclude_ids(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

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

	return array_values( array_unique( array_filter( $exclude ) ) );
}

/**
 * 购物车内商品所属 product_cat term ID。
 *
 * @return int[]
 */
function tailwindscore_get_cart_product_category_ids(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$term_ids = array();

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = (int) ( $cart_item['product_id'] ?? 0 );
		if ( $product_id < 1 ) {
			continue;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! is_array( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$term_ids[] = (int) $term->term_id;
			}
		}
	}

	return array_values( array_unique( array_filter( $term_ids ) ) );
}

/**
 * 按查询参数拉取推荐候选 ID（已做可见性过滤）。
 *
 * @param array<string, mixed> $query_args wc_get_products 参数。
 * @param int[]                $exclude    排除。
 * @param int                  $need       还需要多少。
 * @return int[]
 */
function tailwindscore_query_suggestion_product_ids( array $query_args, array $exclude, int $need ): array {
	if ( $need < 1 || ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$query_args = array_merge(
		array(
			'status' => 'publish',
			'type'   => array( 'simple', 'variable' ),
			'return' => 'objects',
		),
		$query_args
	);

	$query_args['limit']   = $need + count( $exclude );
	$query_args['exclude'] = array_values(
		array_unique(
			array_merge(
				isset( $query_args['exclude'] ) && is_array( $query_args['exclude'] )
					? array_map( 'absint', $query_args['exclude'] )
					: array(),
				array_map( 'absint', $exclude )
			)
		)
	);

	$products = wc_get_products( $query_args );
	if ( ! is_array( $products ) ) {
		return array();
	}

	$ids = array();
	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product || ! tailwindscore_is_suggestion_product_eligible( $product ) ) {
			continue;
		}
		$ids[] = (int) $product->get_id();
		if ( count( $ids ) >= $need ) {
			break;
		}
	}

	return $ids;
}

/**
 * 购物车推荐瀑布：Cross-sells → 车内分类 → 人气补齐。
 *
 * @param int    $limit   数量上限。
 * @param string $orderby 第 2/3 层排序字段。
 * @return int[]
 */
function tailwindscore_resolve_cart_suggestion_ids( int $limit = 8, string $orderby = 'popularity' ): array {
	$limit = max( 1, min( 12, $limit ) );
	$allowed_orderby = array( 'popularity', 'date', 'menu_order', 'title' );
	$orderby         = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'popularity';

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return array();
	}

	$exclude = tailwindscore_get_cart_suggestion_exclude_ids();
	$result  = array();

	// ① 合并车内各主品 Cross-sells（保运营顺序）。
	$cross_sell_ids = array();
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = (int) ( $cart_item['product_id'] ?? 0 );
		if ( $product_id < 1 ) {
			continue;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$cross_sell_ids = array_merge( $cross_sell_ids, $product->get_cross_sell_ids() );
	}

	$result = tailwindscore_filter_suggestion_product_ids( $cross_sell_ids, $exclude, $limit );

	// ② 车内商品所属分类补齐。
	if ( count( $result ) < $limit ) {
		$category_ids = tailwindscore_get_cart_product_category_ids();
		if ( array() !== $category_ids ) {
			$need     = $limit - count( $result );
			$fill_ids = tailwindscore_query_suggestion_product_ids(
				array(
					'orderby'   => $orderby,
					'order'     => 'DESC',
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $category_ids,
						),
					),
				),
				array_merge( $exclude, $result ),
				$need
			);
			$result = array_values( array_unique( array_merge( $result, $fill_ids ) ) );
		}
	}

	// ③ 人气（或配置的 orderby）全站补齐。
	if ( count( $result ) < $limit ) {
		$need     = $limit - count( $result );
		$fill_ids = tailwindscore_query_suggestion_product_ids(
			array(
				'orderby' => $orderby,
				'order'   => 'DESC',
			),
			array_merge( $exclude, $result ),
			$need
		);
		$result = array_values( array_unique( array_merge( $result, $fill_ids ) ) );
	}

	$result = array_slice( $result, 0, $limit );

	/**
	 * 过滤购物车瀑布推荐 ID。
	 *
	 * @param int[]  $result  推荐 ID。
	 * @param int    $limit   上限。
	 * @param string $orderby 补齐层排序。
	 */
	return (array) apply_filters( 'tailwindscore_resolved_cart_suggestion_ids', $result, $limit, $orderby );
}
