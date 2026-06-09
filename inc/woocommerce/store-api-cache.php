<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Store API and purchase-flow endpoints must never be cached.
 * Full-page static cache (e.g. Baota) otherwise serves stale cart JSON and nonces.
 */

/**
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response REST response.
 * @param WP_REST_Server                                  $server   REST server.
 * @param WP_REST_Request                                 $request  REST request.
 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed
 */
function tailwindscore_store_api_nocache_headers( $response, WP_REST_Server $server, WP_REST_Request $request ) {
	$route = $request->get_route();

	if ( ! is_string( $route ) || ! str_starts_with( $route, '/wc/store/' ) ) {
		return $response;
	}

	if ( $response instanceof WP_REST_Response ) {
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );
		$response->header( 'Vary', 'Cookie' );
	}

	return $response;
}
add_filter( 'rest_post_dispatch', 'tailwindscore_store_api_nocache_headers', 10, 3 );

/**
 * When the shopper already has items in cart, do not serve a cached HTML shell.
 */
function tailwindscore_nocache_when_cart_has_items(): void {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
		return;
	}

	if ( is_cart() || is_checkout() ) {
		return;
	}

	if ( empty( $_COOKIE['woocommerce_items_in_cart'] ) ) {
		return;
	}

	nocache_headers();
}
add_action( 'template_redirect', 'tailwindscore_nocache_when_cart_has_items', 0 );

/**
 * Hint reverse proxies to vary full-page cache by session cookies.
 */
function tailwindscore_vary_cache_on_cookies(): void {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
		return;
	}

	header( 'Vary: Cookie', false );
}
add_action( 'send_headers', 'tailwindscore_vary_cache_on_cookies', 0 );
