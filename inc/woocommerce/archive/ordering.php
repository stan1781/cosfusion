<?php
declare(strict_types=1);

/**
 * 归档排序：短标签与 orderby URL。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 工具栏触发器上的短标签。
 *
 * @param string $orderby_id catalog_orderby 键（如 date、price-desc）。
 * @param string $full_label WooCommerce 完整选项文案（未知 id 时回退）。
 */
function tailwindscore_archive_orderby_short_label( string $orderby_id, string $full_label = '' ): string {
	$map = array(
		'menu_order' => __( 'Default', 'tailwindscore' ),
		'popularity' => __( 'Popularity', 'tailwindscore' ),
		'rating'     => __( 'Rating', 'tailwindscore' ),
		'date'       => __( 'Latest', 'tailwindscore' ),
		'price'      => __( 'Price: Low', 'tailwindscore' ),
		'price-desc' => __( 'Price: High', 'tailwindscore' ),
	);

	if ( isset( $map[ $orderby_id ] ) ) {
		return $map[ $orderby_id ];
	}

	return '' !== $full_label ? $full_label : $orderby_id;
}

/**
 * 下拉菜单项文案：去掉 Woo「Sort by」前缀，避免半宽菜单里换行。
 *
 * @param string $orderby_id catalog_orderby 键。
 * @param string $full_label WooCommerce 完整选项文案。
 */
function tailwindscore_archive_orderby_menu_label( string $orderby_id, string $full_label ): string {
	$label = trim( $full_label );

	if ( '' === $label ) {
		return tailwindscore_archive_orderby_short_label( $orderby_id );
	}

	$stripped = preg_replace( '/^Sort by\s+/i', '', $label );

	if ( is_string( $stripped ) && '' !== $stripped ) {
		return $stripped;
	}

	return $label;
}

/**
 * 当前归档 URL 换 orderby，并去掉分页参数。
 *
 * @param string $orderby_id catalog_orderby 键。
 */
function tailwindscore_archive_orderby_url( string $orderby_id ): string {
	$url = remove_query_arg( array( 'paged', 'product-page' ) );

	return add_query_arg( 'orderby', sanitize_key( $orderby_id ), $url );
}
