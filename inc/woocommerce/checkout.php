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

	get_template_part( 'template-parts/woocommerce/checkout-login-dialog' );
}

/**
 * Put billing email first in the checkout field order.
 *
 * @param array<string, mixed> $checkout_fields Checkout fields.
 * @return array<string, mixed>
 */
function tailwindscore_checkout_email_first( array $checkout_fields ): array {
	if ( isset( $checkout_fields['billing']['billing_email'] ) ) {
		$checkout_fields['billing']['billing_email']['priority'] = 4;
	}

	return $checkout_fields;
}
add_filter( 'woocommerce_checkout_fields', 'tailwindscore_checkout_email_first' );

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

	wp_dequeue_style( 'mailnest-frontend' );
	wp_dequeue_script( 'mailnest-frontend' );
	wp_deregister_style( 'mailnest-frontend' );
	wp_deregister_script( 'mailnest-frontend' );
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_dequeue_plugin_assets_on_checkout_cart', 999 );

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
