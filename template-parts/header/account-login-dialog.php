<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$redirect = isset( $args['redirect'] ) && is_string( $args['redirect'] ) && '' !== $args['redirect']
	? $args['redirect']
	: tailwindscore_account_header_login_redirect_url();

$modal_id = isset( $args['modal_id'] ) && is_string( $args['modal_id'] ) && '' !== $args['modal_id']
	? $args['modal_id']
	: 'account-login-modal';

$title_id = $modal_id . '-title';
?>
<div
	id="<?php echo esc_attr( $modal_id ); ?>"
	class="login-modal"
	data-ts-login-modal
	hidden
	aria-hidden="true"
>
	<button
		type="button"
		class="login-modal__backdrop"
		data-ts-login-modal-close
		aria-label="<?php esc_attr_e( 'Close login', 'tailwindscore' ); ?>"
	></button>
	<div
		class="login-modal__dialog stack"
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
	>
		<div class="login-modal__header">
			<h2 id="<?php echo esc_attr( $title_id ); ?>" class="login-modal__title"><?php esc_html_e( 'Login', 'tailwindscore' ); ?></h2>
			<?php
			tailwindscore_render_close_button(
				__( 'Close login', 'tailwindscore' ),
				array(
					'attrs' => array(
						'data-ts-login-modal-close' => '',
					),
				)
			);
			?>
		</div>
		<div class="login-modal__body">
			<?php
			wc_get_template(
				'myaccount/partials/login-form.php',
				array(
					'redirect' => $redirect,
				)
			);
			?>
			<?php if ( function_exists( 'tailwindscore_account_registration_is_available' ) && tailwindscore_account_registration_is_available() ) : ?>
				<p class="login-modal__register">
					<?php
					printf(
						/* translators: %s: register page URL */
						wp_kses_post( __( 'Don&rsquo;t have an account? <a href="%s">Register</a>', 'tailwindscore' ) ),
						esc_url( tailwindscore_account_register_url() )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>
