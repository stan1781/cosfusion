<?php
declare(strict_types=1);

/**
 * 登录表单字段（共享标记）。
 *
 * @package TailwindScore
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

/** @var string $redirect 来自 wc_get_template() 的可选重定向 URL。 */
$redirect = isset( $redirect ) && is_string( $redirect ) && '' !== $redirect
	? $redirect
	: tailwindscore_account_auth_login_redirect_url();

?>
<form class="woocommerce-form woocommerce-form-login login account-auth__form" method="post" novalidate data-ts-auth-login-ajax>
	<?php do_action( 'woocommerce_login_form_start' ); ?>

	<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>">

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="account-auth__label" for="username">
			<?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
			<span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
		</label>
		<input
			type="text"
			class="woocommerce-Input woocommerce-Input--text input-text input"
			name="username"
			id="username"
			autocomplete="username"
			value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>"
			required
			aria-required="true"
		>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="account-auth__label" for="password">
			<?php esc_html_e( 'Password', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
			<span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
		</label>
		<input
			class="woocommerce-Input woocommerce-Input--text input-text input"
			type="password"
			name="password"
			id="password"
			autocomplete="current-password"
			required
			aria-required="true"
		>
	</p>

	<p class="account-auth__lost-password">
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
			<?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?>
		</a>
	</p>

	<?php do_action( 'woocommerce_login_form' ); ?>

	<?php if ( Tailwindscore_Account_Auth_Settings::show_remember_me() ) : ?>
		<p class="form-row account-auth__remember">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever">
				<span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
			</label>
		</p>
	<?php endif; ?>

	<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

	<button
		type="submit"
		class="woocommerce-button button woocommerce-form-login__submit btn w-full<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
		name="login"
		value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"
		data-ts-button-loading
	>
		<?php esc_html_e( 'Log in', 'woocommerce' ); ?>
	</button>

	<?php
	wc_get_template(
		'myaccount/partials/auth-google-slot.php',
		array(
			'placement' => 'after_login',
		)
	);
	?>

	<?php do_action( 'woocommerce_login_form_end' ); ?>
</form>
