<?php
declare(strict_types=1);

/**
 * Checkout Payment Section
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment ts-loading-host">
	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li>';
				wc_print_notice(
					apply_filters(
						'woocommerce_no_available_payment_methods_message',
						WC()->customer->get_billing_country()
							? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' )
							: esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' )
					),
					'notice'
				);
				echo '</li>';
			}
			?>
		</ul>
	<?php endif; ?>
	<?php
	// Mobile payment recap: same order summary without coupon input (see sidebar primary summary for discount).
	add_filter( 'bootscore_checkout_show_coupon_in_order_review', '__return_false' );
	woocommerce_order_review();
	remove_filter( 'bootscore_checkout_show_coupon_in_order_review', '__return_false' );
	?>
	<div class="form-row place-order">
		<noscript>
			<?php
			printf(
				/* translators: %1$s and %2$s: emphasis tags */
				esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ),
				'<em>',
				'</em>'
			);
			?>
			<br />
			<button type="submit" class="button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt ts-button' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" name="woocommerce_checkout_place_order" id="place_order" data-ts-button-loading value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
