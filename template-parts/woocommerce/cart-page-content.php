<?php
declare(strict_types=1);

// 轻量购物车页主体 — 与抽屉使用相同的行项目与 Store API 钩子。

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart = WC()->cart;
?>
<div class="cart-page-cart<?php echo $cart->is_empty() ? '' : ' cart-page-cart--filled'; ?>">
	<?php if ( $cart->is_empty() ) : ?>
		<div class="cart-drawer-cart__empty stack">
			<p><?php esc_html_e( 'Your cart is empty.', 'tailwindscore' ); ?></p>
			<a class="btn btn-outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'tailwindscore' ); ?></a>
		</div>
		<?php if ( function_exists( 'tailwindscore_recently_viewed_show_on_cart' ) && tailwindscore_recently_viewed_show_on_cart() ) : ?>
			<?php echo tailwindscore_render_recently_viewed_host(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	<?php else : ?>
		<div class="wc-cart__scroll">
			<div class="cart-drawer-cart__body">
				<ul class="cart-drawer-cart__items wc-cart__items" data-ts-cart-items>
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
			<?php if ( function_exists( 'tailwindscore_render_cart_product_suggestions' ) ) : ?>
				<?php tailwindscore_render_cart_product_suggestions(); ?>
			<?php endif; ?>
		</div>
		<div class="wc-cart__sidebar">
			<?php tailwindscore_render_cart_free_shipping_progress(); ?>
			<aside class="wc-cart__aside">
				<div class="cart-drawer-cart__coupon" data-ts-cart-coupon>
					<?php tailwindscore_render_coupon_form( array( 'context' => 'cart' ) ); ?>
				</div>
				<div class="wc-cart__footer-bar">
					<?php tailwindscore_render_cart_summary( array( 'context' => 'cart' ) ); ?>
				</div>
			</aside>
		</div>
	<?php endif; ?>
</div>
