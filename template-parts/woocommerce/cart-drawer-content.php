<?php
declare(strict_types=1);

// The drawer is the primary place for cart edits, coupon entry, and cart-state feedback.
// The drawer and lightweight cart page must render line items through the same partial.

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart = WC()->cart;
?>
<div class="ts-cart-drawer-cart<?php echo $cart->is_empty() ? '' : ' ts-cart-drawer-cart--filled'; ?>">
	<?php tailwindscore_render_cart_feedback( array( 'surface' => 'drawer' ) ); ?>
	<?php if ( $cart->is_empty() ) : ?>
		<div class="ts-cart-drawer-cart__empty ts-stack">
			<p><?php esc_html_e( 'Your cart is empty.', 'tailwindscore' ); ?></p>
			<a class="ts-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'tailwindscore' ); ?></a>
		</div>
	<?php else : ?>
		<div class="ts-cart-drawer-cart__body">
			<ul class="ts-cart-drawer-cart__items" data-ts-cart-items>
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
		<div class="ts-cart-drawer-cart__coupon" data-ts-cart-coupon>
			<form class="ts-cart-drawer-cart__coupon-form ts-stack" data-ts-cart-coupon-form action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<label for="ts-cart-coupon-code"><?php esc_html_e( 'Coupon code', 'tailwindscore' ); ?></label>
				<div class="ts-input-group">
					<input
						id="ts-cart-coupon-code"
						class="ts-input"
						type="text"
						name="coupon_code"
						value=""
						autocomplete="off"
						data-ts-cart-coupon-input
						placeholder="<?php esc_attr_e( 'Enter code', 'tailwindscore' ); ?>"
					>
					<button class="ts-button ts-button--secondary" type="submit" data-ts-cart-coupon-submit>
						<?php esc_html_e( 'Apply', 'tailwindscore' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php tailwindscore_render_cart_summary( array( 'context' => 'drawer' ) ); ?>
	<?php endif; ?>
</div>
