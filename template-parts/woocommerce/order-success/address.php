<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$order = $args['order'] ?? null;

if ( ! $order instanceof WC_Order ) {
	return;
}

$shipping_html = $order->get_formatted_shipping_address();
$billing_html  = $order->get_formatted_billing_address();
$address_html  = '' !== $shipping_html ? $shipping_html : $billing_html;
$title         = '' !== $shipping_html
	? __( 'Shipping address', 'woocommerce' )
	: __( 'Billing address', 'woocommerce' );

if ( '' === $address_html ) {
	return;
}

$lines = array_values(
	array_filter(
		array_map( 'trim', preg_split( '/<br\s*\/?>/i', $address_html ) ?: array() )
	)
);
?>
<section class="wc-thankyou__card wc-thankyou__address" aria-label="<?php echo esc_attr( $title ); ?>">
	<h2 class="wc-thankyou__card-title">
		<span class="wc-thankyou__card-title-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<span class="wc-thankyou__card-title-text"><?php echo esc_html( $title ); ?></span>
	</h2>
	<address class="wc-thankyou__address-body">
		<?php foreach ( $lines as $line ) : ?>
			<span class="wc-thankyou__address-line"><?php echo wp_kses_post( $line ); ?></span>
		<?php endforeach; ?>
	</address>
</section>
