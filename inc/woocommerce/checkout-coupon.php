<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Detached checkout coupon shell (fields use the form="" attribute in coupon-form.php).
 */
function tailwindscore_checkout_coupon_form_shell(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! wc_coupons_enabled() ) {
		return;
	}

	static $rendered = false;

	if ( $rendered ) {
		return;
	}

	$rendered = true;
	?>
	<form
		id="woocommerce-checkout-form-coupon"
		class="checkout_coupon woocommerce-form-coupon ts-coupon-form"
		method="post"
		hidden
		aria-hidden="true"
		data-ts-checkout-coupon-form
	></form>
	<?php
}
add_action( 'woocommerce_after_checkout_form', 'tailwindscore_checkout_coupon_form_shell', 5 );

/**
 * Strip coupon fields from checkout post_data so update_order_review cannot resurrect a removed code.
 */
function tailwindscore_strip_coupon_from_checkout_post_data(): void {
	if ( empty( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$parsed = array();
	parse_str( (string) wp_unslash( $_POST['post_data'] ), $parsed ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	unset( $parsed['coupon_code'], $parsed['apply_coupon'] );

	$_POST['post_data'] = http_build_query( $parsed ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}
add_action( 'wp_ajax_woocommerce_update_order_review', 'tailwindscore_strip_coupon_from_checkout_post_data', 1 );
add_action( 'wp_ajax_nopriv_woocommerce_update_order_review', 'tailwindscore_strip_coupon_from_checkout_post_data', 1 );

/**
 * Redirect after ?remove_coupon= on checkout (WC removes the code but does not always redirect).
 */
function tailwindscore_checkout_remove_coupon_redirect(): void {
	if ( ! isset( $_GET['remove_coupon'] ) || ! function_exists( 'is_checkout' ) || ! is_checkout() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	wp_safe_redirect( remove_query_arg( 'remove_coupon', wc_get_checkout_url() ) );
	exit;
}
add_action( 'wp_loaded', 'tailwindscore_checkout_remove_coupon_redirect', 25 );

/**
 * Persist cart session immediately after coupon changes.
 */
function tailwindscore_persist_cart_after_coupon_change(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	WC()->cart->calculate_totals();
}
add_action( 'woocommerce_applied_coupon', 'tailwindscore_persist_cart_after_coupon_change', 99 );
add_action( 'woocommerce_removed_coupon', 'tailwindscore_persist_cart_after_coupon_change', 99 );
