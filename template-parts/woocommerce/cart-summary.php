<?php
declare(strict_types=1);

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart    = WC()->cart;
$context = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'drawer';

if ( $cart->is_empty() ) {
	return;
}

$is_cart_page    = 'cart' === $context;
$discount_total  = (float) $cart->get_discount_total();
$subtotal_before = (float) $cart->get_subtotal();
$subtotal_after  = max( 0, $subtotal_before - $discount_total );
?>
<div class="cart-drawer-cart__footer stack<?php echo $is_cart_page ? ' cart-drawer-cart__footer--cart-page' : ''; ?>" data-ts-cart-footer>
	<div class="cart-drawer-cart__totals-group stack">
		<?php if ( $discount_total > 0 ) : ?>
			<div class="cart-drawer-cart__totals cart-drawer-cart__totals--discount">
				<span><?php esc_html_e( 'Coupon discount', 'tailwindscore' ); ?></span>
				<strong class="cart-drawer-cart__discount-amount">&minus;<?php echo wp_kses_post( wc_price( $discount_total ) ); ?></strong>
			</div>
		<?php endif; ?>
		<div class="cart-drawer-cart__totals cart-drawer-cart__totals--subtotal">
			<span><?php esc_html_e( 'Subtotal', 'tailwindscore' ); ?></span>
			<strong data-ts-cart-subtotal><?php echo wp_kses_post( wc_price( $subtotal_after ) ); ?></strong>
		</div>
		<?php if ( $is_cart_page ) : ?>
			<p class="wc-cart__shipping-note"><?php esc_html_e( 'Shipping and taxes calculated at checkout.', 'tailwindscore' ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( ! $is_cart_page ) : ?>
		<a class="btn" href="<?php echo esc_url( tailwindscore_checkout_url() ); ?>" data-ts-button-loading>
			<?php esc_html_e( 'Checkout', 'tailwindscore' ); ?>
		</a>
		<a class="cart-drawer-cart__view-cart" href="<?php echo esc_url( tailwindscore_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'tailwindscore' ); ?></a>
	<?php endif; ?>
</div>
