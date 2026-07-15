<?php
declare(strict_types=1);

function tailwindscore_woocommerce_setup(): void {
	add_theme_support( 'woocommerce' );
	// 单品图库 UI 由 modules/gallery（Swiper）提供；不使用 WC flexslider 栈。
}
add_action( 'after_setup_theme', 'tailwindscore_woocommerce_setup' );

function tailwindscore_disable_woocommerce_styles( array $styles ): array {
	unset( $styles['woocommerce-general'] );
	unset( $styles['woocommerce-layout'] );
	unset( $styles['woocommerce-smallscreen'] );

	return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'tailwindscore_disable_woocommerce_styles' );

/**
 * 用 woo.css 中的主题样式替换 WooCommerce 默认 Select2 样式表。
 */
function tailwindscore_dequeue_woocommerce_select2_style(): void {
	if ( ! function_exists( 'is_cart' ) ) {
		return;
	}

	if ( is_cart() || is_checkout() || is_account_page() ) {
		wp_dequeue_style( 'select2' );
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_dequeue_woocommerce_select2_style', 100 );

/**
 * 共享数量步进器 markup 与 WooCommerce class 钩子。
 */
function tailwindscore_quantity_input_classes( array $classes ): array {
	if ( ! in_array( 'quantity__input', $classes, true ) ) {
		$classes[] = 'quantity__input';
	}

	return $classes;
}
add_filter( 'woocommerce_quantity_input_classes', 'tailwindscore_quantity_input_classes' );

/**
 * 渲染主题数量步进器。
 *
 * @param array<string, mixed> $args template-parts/components/quantity-input.php 的模板参数。
 */
function tailwindscore_render_quantity_input( array $args = array() ): void {
	get_template_part( 'template-parts/components/quantity-input', null, $args );
}

function tailwindscore_render_product_price_row(): void {
	get_template_part( 'template-parts/woocommerce/product-price-row' );
}

function tailwindscore_setup_product_price_row(): void {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'tailwindscore_render_product_price_row', 10 );
}
add_action( 'init', 'tailwindscore_setup_product_price_row' );

