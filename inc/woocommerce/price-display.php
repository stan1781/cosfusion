<?php
declare(strict_types=1);

/**
 * 简化可变商品价格 HTML，用于列表卡片、快速查看与搜索。
 *
 * 在 wc-enhanced-tools 多币种（优先级 10）之前运行，使转换后价格
 * 获得一致的 markup。
 *
 * @param string     $price   WooCommerce 默认价格 HTML。
 * @param WC_Product $product 商品实例。
 */
function tailwindscore_simplify_variable_price_display( string $price, WC_Product $product ): string {
	if ( ! function_exists( 'get_woocommerce_currency_symbol' ) || ! $product->is_type( 'variable' ) ) {
		return $price;
	}

	if ( $product->is_on_sale() ) {
		$regular_price = (float) $product->get_variation_regular_price( 'min', true );
		$sale_price    = (float) $product->get_variation_price( 'min', true );

		if ( $regular_price && $sale_price && $sale_price < $regular_price ) {
			return sprintf(
				'<del>%s</del> <ins>%s</ins>',
				wc_price( $regular_price ),
				wc_price( $sale_price )
			);
		}
	}

	$min_price = $product->get_variation_price( 'min', true );
	$max_price = $product->get_variation_price( 'max', true );

	if ( $min_price === $max_price ) {
		$currency_symbol = get_woocommerce_currency_symbol();
		$price_formatted = wc_format_decimal( $min_price, wc_get_price_decimals() );

		return sprintf(
			'<span class="woocommerce-Price-amount amount">' .
				'<bdi><span class="woocommerce-Price-currencySymbol">%s</span>%s</bdi>' .
			'</span>',
			esc_html( $currency_symbol ),
			esc_html( $price_formatted )
		);
	}

	$currency_symbol     = get_woocommerce_currency_symbol();
	$min_price_formatted = wc_format_decimal( $min_price, wc_get_price_decimals() );
	$max_price_formatted = wc_format_decimal( $max_price, wc_get_price_decimals() );

	$screen_reader_text = sprintf(
		/* translators: 1: minimum price, 2: maximum price */
		esc_html__( 'Price range: %1$s through %2$s', 'woocommerce' ),
		$currency_symbol . $min_price_formatted,
		$currency_symbol . $max_price_formatted
	);

	return sprintf(
		'<span aria-hidden="true">' .
			'<span class="from-text">%s </span>' .
			'<span class="woocommerce-Price-amount amount">' .
				'<bdi><span class="woocommerce-Price-currencySymbol">%s</span>%s</bdi>' .
			'</span>' .
		'</span>' .
		'<span class="screen-reader-text">%s</span>',
		esc_html__( 'From', 'woocommerce' ),
		esc_html( $currency_symbol ),
		esc_html( $min_price_formatted ),
		esc_html( $screen_reader_text )
	);
}

add_filter( 'woocommerce_get_price_html', 'tailwindscore_simplify_variable_price_display', 9, 2 );
