<?php
declare(strict_types=1);

/**
 * Registration form fields.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

wc_get_template(
	'myaccount/partials/auth-google-slot.php',
	array(
		'placement' => 'before_form',
	)
);
?>
<form method="post" class="woocommerce-form woocommerce-form-register register ts-account-auth__form" novalidate data-ts-auth-register-ajax <?php do_action( 'woocommerce_register_form_tag' ); ?>>
	<?php do_action( 'woocommerce_register_form_start' ); ?>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="ts-account-auth__label" for="reg_username">
			<?php esc_html_e( 'Username', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="text"
			class="woocommerce-Input woocommerce-Input--text input-text ts-input"
			name="username"
			id="reg_username"
			autocomplete="username"
			value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( (string) $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>"
			required
			aria-required="true"
		>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="ts-account-auth__label" for="reg_email">
			<?php esc_html_e( 'Email address', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="email"
			class="woocommerce-Input woocommerce-Input--text input-text ts-input"
			name="email"
			id="reg_email"
			autocomplete="email"
			value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( (string) $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>"
			required
			aria-required="true"
		>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="ts-account-auth__label" for="reg_password">
			<?php esc_html_e( 'Password', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="password"
			class="woocommerce-Input woocommerce-Input--text input-text ts-input"
			name="password"
			id="reg_password"
			autocomplete="new-password"
			required
			aria-required="true"
		>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="ts-account-auth__label" for="reg_password_confirm">
			<?php esc_html_e( 'Confirm password', 'tailwindscore' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="password"
			class="woocommerce-Input woocommerce-Input--text input-text ts-input"
			name="password_confirm"
			id="reg_password_confirm"
			autocomplete="new-password"
			required
			aria-required="true"
		>
	</p>

	<?php do_action( 'woocommerce_register_form' ); ?>

	<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

	<button
		type="submit"
		class="woocommerce-Button woocommerce-button button ts-button w-full<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit"
		name="register"
		value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"
		data-ts-button-loading
	>
		<?php esc_html_e( 'Register', 'woocommerce' ); ?>
	</button>

	<?php do_action( 'woocommerce_register_form_end' ); ?>
</form>

<p class="ts-account-auth__switch">
	<?php esc_html_e( 'Already have an account?', 'tailwindscore' ); ?>
	<a href="<?php echo esc_url( tailwindscore_account_login_url() ); ?>">
		<?php esc_html_e( 'Log in', 'tailwindscore' ); ?>
	</a>
</p>
