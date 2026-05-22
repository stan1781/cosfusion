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

