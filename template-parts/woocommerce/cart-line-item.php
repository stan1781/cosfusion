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
$quantity_name     = '';
$is_editable       = function_exists( 'tailwindscore_cart_item_is_editable' ) && tailwindscore_cart_item_is_editable( $cart_item, $cart_item_key );
$remove_label      = sprintf(
	/* translators: %s: product name */
	__( 'Remove %s from your cart', 'woocommerce' ),
	$product_name
);
?>
<li class="cart-drawer-cart__item" data-ts-cart-item data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
	<div class="cart-drawer-cart__media">
		<?php if ( $product_permalink ) : ?>
			<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_thumbnail ); ?></a>
		<?php else : ?>
			<?php echo wp_kses_post( $product_thumbnail ); ?>
		<?php endif; ?>
		
	</div>
	<div class="cart-drawer-cart__content stack">
		<div class="cart-drawer-cart__row">
			<div class="cart-drawer-cart__meta stack">
				<?php if ( $product_permalink ) : ?>
					<a class="cart-drawer-cart__name" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
				<?php else : ?>
					<span class="cart-drawer-cart__name"><?php echo esc_html( $product_name ); ?></span>
				<?php endif; ?>
				<?php tailwindscore_render_cart_line_item_meta( $cart_item, $cart_item_key ); ?>
				<span class="cart-drawer-cart__quantity">
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
		</div>
		<div class="cart-drawer-cart__controls">
			<?php
			tailwindscore_render_quantity_input(
				array(
					'input_id'       => 'cart-qty-' . $cart_item_key,
					'input_name'     => $quantity_name,
					'input_value'    => (string) $cart_item['quantity'],
					'min_value'      => '0',
					'max_value'      => $max_quantity,
					'cart_item_key'  => $cart_item_key,
					'context'        => $context,
					'aria_label'     => __( 'Product quantity', 'woocommerce' ),
					'remove_label'   => $remove_label,
				)
			);
			?>
			<?php if ( $is_editable ) : ?>
				<button
					type="button"
					class="cart-drawer-cart__edit"
					data-ts-cart-item-edit
					data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
					aria-label="<?php esc_attr_e( 'Edit options', 'tailwindscore' ); ?>"
				>
					<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'pencil' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</button>
			<?php endif; ?>
		</div>
		<div class="cart-drawer-cart__subtotal"><?php echo wp_kses_post( $product_subtotal ); ?></div>
	</div>
</li>
