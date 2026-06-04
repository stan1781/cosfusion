<?php
declare(strict_types=1);

// Lightweight cart page body — same line items and Store API hooks as the drawer.

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart = WC()->cart;
?>
<div class="ts-cart-page-cart<?php echo $cart->is_empty() ? '' : ' ts-cart-page-cart--filled'; ?>">
	<?php if ( $cart->is_empty() ) : ?>
		<div class="ts-cart-drawer-cart__empty ts-stack">
			<p><?php esc_html_e( 'Your cart is empty.', 'tailwindscore' ); ?></p>
			<a class="ts-button ts-button--outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'tailwindscore' ); ?></a>
		</div>
	<?php else : ?>
		<div class="ts-wc-cart__scroll">
			<div class="ts-cart-drawer-cart__body">
				<ul class="ts-cart-drawer-cart__items ts-wc-cart__items" data-ts-cart-items>
					<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
						<?php
						tailwindscore_render_cart_line_item(
							array(
								'cart_item_key' => $cart_item_key,
								'cart_item'     => $cart_item,
								'context'       => 'cart',
							)
						);
						?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<aside class="ts-wc-cart__aside">
			<div class="ts-cart-drawer-cart__coupon" data-ts-cart-coupon>
				<?php tailwindscore_render_coupon_form( array( 'context' => 'cart' ) ); ?>
			</div>
			<div class="ts-wc-cart__footer-bar">
				<?php tailwindscore_render_cart_summary( array( 'context' => 'cart' ) ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div>
