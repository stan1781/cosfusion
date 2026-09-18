<?php
declare(strict_types=1);

/**
 * 订单客户地址（账单 / 配送）。
 *
 * @package TailwindScore
 * @version 8.7.0
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Order $order */

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
?>
<section class="woocommerce-customer-details wc-account-order-addresses">

	<div class="woocommerce-column woocommerce-column--billing-address wc-account-panel wc-account-panel--address">
		<h2 class="woocommerce-column__title wc-account-panel__title">
			<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span><?php esc_html_e( 'Billing address', 'woocommerce' ); ?></span>
		</h2>

		<address class="wc-account-panel__body">
			<?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

			<?php if ( $order->get_billing_phone() ) : ?>
				<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_billing_phone() ); ?></p>
			<?php endif; ?>

			<?php if ( $order->get_billing_email() ) : ?>
				<p class="woocommerce-customer-details--email"><?php echo esc_html( $order->get_billing_email() ); ?></p>
			<?php endif; ?>

			<?php
			/**
			 * Action hook fired after an address in the order customer details.
			 *
			 * @since 8.7.0
			 * @param string   $address_type Type of address (billing or shipping).
			 * @param WC_Order $order        Order object.
			 */
			do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order );
			?>
		</address>
	</div>

	<?php if ( $show_shipping ) : ?>
		<div class="woocommerce-column woocommerce-column--shipping-address wc-account-panel wc-account-panel--address">
			<h2 class="woocommerce-column__title wc-account-panel__title">
				<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></span>
			</h2>
			<address class="wc-account-panel__body">
				<?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

				<?php if ( $order->get_shipping_phone() ) : ?>
					<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
				<?php endif; ?>

				<?php
				/**
				 * Action hook fired after an address in the order customer details.
				 *
				 * @since 8.7.0
				 * @param string   $address_type Type of address (billing or shipping).
				 * @param WC_Order $order        Order object.
				 */
				do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order );
				?>
			</address>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>

</section>
