<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$order = $args['order'] ?? null;

if ( ! $order instanceof WC_Order ) {
	return;
}

$method         = trim( (string) $order->get_shipping_method() );
$payment_title  = trim( (string) $order->get_payment_method_title() );

if ( '' === $method && '' === $payment_title ) {
	return;
}
?>
<section class="wc-thankyou__card wc-thankyou__shipping" aria-label="<?php esc_attr_e( 'Delivery & payment', 'tailwindscore' ); ?>">
	<h2 class="wc-thankyou__card-title">
		<span class="wc-thankyou__card-title-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<span class="wc-thankyou__card-title-text"><?php esc_html_e( 'Delivery & payment', 'tailwindscore' ); ?></span>
	</h2>
	<dl class="wc-thankyou__detail-list">
		<?php if ( '' !== $method ) : ?>
			<div class="wc-thankyou__detail-row">
				<dt class="wc-thankyou__detail-term">
					<span class="wc-thankyou__detail-icon" aria-hidden="true">
						<span class="icon"><?php echo tailwindscore_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</span>
					<span class="wc-thankyou__detail-label"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
				</dt>
				<dd class="wc-thankyou__detail-value"><?php echo esc_html( $method ); ?></dd>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $payment_title ) : ?>
			<div class="wc-thankyou__detail-row">
				<dt class="wc-thankyou__detail-term">
					<span class="wc-thankyou__detail-icon" aria-hidden="true">
						<span class="icon"><?php echo tailwindscore_icon( 'circle-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</span>
					<span class="wc-thankyou__detail-label"><?php esc_html_e( 'Payment method', 'woocommerce' ); ?></span>
				</dt>
				<dd class="wc-thankyou__detail-value"><?php echo wp_kses_post( $payment_title ); ?></dd>
			</div>
		<?php endif; ?>
	</dl>
</section>
