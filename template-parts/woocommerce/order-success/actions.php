<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$order    = $args['order'] ?? null;
$shop_url = tailwindscore_get_thankyou_shop_url();
$wa_url   = $order instanceof WC_Order
	? tailwindscore_get_whatsapp_url(
		array(
			'context' => 'order',
			'order'   => $order,
		)
	)
	: tailwindscore_get_whatsapp_url();
?>
<section class="wc-thankyou__actions-wrap">
	<header class="wc-thankyou__actions-header">
		<span class="wc-thankyou__actions-header-icon" aria-hidden="true">
			<span class="icon"><?php echo tailwindscore_icon( 'circle-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</span>
		<h2 class="wc-thankyou__actions-title"><?php esc_html_e( 'What you can do next', 'tailwindscore' ); ?></h2>
	</header>
	<nav class="wc-thankyou__actions" aria-label="<?php esc_attr_e( 'Order actions', 'tailwindscore' ); ?>">
		<?php if ( '' !== $wa_url ) : ?>
			<a class="btn btn-outline wc-thankyou__action wc-thankyou__action--whatsapp" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="wc-thankyou__action-icon" aria-hidden="true">
					<span class="icon icon--solid"><?php echo tailwindscore_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</span>
				<span class="wc-thankyou__action-copy">
					<span class="wc-thankyou__action-label"><?php esc_html_e( 'Contact us on WhatsApp', 'tailwindscore' ); ?></span>
					<span class="wc-thankyou__action-desc"><?php esc_html_e( 'Reach our support team quickly', 'tailwindscore' ); ?></span>
				</span>
			</a>
		<?php endif; ?>
		<a class="btn wc-thankyou__action wc-thankyou__action--shop" href="<?php echo esc_url( $shop_url ); ?>">
			<span class="wc-thankyou__action-icon" aria-hidden="true">
				<span class="icon"><?php echo tailwindscore_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</span>
			<span class="wc-thankyou__action-copy">
				<span class="wc-thankyou__action-label"><?php esc_html_e( 'Continue shopping', 'tailwindscore' ); ?></span>
				<span class="wc-thankyou__action-desc"><?php esc_html_e( 'Browse more products', 'tailwindscore' ); ?></span>
			</span>
		</a>
		<?php if ( $order instanceof WC_Order && is_user_logged_in() && $order->get_user_id() === get_current_user_id() ) : ?>
			<a class="btn btn-outline wc-thankyou__action wc-thankyou__action--view" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
				<span class="wc-thankyou__action-icon" aria-hidden="true">
					<span class="icon"><?php echo tailwindscore_icon( 'clipboard' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</span>
				<span class="wc-thankyou__action-copy">
					<span class="wc-thankyou__action-label"><?php esc_html_e( 'View order', 'woocommerce' ); ?></span>
					<span class="wc-thankyou__action-desc"><?php esc_html_e( 'See full order details', 'tailwindscore' ); ?></span>
				</span>
			</a>
		<?php endif; ?>
	</nav>
</section>
