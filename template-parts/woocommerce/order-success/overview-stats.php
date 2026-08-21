<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$order = $args['order'] ?? null;

if ( ! $order instanceof WC_Order ) {
	return;
}
?>
<section class="wc-thankyou__stats" aria-label="<?php esc_attr_e( 'Order overview', 'tailwindscore' ); ?>">
	<div class="wc-thankyou__stat">
		<span class="wc-thankyou__stat-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'clipboard' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<div class="wc-thankyou__stat-copy">
			<span class="wc-thankyou__stat-label"><?php esc_html_e( 'Order number', 'tailwindscore' ); ?></span>
			<strong class="wc-thankyou__stat-value">#<?php echo esc_html( $order->get_order_number() ); ?></strong>
		</div>
	</div>
	<div class="wc-thankyou__stat">
		<span class="wc-thankyou__stat-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<div class="wc-thankyou__stat-copy">
			<span class="wc-thankyou__stat-label"><?php esc_html_e( 'Order date', 'tailwindscore' ); ?></span>
			<strong class="wc-thankyou__stat-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'Y-m-d' ) ); ?></strong>
		</div>
	</div>
	<div class="wc-thankyou__stat">
		<span class="wc-thankyou__stat-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'package' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<div class="wc-thankyou__stat-copy">
			<span class="wc-thankyou__stat-label"><?php esc_html_e( 'Order total', 'tailwindscore' ); ?></span>
			<strong class="wc-thankyou__stat-value wc-thankyou__stat-value--total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
		</div>
	</div>
</section>
