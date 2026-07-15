<?php
declare(strict_types=1);

/**
 * 忘记密码表单。
 *
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

wc_get_template(
    'myaccount/partials/auth-shell-open.php',
    array(
        'split_hero' => false,
        'subtitle'   => __( 'Reset your password', 'tailwindscore' ),
        'context'    => 'recovery',
    )
);

do_action( 'woocommerce_before_lost_password_form' );
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password ts-account-auth__form">

	<p class="ts-account-auth__hint"><?php echo esc_html( apply_filters( 'woocommerce_lost_password_message', __( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'woocommerce' ) ) ); ?></p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="ts-account-auth__label" for="user_login">
			<?php esc_html_e( 'Username or email', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input class="woocommerce-Input woocommerce-Input--text input-text ts-input" type="text" name="user_login" id="user_login" autocomplete="username" required aria-required="true">
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_lostpassword_form' ); ?>

	<p class="woocommerce-form-row form-row form-row-wide">
		<input type="hidden" name="wc_reset_password" value="true">
		<button type="submit" class="woocommerce-Button button ts-button w-full<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" value="<?php esc_attr_e( 'Reset password', 'woocommerce' ); ?>" data-ts-button-loading>
			<?php esc_html_e( 'Reset password', 'woocommerce' ); ?>
		</button>
	</p>

	<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

</form>

<p class="ts-account-auth__switch">
	<a href="<?php echo esc_url( tailwindscore_account_login_url() ); ?>">
		<?php esc_html_e( 'Back to log in', 'tailwindscore' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_after_lost_password_form' );

wc_get_template( 'myaccount/partials/auth-shell-close.php' );
