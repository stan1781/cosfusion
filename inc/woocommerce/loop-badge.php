<?php
declare(strict_types=1);

/**
 * Sale badge for product loop cards.
 */
function tailwindscore_render_loop_sale_badge( WC_Product $product ): void {
	if ( ! $product->is_on_sale() ) {
		return;
	}

	global $post;

	$post = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	if ( ! $post ) {
		return;
	}

	$flash = apply_filters(
		'woocommerce_sale_flash',
		'<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>',
		$post,
		$product
	);

	if ( '' === $flash ) {
		return;
	}

	echo '<div class="ts-wc-product-card__badges">';
	echo $flash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
}
