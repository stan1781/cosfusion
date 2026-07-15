<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

add_action( 'woocommerce_after_checkout_form', 'tailwindscore_checkout_login_dialog', 10 );

/**
 * Output checkout login dialog for guests (replaces default top-of-page login form).
 */
function tailwindscore_checkout_login_dialog(): void {
	if ( is_user_logged_in() ) {
		return;
	}

	get_template_part(
		'template-parts/header/account-login-dialog',
		null,
		array(
			'redirect' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' ),
		)
	);
}

add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

/**
 * Dequeue nonessential plugin assets on cart/checkout for performance.
 */
function tailwindscore_dequeue_plugin_assets_on_checkout_cart(): void {
	if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() ) ) {
		return;
	}

	wp_dequeue_style( 'wc-enhanced-ajax-search' );
	wp_dequeue_script( 'wc-enhanced-ajax-search' );
	wp_deregister_style( 'wc-enhanced-ajax-search' );
	wp_deregister_script( 'wc-enhanced-ajax-search' );

	if ( function_exists( 'tailwindscore_dequeue_mailnest_assets' ) ) {
		tailwindscore_dequeue_mailnest_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_dequeue_plugin_assets_on_checkout_cart', 999 );

/**
 * Drop nonessential runtime config globals on checkout (smaller inline script, no Ajax search init).
 *
 * @param array<string, mixed> $parts Runtime config fragments.
 * @return array<string, mixed>
 */
function tailwindscore_checkout_runtime_config_parts( array $parts ): array {
	if ( ! tailwindscore_is_checkout_page() ) {
		return $parts;
	}

	unset( $parts['ajaxSearch'], $parts['quickView'] );

	return $parts;
}
add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_checkout_runtime_config_parts', 1000 );

/**
 * Prevent cached checkout/cart HTML (stale AJAX nonces break update_order_review).
 */
function tailwindscore_nocache_woocommerce_flow_pages(): void {
	if ( ! function_exists( 'is_cart' ) ) {
		return;
	}

	if ( is_cart() || is_checkout() ) {
		nocache_headers();
	}
}
add_action( 'template_redirect', 'tailwindscore_nocache_woocommerce_flow_pages', 0 );
