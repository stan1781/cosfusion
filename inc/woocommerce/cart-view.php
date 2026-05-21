<?php
declare(strict_types=1);

// Purchase-flow rendering belongs in inc/woocommerce/cart-view.php.
// Generic site helpers stay in inc/template-tags.php.

/**
 * Load a WooCommerce template part from template-parts/woocommerce/.
 *
 * Uses require() so $args is available inside the partial (WP 6.9 load_template no longer extracts $args).
 */
function tailwindscore_load_woocommerce_template_part( string $slug, array $args = array() ): void {
	$template = get_template_directory() . '/template-parts/woocommerce/' . $slug . '.php';

	if ( ! is_readable( $template ) ) {
		return;
	}

	require $template;
}

function tailwindscore_render_cart_line_item( array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'cart-line-item', $args );
}

function tailwindscore_render_cart_summary( array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'cart-summary', $args );
}

function tailwindscore_render_cart_feedback( array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'cart-feedback', $args );
}

function tailwindscore_render_coupon_form( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'context' => 'drawer',
		)
	);

	tailwindscore_load_woocommerce_template_part( 'coupon-form', $args );
}

function tailwindscore_render_cart_drawer_content(): void {
	tailwindscore_load_woocommerce_template_part( 'cart-drawer-content' );
}

function tailwindscore_get_cart_fragment( string $callback ): string {
	ob_start();
	$callback();

	return (string) ob_get_clean();
}

function tailwindscore_render_cart_drawer(): void {
	tailwindscore_render_cart_drawer_content();
}
