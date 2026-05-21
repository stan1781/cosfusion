<?php
declare(strict_types=1);

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart_item_key = isset( $args['cart_item_key'] ) ? (string) $args['cart_item_key'] : '';
$cart_item     = $args['cart_item'] ?? null;
$context       = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'drawer';

if ( ! is_array( $cart_item ) && '' !== $cart_item_key ) {
	$cart_item = WC()->cart->get_cart_item( $cart_item_key );
}

if ( ! is_array( $cart_item ) ) {
	return;
}

$product = $cart_item['data'] ?? null;

if ( ! $product instanceof WC_Product || ! $product->exists() || (int) ( $cart_item['quantity'] ?? 0 ) <= 0 ) {
	return;
}

$product_name      = $product->get_name();
$product_permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
$product_price     = WC()->cart->get_product_price( $product );
$product_thumbnail = $product->get_image( 'woocommerce_thumbnail' );
$line_subtotal     = isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : 0;
$product_subtotal  = $line_subtotal > 0 ? wc_price( $line_subtotal ) : WC()->cart->get_product_subtotal( $product, (int) $cart_item['quantity'] );
$max_quantity      = $product->backorders_allowed() ? '' : (string) $product->get_max_purchase_quantity();
$remove_url        = wc_get_cart_remove_url( $cart_item_key );
$quantity_name     = 'cart' === $context ? 'cart[' . $cart_item_key . '][qty]' : '';
?>
<li class="ts-cart-drawer-cart__item" data-ts-cart-item data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
	<div class="ts-cart-drawer-cart__media">
		<?php if ( $product_permalink ) : ?>
			<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_thumbnail ); ?></a>
		<?php else : ?>
			<?php echo wp_kses_post( $product_thumbnail ); ?>
		<?php endif; ?>
	</div>
	<div class="ts-cart-drawer-cart__content ts-stack">
		<div class="ts-cart-drawer-cart__row">
			<div class="ts-cart-drawer-cart__meta ts-stack">
				<?php if ( $product_permalink ) : ?>
					<a class="ts-cart-drawer-cart__name" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
				<?php else : ?>
					<span class="ts-cart-drawer-cart__name"><?php echo esc_html( $product_name ); ?></span>
				<?php endif; ?>
				<span class="ts-cart-drawer-cart__quantity">
					<?php
					printf(
						/* translators: 1: quantity 2: price */
						esc_html__( '%1$d x %2$s', 'tailwindscore' ),
						(int) $cart_item['quantity'],
						wp_strip_all_tags( $product_price )
					);
					?>
				</span>
			</div>
			<?php if ( 'cart' === $context ) : ?>
				<a
					class="ts-cart-drawer-cart__remove"
					href="<?php echo esc_url( $remove_url ); ?>"
					aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from your cart', 'woocommerce' ), $product_name ) ); ?>"
				>
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'trash' ); ?></span>
				</a>
			<?php else : ?>
				<a
					class="ts-cart-drawer-cart__remove"
					href="<?php echo esc_url( $remove_url ); ?>"
					data-ts-cart-remove
					data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
					aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from your cart', 'woocommerce' ), $product_name ) ); ?>"
				>
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'trash' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
		<div class="ts-cart-drawer-cart__controls" data-ts-cart-quantity-controls>
			<button type="button" class="ts-cart-drawer-cart__quantity-button" data-ts-cart-quantity-step="-1" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Decrease quantity', 'tailwindscore' ); ?>">
				<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'minus' ); ?></span>
			</button>
			<label class="screen-reader-text" for="ts-cart-qty-<?php echo esc_attr( $cart_item_key ); ?>"><?php esc_html_e( 'Quantity', 'tailwindscore' ); ?></label>
			<input
				id="ts-cart-qty-<?php echo esc_attr( $cart_item_key ); ?>"
				class="ts-cart-drawer-cart__quantity-input"
				type="number"
				inputmode="numeric"
				min="0"
				<?php if ( '' !== $max_quantity ) : ?>
					max="<?php echo esc_attr( $max_quantity ); ?>"
				<?php endif; ?>
				step="1"
				value="<?php echo esc_attr( (string) $cart_item['quantity'] ); ?>"
				<?php if ( '' !== $quantity_name ) : ?>
					name="<?php echo esc_attr( $quantity_name ); ?>"
				<?php endif; ?>
				data-ts-cart-quantity-input
				data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
			>
			<button type="button" class="ts-cart-drawer-cart__quantity-button" data-ts-cart-quantity-step="1" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Increase quantity', 'tailwindscore' ); ?>">
				<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'plus' ); ?></span>
			</button>
		</div>
		<div class="ts-cart-drawer-cart__subtotal"><?php echo wp_kses_post( $product_subtotal ); ?></div>
	</div>
</li>
