<?php
declare(strict_types=1);

function tailwindscore_render_product_price_row(): void {
	get_template_part( 'template-parts/woocommerce/product-price-row' );
}

function tailwindscore_setup_product_price_row(): void {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'tailwindscore_render_product_price_row', 10 );
}
add_action( 'init', 'tailwindscore_setup_product_price_row' );
