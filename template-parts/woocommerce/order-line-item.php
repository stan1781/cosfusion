<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$item = $args['item'] ?? null;

if ( ! $item instanceof WC_Order_Item_Product ) {
	return;
}

$product = $item->get_product();
if ( ! $product instanceof WC_Product ) {
	return;
}

$quantity          = (int) $item->get_quantity();
$product_name      = $item->get_name();
$product_permalink = $product->is_visible() ? $product->get_permalink() : '';
$product_thumbnail = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'checkout-line-item__img' ) );
$line_subtotal     = (float) $item->get_subtotal();
$product_subtotal  = $line_subtotal > 0 ? wc_price( $line_subtotal ) : wc_price( (float) $item->get_total() );
$meta              = tailwindscore_get_order_line_item_meta( $item );
?>
<li class="checkout-line-item checkout-line-item--thankyou">
	<div class="checkout-line-item__media">
		<?php if ( $product_permalink ) : ?>
			<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_thumbnail ); ?></a>
		<?php else : ?>
			<?php echo wp_kses_post( $product_thumbnail ); ?>
		<?php endif; ?>
		<?php if ( $quantity > 1 ) : ?>
			<span class="checkout-line-item__qty"><?php echo esc_html( (string) $quantity ); ?></span>
		<?php endif; ?>
	</div>
	<div class="checkout-line-item__info">
		<?php if ( $product_permalink ) : ?>
			<a class="checkout-line-item__name" href="<?php echo esc_url( $product_permalink ); ?>">
				<?php echo esc_html( $product_name ); ?>
			</a>
		<?php else : ?>
			<span class="checkout-line-item__name"><?php echo esc_html( $product_name ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $meta ) ) : ?>
			<?php
			tailwindscore_load_woocommerce_template_part(
				'cart-line-item-meta',
				array(
					'meta' => $meta,
				)
			);
			?>
		<?php endif; ?>
	</div>
	<div class="checkout-line-item__price"><?php echo wp_kses_post( $product_subtotal ); ?></div>
</li>
