<?php
declare(strict_types=1);

/**
 * 感谢页
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TailwindScore
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;

$shop_url = wc_get_page_permalink( 'shop' );
if ( ! is_string( $shop_url ) || '' === $shop_url ) {
	$shop_url = home_url( '/' );
}
?>

<div class="woocommerce-order wc-thankyou stack">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<header class="wc-thankyou__hero wc-thankyou__hero--failed">
				<h1 class="wc-thankyou__title woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
					<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?>
				</h1>
			</header>

			<nav class="wc-thankyou__actions woocommerce-thankyou-order-failed-actions" aria-label="<?php esc_attr_e( 'Order actions', 'tailwindscore' ); ?>">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay btn"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay btn btn-outline"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</nav>

		<?php else : ?>

			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>

			<section class="wc-thankyou__overview card" aria-label="<?php esc_attr_e( 'Order summary', 'tailwindscore' ); ?>">
				<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

					<li class="woocommerce-order-overview__order order">
						<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>

					<li class="woocommerce-order-overview__date date">
						<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
						<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>

					<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
						<li class="woocommerce-order-overview__email email">
							<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
							<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						</li>
					<?php endif; ?>

					<li class="woocommerce-order-overview__total total">
						<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>

					<?php if ( $order->get_payment_method_title() ) : ?>
						<li class="woocommerce-order-overview__payment-method method">
							<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
							<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
						</li>
					<?php endif; ?>

				</ul>
			</section>

			<nav class="wc-thankyou__actions" aria-label="<?php esc_attr_e( 'Order actions', 'tailwindscore' ); ?>">
				<a class="btn btn-outline" href="<?php echo esc_url( $shop_url ); ?>">
					<?php esc_html_e( 'Browse products', 'tailwindscore' ); ?>
				</a>
				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() ) : ?>
					<a class="btn" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						<?php esc_html_e( 'View order', 'tailwindscore' ); ?>
					</a>
				<?php endif; ?>
			</nav>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

		<nav class="wc-thankyou__actions" aria-label="<?php esc_attr_e( 'Order actions', 'tailwindscore' ); ?>">
			<a class="btn btn-outline" href="<?php echo esc_url( $shop_url ); ?>">
				<?php esc_html_e( 'Browse products', 'tailwindscore' ); ?>
			</a>
		</nav>

	<?php endif; ?>

</div>
