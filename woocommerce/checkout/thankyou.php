<?php
declare(strict_types=1);

/**
 * 感谢页
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TailwindScore
 * @version 8.1.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order wc-thankyou">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<header class="wc-thankyou__hero wc-thankyou__hero--failed">
				<h1 class="wc-thankyou__title woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
					<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?>
				</h1>
			</header>

			<nav class="wc-thankyou__actions" aria-label="<?php esc_attr_e( 'Order actions', 'tailwindscore' ); ?>">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn">
					<?php esc_html_e( 'Pay', 'woocommerce' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="btn btn-outline">
						<?php esc_html_e( 'My account', 'woocommerce' ); ?>
					</a>
				<?php endif; ?>
			</nav>

		<?php else : ?>

			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>

			<div class="wc-thankyou__layout">
				<div class="wc-thankyou__main">
					<?php tailwindscore_render_order_success_partial( 'order-summary', array( 'order' => $order ) ); ?>
				</div>
				<aside class="wc-thankyou__aside">
					<?php tailwindscore_render_order_success_partial( 'shipping', array( 'order' => $order ) ); ?>
					<?php tailwindscore_render_order_success_partial( 'address', array( 'order' => $order ) ); ?>
				</aside>
			</div>

			<?php tailwindscore_thankyou_payment_instructions( $order->get_id() ); ?>

			<?php tailwindscore_render_order_success_partial( 'next-steps' ); ?>

			<?php tailwindscore_render_order_success_partial( 'actions', array( 'order' => $order ) ); ?>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

		<?php tailwindscore_render_order_success_partial( 'actions', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>
