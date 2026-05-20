<?php
declare(strict_types=1);

function tailwindscore_wc_wrapper_open(): void {
	echo '<main class="site-main ts-main ts-main--commerce"><div class="ts-container ts-section ts-wc-shell">';
}

function tailwindscore_wc_wrapper_close(): void {
	echo '</div></main>';
}

function tailwindscore_cart_fragments( array $fragments ): array {
	$fragments['[data-ts-cart-badge]'] = tailwindscore_get_cart_fragment( 'tailwindscore_render_cart_badge' );
	$fragments['[data-ts-cart-screen-reader]'] = tailwindscore_get_cart_fragment( 'tailwindscore_render_cart_screen_reader_text' );
	$fragments['[data-ts-cart-summary]'] = esc_html( tailwindscore_cart_summary_text() );
	$fragments['[data-ts-cart-drawer-inner]'] = tailwindscore_get_cart_fragment( 'tailwindscore_render_cart_drawer' );

	return $fragments;
}

function tailwindscore_send_cart_fragments(): void {
	wp_send_json_success(
		array(
			'fragments' => tailwindscore_cart_fragments( array() ),
		)
	);
}

function tailwindscore_cart_error_message(): string {
	if ( function_exists( 'wc_get_notices' ) ) {
		$errors = wc_get_notices( 'error' );

		if ( ! empty( $errors[0]['notice'] ) ) {
			return wp_strip_all_tags( (string) $errors[0]['notice'] );
		}
	}

	return 'Unable to update cart.';
}

function tailwindscore_cart_has_item_key( string $cart_item_key ): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	$cart_item = WC()->cart->get_cart_item( $cart_item_key );

	return is_array( $cart_item ) && ! empty( $cart_item );
}

function tailwindscore_add_to_cart(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Cart unavailable.' ), 400 );
	}

	check_ajax_referer( 'tailwindscore-cart', 'nonce' );

	$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( (string) $_POST['product_id'] ) ) : 0;
	$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( (string) $_POST['quantity'] ) ) : 1;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( (string) $_POST['variation_id'] ) ) : 0;
	$variation    = array();

	if ( isset( $_POST['variation'] ) && is_array( $_POST['variation'] ) ) {
		foreach ( wp_unslash( $_POST['variation'] ) as $attribute_key => $attribute_value ) {
			$variation[ wc_clean( (string) $attribute_key ) ] = wc_clean( (string) $attribute_value );
		}
	}

	if ( $product_id < 1 || $quantity < 1 ) {
		wp_send_json_error( array( 'message' => 'Invalid add to cart payload.' ), 400 );
	}

	wc_clear_notices();

	$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

	if ( false === $added ) {
		wp_send_json_error( array( 'message' => tailwindscore_cart_error_message() ), 400 );
	}

	WC()->cart->calculate_totals();
	tailwindscore_send_cart_fragments();
}

function tailwindscore_update_cart_item_quantity(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Cart unavailable.' ), 400 );
	}

	check_ajax_referer( 'tailwindscore-cart', 'nonce' );

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( (string) $_POST['cart_item_key'] ) ) : '';
	$quantity      = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( (string) $_POST['quantity'] ) ) : null;

	if ( '' === $cart_item_key || null === $quantity || $quantity < 1 ) {
		wp_send_json_error( array( 'message' => 'Invalid cart payload.' ), 400 );
	}

	if ( ! tailwindscore_cart_has_item_key( $cart_item_key ) ) {
		wp_send_json_error( array( 'message' => 'Cart item not found.' ), 404 );
	}

	$updated = WC()->cart->set_quantity( $cart_item_key, $quantity, true );

	if ( false === $updated ) {
		wp_send_json_error( array( 'message' => 'Unable to update cart item.' ), 400 );
	}

	WC()->cart->calculate_totals();
	tailwindscore_send_cart_fragments();
}

function tailwindscore_remove_cart_item(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Cart unavailable.' ), 400 );
	}

	check_ajax_referer( 'tailwindscore-cart', 'nonce' );

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( (string) $_POST['cart_item_key'] ) ) : '';

	if ( '' === $cart_item_key ) {
		wp_send_json_error( array( 'message' => 'Invalid cart payload.' ), 400 );
	}

	if ( ! tailwindscore_cart_has_item_key( $cart_item_key ) ) {
		wp_send_json_error( array( 'message' => 'Cart item not found.' ), 404 );
	}

	$removed = WC()->cart->remove_cart_item( $cart_item_key );

	if ( false === $removed ) {
		wp_send_json_error( array( 'message' => 'Unable to remove cart item.' ), 400 );
	}

	WC()->cart->calculate_totals();
	tailwindscore_send_cart_fragments();
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

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
add_action( 'woocommerce_before_main_content', 'tailwindscore_wc_wrapper_open', 10 );
add_action( 'woocommerce_after_main_content', 'tailwindscore_wc_wrapper_close', 10 );
add_filter( 'woocommerce_add_to_cart_fragments', 'tailwindscore_cart_fragments' );
add_action( 'wp_ajax_tailwindscore_add_to_cart', 'tailwindscore_add_to_cart' );
add_action( 'wp_ajax_nopriv_tailwindscore_add_to_cart', 'tailwindscore_add_to_cart' );
add_action( 'wp_ajax_tailwindscore_update_cart_item_quantity', 'tailwindscore_update_cart_item_quantity' );
add_action( 'wp_ajax_nopriv_tailwindscore_update_cart_item_quantity', 'tailwindscore_update_cart_item_quantity' );
add_action( 'wp_ajax_tailwindscore_remove_cart_item', 'tailwindscore_remove_cart_item' );
add_action( 'wp_ajax_nopriv_tailwindscore_remove_cart_item', 'tailwindscore_remove_cart_item' );
add_action( 'template_redirect', 'tailwindscore_redirect_product_add_to_cart_post', 99 );
