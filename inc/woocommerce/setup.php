<?php
declare(strict_types=1);

function tailwindscore_woocommerce_setup(): void {
	add_theme_support( 'woocommerce' );
	// Product gallery UI is provided by modules/gallery (Swiper); no WC flexslider stack.
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
 * Replace WooCommerce's default Select2 stylesheet with theme styles in woo.css.
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
 * Shared quantity stepper markup and WooCommerce class hooks.
 */
function tailwindscore_quantity_input_classes( array $classes ): array {
	if ( ! in_array( 'ts-quantity__input', $classes, true ) ) {
		$classes[] = 'ts-quantity__input';
	}

	return $classes;
}
add_filter( 'woocommerce_quantity_input_classes', 'tailwindscore_quantity_input_classes' );

/**
 * Render the theme quantity stepper.
 *
 * @param array<string, mixed> $args Template arguments for template-parts/components/quantity-input.php.
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

