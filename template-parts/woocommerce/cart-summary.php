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
<div class="ts-cart-drawer-cart__footer ts-stack<?php echo $is_cart_page ? ' ts-cart-drawer-cart__footer--cart-page' : ''; ?>" data-ts-cart-footer>
	<div class="ts-cart-drawer-cart__totals-group ts-stack">
		<?php if ( $discount_total > 0 ) : ?>
			<div class="ts-cart-drawer-cart__totals ts-cart-drawer-cart__totals--discount">
				<span><?php esc_html_e( 'Coupon discount', 'tailwindscore' ); ?></span>
				<strong class="ts-cart-drawer-cart__discount-amount">&minus;<?php echo wp_kses_post( wc_price( $discount_total ) ); ?></strong>
			</div>
		<?php endif; ?>
		<div class="ts-cart-drawer-cart__totals ts-cart-drawer-cart__totals--subtotal">
			<span><?php esc_html_e( 'Subtotal', 'tailwindscore' ); ?></span>
			<strong data-ts-cart-subtotal><?php echo wp_kses_post( wc_price( $subtotal_after ) ); ?></strong>
		</div>
	</div>
	<a class="ts-button<?php echo $is_cart_page ? ' ts-button--primary' : ''; ?>" href="<?php echo esc_url( tailwindscore_checkout_url() ); ?>" data-ts-button-loading>
		<?php esc_html_e( 'Checkout', 'tailwindscore' ); ?>
	</a>
	<?php if ( ! $is_cart_page ) : ?>
		<a class="ts-cart-drawer-cart__view-cart" href="<?php echo esc_url( tailwindscore_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'tailwindscore' ); ?></a>
	<?php endif; ?>
</div>
