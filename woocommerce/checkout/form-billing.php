<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-billing-fields">
	<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>
		<h3 class="ts-wc-checkout__section-title"><?php esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>
	<?php else : ?>
		<h3 class="ts-wc-checkout__section-title"><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h3>
	<?php endif; ?>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		foreach ( $fields as $key => $field ) {
			if ( 'billing_email' === $key && ! is_user_logged_in() ) {
				$email_label_text = isset( $field['label'] ) ? (string) $field['label'] : '';
				$field['label']   = sprintf(
					'<span class="checkout-email-label-text">%1$s</span><a class="checkout-email-signin-link" href="#" data-ts-checkout-login-open>%2$s</a>',
					esc_html( $email_label_text ),
					esc_html__( 'Sign in', 'tailwindscore' )
				);

				if ( empty( $field['label_class'] ) ) {
					$field['label_class'] = array();
				}
				if ( is_string( $field['label_class'] ) ) {
					$field['label_class'] = array( $field['label_class'] );
				}
				$field['label_class'][] = 'checkout-email-label';
			}

			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );

			if ( 'billing_email' === $key && ! is_user_logged_in() && $checkout->is_registration_enabled() ) :
				?>
				<div class="woocommerce-account-fields">
					<?php if ( ! $checkout->is_registration_required() ) : ?>
						<p class="form-row form-row-wide create-account checkout-grid-full screen-reader-text">
							<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
								<input
									class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
									id="createaccount"
									<?php
									checked(
										(
											true === $checkout->get_value( 'createaccount' ) ||
											( true === apply_filters( 'woocommerce_create_account_default_checked', false ) )
										),
										true
									);
									?>
									type="checkbox"
									name="createaccount"
									value="1"
								/>
								<span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
							</label>
						</p>
					<?php endif; ?>

					<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

					<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
						<div class="create-account">
							<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $account_key => $account_field ) : ?>
								<?php woocommerce_form_field( $account_key, $account_field, $checkout->get_value( $account_key ) ); ?>
							<?php endforeach; ?>
							<div class="clear"></div>
						</div>
					<?php endif; ?>

					<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
				</div>
				<?php
			endif;
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
