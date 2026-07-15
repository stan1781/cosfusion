<?php
declare(strict_types=1);

/**
 * 结账表单
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html(
		apply_filters(
			'woocommerce_checkout_must_be_logged_in_message',
			__( 'You must be logged in to checkout.', 'woocommerce' )
		)
	);
	return;
}

/**
 * 支付区渲染在左栏；右栏仅显示订单摘要。
 */
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
?>
<form
	name="checkout"
	method="post"
	class="checkout woocommerce-checkout ts-wc-checkout ts-stack"
	action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
	enctype="multipart/form-data"
	aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>"
>
	<div class="ts-wc-checkout__layout">
		<?php if ( $checkout->get_checkout_fields() ) : ?>
			<div class="ts-wc-checkout__details">
				<div id="customer_details" class="ts-wc-checkout__customer-fields col2-set">
					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				</div>

				<?php if ( WC()->cart && WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
					<section class="ts-wc-checkout__shipping-methods woocommerce-checkout-shipping-methods">
						<h3 class="ts-wc-checkout__section-title shipping-method-title">
							<?php esc_html_e( 'Shipping', 'woocommerce' ); ?>
							<span
								class="ts-wc-checkout__hint"
								title="<?php echo esc_attr__( 'Shipping costs will be calculated after you enter your address', 'woocommerce' ); ?>"
								aria-label="<?php echo esc_attr__( 'Shipping costs will be calculated after you enter your address', 'woocommerce' ); ?>"
							>?</span>
						</h3>
						<div class="shipping-method-list">
							<?php wc_cart_totals_shipping_html(); ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="woocommerce-checkout-payment-wrap ts-wc-checkout__payment-wrap">
					<h3 class="ts-wc-checkout__section-title"><?php esc_html_e( 'Payment', 'woocommerce' ); ?></h3>
					<?php woocommerce_checkout_payment(); ?>
				</section>
			</div>
		<?php endif; ?>

		<aside class="ts-wc-checkout__summary ts-stack ts-loading-host">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<h3 id="order_review_heading" class="screen-reader-text"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</aside>
	</div>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
