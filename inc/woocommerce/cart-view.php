<?php
declare(strict_types=1);

// Purchase-flow rendering belongs in inc/woocommerce/cart-view.php.
// Generic site helpers stay in inc/template-tags.php.

function tailwindscore_render_cart_line_item( array $args = array() ): void {
	get_template_part( 'woocommerce/cart-line-item', null, $args );
}

function tailwindscore_render_cart_summary( array $args = array() ): void {
	get_template_part( 'woocommerce/cart-summary', null, $args );
}

function tailwindscore_render_cart_feedback( array $args = array() ): void {
	get_template_part( 'woocommerce/cart-feedback', null, $args );
}

function tailwindscore_render_cart_drawer_content(): void {
	get_template_part( 'woocommerce/cart-drawer-content' );
}

function tailwindscore_get_cart_fragment( string $callback ): string {
	ob_start();
	$callback();

	return (string) ob_get_clean();
}

function tailwindscore_render_cart_drawer(): void {
	tailwindscore_render_cart_drawer_content();
}
