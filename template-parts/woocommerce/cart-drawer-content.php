<?php
declare(strict_types=1);

// 抽屉是编辑购物车的主要入口；优惠券输入为可选（Customizer）。
// 抽屉与轻量购物车页必须通过同一 partial 渲染行项目。

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart = WC()->cart;
?>
<div class="cart-drawer-cart<?php echo $cart->is_empty() ? '' : ' cart-drawer-cart--filled'; ?>">
	<?php if ( $cart->is_empty() ) : ?>
		<div class="cart-drawer-cart__empty stack">
			<p><?php esc_html_e( 'Your cart is empty.', 'tailwindscore' ); ?></p>
			<a class="btn btn-outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'tailwindscore' ); ?></a>
		</div>
	<?php else : ?>
		<div class="cart-drawer-cart__body">
			<?php tailwindscore_render_cart_free_shipping_progress(); ?>
			<ul class="cart-drawer-cart__items" data-ts-cart-items>
				<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
					<?php
					tailwindscore_render_cart_line_item(
						array(
							'cart_item_key' => $cart_item_key,
							'cart_item'     => $cart_item,
							'context'       => 'drawer',
						)
					);
					?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php if ( tailwindscore_cart_drawer_coupon_is_enabled() ) : ?>
			<div class="cart-drawer-cart__coupon" data-ts-cart-coupon>
				<?php tailwindscore_render_coupon_form( array( 'context' => 'drawer' ) ); ?>
			</div>
		<?php endif; ?>
		<?php tailwindscore_render_cart_summary( array( 'context' => 'drawer' ) ); ?>
	<?php endif; ?>
</div>
