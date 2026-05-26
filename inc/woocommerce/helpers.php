<?php
declare(strict_types=1);

function tailwindscore_is_checkout_page(): bool {
	return function_exists( 'is_checkout' ) && is_checkout();
}

function tailwindscore_is_woo_page(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page() || is_page( 'register' );
}

/**
 * Whether optional header panels (search modal, cart drawer) should render.
 */
function tailwindscore_should_render_header_purchase_panels(): bool {
	return ! tailwindscore_is_checkout_page();
}

function tailwindscore_cart_url(): string {
	if ( function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}

	return home_url( '/' );
}

function tailwindscore_cart_count(): int {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

function tailwindscore_checkout_url(): string {
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}

	return home_url( '/' );
}

function tailwindscore_cart_summary_text(): string {
	$count = tailwindscore_cart_count();

	return sprintf(
		/* translators: %d: cart item count */
		_n( '%d item ready for checkout', '%d items ready for checkout', $count, 'tailwindscore' ),
		$count
	);
}
