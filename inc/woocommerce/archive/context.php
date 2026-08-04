<?php
declare(strict_types=1);

/**
 * Shop / 分类归档：当前请求上下文。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array{
 *     is_shop: bool,
 *     current_term: WP_Term|null,
 *     shop_label: string,
 *     category_label: string,
 *     sidebar_groups: array<int, array<string, mixed>>,
 *     active_filter_count: int
 * }
 */
function tailwindscore_archive_get_context(): array {
	static $context = null;

	if ( null !== $context ) {
		return $context;
	}

	$current_term = null;

	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$queried = get_queried_object();

		if ( $queried instanceof WP_Term ) {
			$current_term = $queried;
		}
	}

	$category_label = '';

	if ( $current_term instanceof WP_Term ) {
		$category_label = $current_term->name;
	}

	$is_shop    = function_exists( 'is_shop' ) && is_shop();
	$shop_label = __( 'Shop', 'tailwindscore' );

	$context = array(
		'is_shop'             => $is_shop,
		'current_term'        => $current_term,
		'shop_label'          => $shop_label,
		'category_label'      => $category_label,
		'sidebar_groups'      => tailwindscore_archive_get_filter_groups( $current_term ),
		'active_filter_count' => tailwindscore_archive_get_active_attribute_count(),
	);

	return $context;
}

/**
 * 当前请求是否为商店或产品分类法归档。
 */
function tailwindscore_is_product_archive(): bool {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return false;
	}

	return ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
}
