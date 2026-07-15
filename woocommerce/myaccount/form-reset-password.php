<?php
declare(strict_types=1);

/**
 * 重置密码表单。
 *
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

wc_get_template(
	'myaccount/partials/auth-shell-open.php',
	array(
		'split_hero' => false,
		'subtitle'   => __( 'Set a new password', 'tailwindscore' ),
		'context'    => 'recovery',
	)
);

do_action( 'woocommerce_before_reset_password_form' );
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password account-auth__form">

	<p class="account-auth__hint"><?php echo esc_html( apply_filters( 'woocommerce_reset_password_message', __( 'Enter a new password below.', 'woocommerce' ) ) ); ?></p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="account-auth__label" for="password_1">
			<?php esc_html_e( 'New password', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text input" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true">
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label class="account-auth__label" for="password_2">
			<?php esc_html_e( 'Re-enter new password', 'woocommerce' ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text input" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true">
	</p>

	<input type="hidden" name="reset_key" value="<?php echo esc_attr( isset( $args['key'] ) ? (string) $args['key'] : '' ); ?>">
	<input type="hidden" name="reset_login" value="<?php echo esc_attr( isset( $args['login'] ) ? (string) $args['login'] : '' ); ?>">

	<div class="clear"></div>

	<?php do_action( 'woocommerce_resetpassword_form' ); ?>

	<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>

	<p class="woocommerce-form-row form-row form-row-wide">
		<input type="hidden" name="wc_reset_password" value="true">
		<button type="submit" class="woocommerce-Button button btn w-full<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" value="<?php esc_attr_e( 'Save', 'woocommerce' ); ?>" data-ts-button-loading>
			<?php esc_html_e( 'Save', 'woocommerce' ); ?>
		</button>
	</p>

</form>

<p class="account-auth__switch">
	<a href="<?php echo esc_url( tailwindscore_account_login_url() ); ?>">
		<?php esc_html_e( 'Back to log in', 'tailwindscore' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_after_reset_password_form' );

wc_get_template( 'myaccount/partials/auth-shell-close.php' );
