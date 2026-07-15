<?php
declare(strict_types=1);

/**
 * 忘记密码确认页。
 *
 * @package WooCommerce\Templates
 * @version 3.9.0
 */

defined( 'ABSPATH' ) || exit;

wc_get_template(
	'myaccount/partials/auth-shell-open.php',
	array(
		'split_hero' => false,
		'subtitle'   => __( 'Check your email', 'tailwindscore' ),
		'context'    => 'recovery',
	)
);

do_action( 'woocommerce_before_lost_password_confirmation_message' );

wc_print_notice( esc_html__( 'Password reset email has been sent.', 'woocommerce' ), 'success' );
?>

<p class="account-auth__hint"><?php echo esc_html( apply_filters( 'woocommerce_lost_password_confirmation_message', esc_html__( 'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.', 'woocommerce' ) ) ); ?></p>

<p class="account-auth__switch">
	<a href="<?php echo esc_url( tailwindscore_account_login_url() ); ?>">
		<?php esc_html_e( 'Back to log in', 'tailwindscore' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_after_lost_password_confirmation_message' );

wc_get_template( 'myaccount/partials/auth-shell-close.php' );
