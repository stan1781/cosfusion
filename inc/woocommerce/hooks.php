<?php
declare(strict_types=1);

function tailwindscore_wc_wrapper_open(): void {
	echo '<main class="site-main ts-main ts-main--commerce">';

	if ( function_exists( 'tailwindscore_wc_archive_has_hero_layout' ) && tailwindscore_wc_archive_has_hero_layout() ) {
		return;
	}

	echo '<div class="ts-container ts-section ts-wc-shell">';
}

function tailwindscore_wc_wrapper_close(): void {
	echo '</div></main>';
}

function tailwindscore_redirect_product_add_to_cart_post(): void {
	if ( wp_doing_ajax() || is_admin() ) {
		return;
	}

	if ( 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return;
	}

	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product_id = isset( $_POST['add-to-cart'] ) ? absint( wp_unslash( (string) $_POST['add-to-cart'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $product_id < 1 ) {
		return;
	}

	if ( function_exists( 'wc_notice_count' ) && wc_notice_count( 'error' ) > 0 ) {
		return;
	}

	$permalink = get_permalink( $product_id );
	if ( ! is_string( $permalink ) || '' === $permalink ) {
		return;
	}

	wp_safe_redirect( $permalink, 303 );
	exit;
}

/**
 * Hide product meta (SKU, categories, tags) from non-administrators on single product pages.
 */
function tailwindscore_restrict_product_meta_to_admins(): void {
	if ( is_admin() || current_user_can( 'manage_options' ) ) {
		return;
	}

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
}
add_action( 'wp', 'tailwindscore_restrict_product_meta_to_admins' );

/**
 * Archive template renders notices outside the shop-loop toolbar.
 */
function tailwindscore_wc_archive_detach_loop_notices(): void {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return;
	}

	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}

	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
}
add_action( 'wp', 'tailwindscore_wc_archive_detach_loop_notices' );

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
add_action( 'woocommerce_before_main_content', 'tailwindscore_wc_wrapper_open', 10 );
add_action( 'woocommerce_after_main_content', 'tailwindscore_wc_wrapper_close', 10 );
add_action( 'template_redirect', 'tailwindscore_redirect_product_add_to_cart_post', 99 );
