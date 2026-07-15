<?php
declare(strict_types=1);

/**
 * 我的账户登录表单（仅访客 — 无内联注册）。
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

wc_get_template(
	'myaccount/partials/auth-shell-open.php',
	array(
		'split_hero' => true,
		'subtitle'   => Tailwindscore_Account_Auth_Settings::get_login_welcome_text(),
		'context'    => 'login',
	)
);

wc_get_template( 'myaccount/partials/login-form.php' );

if ( tailwindscore_account_registration_is_available() ) {
	?>
	<p class="ts-account-auth__switch">
		<?php esc_html_e( 'New here?', 'tailwindscore' ); ?>
		<a href="<?php echo esc_url( tailwindscore_account_register_url() ); ?>">
			<?php esc_html_e( 'Create account', 'tailwindscore' ); ?>
		</a>
	</p>
	<?php
}

wc_get_template( 'myaccount/partials/auth-shell-close.php' );

do_action( 'woocommerce_after_customer_login_form' );
