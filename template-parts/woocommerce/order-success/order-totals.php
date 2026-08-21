<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$order = $args['order'] ?? null;

if ( ! $order instanceof WC_Order ) {
	return;
}

$discount_total = (float) $order->get_discount_total();
$shipping_total = (float) $order->get_shipping_total();
if ( $order->get_shipping_tax() > 0 ) {
	$shipping_total += (float) $order->get_shipping_tax();
}

$visible_fees = array();
foreach ( $order->get_fees() as $fee ) {
	if ( ! $fee instanceof WC_Order_Item_Fee ) {
		continue;
	}

	if ( (float) $fee->get_total() <= 0 ) {
		continue;
	}

	$visible_fees[] = $fee;
}
?>
<div class="order-totals wc-thankyou__totals">
	<div class="checkout-total-row">
		<span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
		<span><?php echo wp_kses_post( wc_price( (float) $order->get_subtotal() ) ); ?></span>
	</div>

	<?php if ( $discount_total > 0 ) : ?>
		<div class="checkout-total-row cart-discount">
			<span><?php esc_html_e( 'Discount', 'woocommerce' ); ?></span>
			<span>&minus;<?php echo wp_kses_post( wc_price( $discount_total ) ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( $order->get_shipping_method() ) : ?>
		<div class="checkout-total-row checkout-total-row--shipping">
			<span><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
			<span>
				<?php
				if ( $shipping_total <= 0 ) {
					esc_html_e( 'Free', 'tailwindscore' );
				} else {
					echo wp_kses_post( wc_price( $shipping_total ) );
				}
				?>
			</span>
		</div>
	<?php endif; ?>

	<?php foreach ( $visible_fees as $fee ) : ?>
		<div class="checkout-total-row fee">
			<span><?php echo esc_html( $fee->get_name() ); ?></span>
			<span><?php echo wp_kses_post( wc_price( (float) $fee->get_total() + (float) $fee->get_total_tax() ) ); ?></span>
		</div>
	<?php endforeach; ?>

	<?php if ( wc_tax_enabled() && (float) $order->get_total_tax() > 0 ) : ?>
		<div class="checkout-total-row tax-total">
			<span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
			<span><?php echo wp_kses_post( wc_price( (float) $order->get_total_tax() ) ); ?></span>
		</div>
	<?php endif; ?>

	<div class="checkout-total-row checkout-total-row--grand order-total">
		<span><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
		<span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
	</div>
</div>
