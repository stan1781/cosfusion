<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 搜索提交时：关键词精确匹配商品 SKU 则直达产品详情页。
 *
 * 仅处理简单商品 / 可变商品父级；变体 SKU 与不可见商品不跳转。
 * 不介入 AJAX 实时搜索建议。
 *
 * @return void
 */
function tailwindscore_search_sku_redirect(): void {
	if ( is_admin() || ! function_exists( 'WC' ) || ! is_search() ) {
		return;
	}

	$sku = trim( (string) get_query_var( 's' ) );
	if ( '' === $sku ) {
		return;
	}

	$product_id = wc_get_product_id_by_sku( $sku );
	if ( $product_id < 1 ) {
		return;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	if ( $product->is_type( 'variation' ) ) {
		return;
	}

	if ( 'publish' !== $product->get_status() || ! $product->is_visible() ) {
		return;
	}

	$url = get_permalink( $product_id );
	if ( ! is_string( $url ) || '' === $url ) {
		return;
	}

	wp_safe_redirect( $url, 302 );
	exit;
}
add_action( 'template_redirect', 'tailwindscore_search_sku_redirect' );
