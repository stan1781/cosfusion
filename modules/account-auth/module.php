<?php
declare(strict_types=1);

/**
 * 账户认证模块引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

	define( 'TAILWINDSCORE_MODULE_ACCOUNT_AUTH_VERSION', '0.1.1' );

$tailwindscore_account_auth_dir = __DIR__;

require_once $tailwindscore_account_auth_dir . '/includes/class-account-auth-settings.php';
require_once $tailwindscore_account_auth_dir . '/includes/class-account-auth-context.php';
require_once $tailwindscore_account_auth_dir . '/includes/class-account-auth-login.php';
require_once $tailwindscore_account_auth_dir . '/includes/class-account-auth-register.php';
require_once $tailwindscore_account_auth_dir . '/includes/class-account-registration.php';
require_once $tailwindscore_account_auth_dir . '/includes/class-account-auth-hooks.php';
require_once $tailwindscore_account_auth_dir . '/kirki.php';

/**
 * @return void
 */
function tailwindscore_account_auth_module_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	new Tailwindscore_Account_Auth_Hooks();
	new Tailwindscore_Account_Registration();
	new Tailwindscore_Account_Auth_Login();
	new Tailwindscore_Account_Auth_Register();
}
add_action( 'after_setup_theme', 'tailwindscore_account_auth_module_init', 25 );
