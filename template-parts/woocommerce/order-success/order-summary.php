<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$order = $args['order'] ?? null;

if ( ! $order instanceof WC_Order ) {
	return;
}
?>
<section class="wc-thankyou__card wc-thankyou__order-summary" aria-label="<?php esc_attr_e( 'Order summary', 'tailwindscore' ); ?>">
	<h2 class="wc-thankyou__card-title">
		<span class="wc-thankyou__card-title-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'package' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<span class="wc-thankyou__card-title-text"><?php esc_html_e( 'Order summary', 'tailwindscore' ); ?></span>
	</h2>
	<ul class="wc-thankyou__items">
		<?php
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			if ( ! (bool) apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
				continue;
			}

			tailwindscore_render_order_line_item( $item );
		}
		?>
	</ul>
	<?php tailwindscore_render_order_success_partial( 'order-totals', array( 'order' => $order ) ); ?>
</section>
