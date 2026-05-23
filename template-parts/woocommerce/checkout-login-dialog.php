<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div
	id="ts-checkout-login-modal"
	class="ts-checkout-login-modal"
	data-ts-checkout-login-modal
	hidden
	aria-hidden="true"
>
	<button
		type="button"
		class="ts-checkout-login-modal__backdrop"
		data-ts-checkout-login-close
		aria-label="<?php esc_attr_e( 'Close login', 'tailwindscore' ); ?>"
	></button>
	<div
		id="ts-checkout-login-panel"
		class="ts-checkout-login-modal__dialog ts-stack"
		role="dialog"
		aria-modal="true"
		aria-labelledby="ts-checkout-login-title"
	>
		<div class="ts-checkout-login-modal__header">
			<h2 id="ts-checkout-login-title" class="ts-checkout-login-modal__title"><?php esc_html_e( 'Login', 'tailwindscore' ); ?></h2>
			<?php
			tailwindscore_render_close_button(
				__( 'Close login', 'tailwindscore' ),
				array(
					'attrs' => array(
						'data-ts-checkout-login-close' => '',
					),
				)
			);
			?>
		</div>
		<div class="ts-checkout-login-modal__body">
			<?php
			woocommerce_login_form(
				array(
					'redirect' => wc_get_checkout_url(),
					'hidden'   => false,
				)
			);
			?>
		</div>
	</div>
</div>
