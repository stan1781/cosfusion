<?php
declare(strict_types=1);

/**
 * Account auth context helpers.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return bool
 */
function tailwindscore_is_guest_account_page(): bool {
	return class_exists( 'WooCommerce' )
		&& function_exists( 'is_account_page' )
		&& is_account_page()
		&& ! is_user_logged_in();
}

/**
 * @return bool
 */
function tailwindscore_is_register_page(): bool {
	return is_page( 'register' );
}

/**
 * Auth surfaces use the standalone document layout (no site header/footer).
 *
 * @return bool
 */
function tailwindscore_is_account_auth_surface(): bool {
	if ( tailwindscore_is_register_page() ) {
		return true;
	}

	return tailwindscore_is_guest_account_page();
}

/**
 * Whether the AJAX login script config should load.
 *
 * @return bool
 */
function tailwindscore_account_auth_should_enqueue_ajax_login(): bool {
	if ( ! class_exists( 'WooCommerce' ) || is_user_logged_in() ) {
		return false;
	}

	if ( tailwindscore_is_account_auth_surface() ) {
		return true;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	return function_exists( 'tailwindscore_should_render_header_account_ui' )
		&& tailwindscore_should_render_header_account_ui();
}

/**
 * Whether the AJAX register script config should load.
 *
 * @return bool
 */
function tailwindscore_account_auth_should_enqueue_ajax_register(): bool {
	return class_exists( 'WooCommerce' ) && ! is_user_logged_in() && tailwindscore_is_register_page();
}

/**
 * Default redirect after AJAX login.
 *
 * @return string
 */
function tailwindscore_account_auth_login_redirect_url(): string {
	if ( function_exists( 'is_checkout' ) && is_checkout() && function_exists( 'wc_get_checkout_url' ) ) {
		return (string) wc_get_checkout_url();
	}

	if ( function_exists( 'tailwindscore_account_header_login_redirect_url' ) ) {
		return tailwindscore_account_header_login_redirect_url();
	}

	return tailwindscore_account_login_url();
}

/**
 * @return bool
 */
function tailwindscore_account_auth_show_google(): bool {
	return Tailwindscore_Account_Auth_Settings::show_login_google();
}

/**
 * @return bool
 */
function tailwindscore_account_auth_show_register_google(): bool {
	return Tailwindscore_Account_Auth_Settings::show_register_google();
}

/**
 * @return bool
 */
function tailwindscore_account_registration_is_available(): bool {
	return Tailwindscore_Account_Auth_Settings::is_registration_available();
}

/**
 * @return string
 */
function tailwindscore_account_register_url(): string {
	return Tailwindscore_Account_Auth_Settings::register_page_url();
}

/**
 * @return string
 */
function tailwindscore_account_login_url(): string {
	return Tailwindscore_Account_Auth_Settings::login_page_url();
}
