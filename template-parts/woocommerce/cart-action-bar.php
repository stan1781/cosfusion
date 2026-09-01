<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
	return;
}

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<div class="action-bar action-bar--fixed wc-cart__action-bar" data-ts-cart-action-bar>
	<a class="action-bar__secondary-link" href="<?php echo esc_url( $shop_url ); ?>">
		<span class="action-bar__secondary-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
		</span>
		<span class="action-bar__secondary-label"><?php esc_html_e( 'Continue shopping', 'tailwindscore' ); ?></span>
	</a>
	<div class="action-bar__primary wc-cart__action-bar-primary">
		<a class="btn btn-primary action-bar__cta" href="<?php echo esc_url( tailwindscore_checkout_url() ); ?>" data-ts-button-loading>
			<span class="action-bar__cta-label">
				<?php esc_html_e( 'Checkout', 'tailwindscore' ); ?>
				(<span data-ts-cart-checkout-total><?php wc_cart_totals_order_total_html(); ?></span>)
			</span>
			<span class="action-bar__cta-icon" aria-hidden="true">
				<span class="icon"><?php echo tailwindscore_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
			</span>
		</a>
	</div>
</div>
