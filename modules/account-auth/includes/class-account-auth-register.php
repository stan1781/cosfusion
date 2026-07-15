<?php
declare(strict_types=1);

/**
 * 账户认证页面的 AJAX 注册。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 处理 nopriv AJAX 注册及前台处理器的运行时配置。
 */
class Tailwindscore_Account_Auth_Register {

	public const AJAX_ACTION = 'ts_account_register';

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_register' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle_register' ) );
		add_filter( 'tailwindscore_runtime_config_parts', array( $this, 'filter_runtime_config' ) );
	}

	/**
	 * @return void
	 */
	public function handle_register(): void {
		if ( ! isset( $_POST['woocommerce-register-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error(
				array(
					'message' => __( 'Registration failed. Please refresh the page and try again.', 'tailwindscore' ),
				)
			);
		}

		check_ajax_referer( 'woocommerce-register', 'woocommerce-register-nonce' );

		if ( ! did_action( 'woocommerce_init' ) ) {
			WC()->initialize_session();
		}

		if ( is_user_logged_in() ) {
			wp_send_json_success(
				array(
					'redirect' => tailwindscore_account_login_url(),
				)
			);
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( (string) $_POST['username'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

		try {
			$result = Tailwindscore_Account_Registration::create_customer( $username, $email, $password );

			if ( is_wp_error( $result ) ) {
				throw new Exception( wp_strip_all_tags( $result->get_error_message() ) );
			}

			wp_send_json_success(
				array(
					'redirect' => self::registration_success_redirect_url(),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * @return string
	 */
	public static function registration_success_redirect_url(): string {
		return add_query_arg( 'registration', 'success', tailwindscore_account_login_url() );
	}

	/**
	 * @param array<string, mixed> $parts 运行时配置片段。
	 * @return array<string, mixed>
	 */
	public function filter_runtime_config( array $parts ): array {
		if ( ! tailwindscore_account_auth_should_enqueue_ajax_register() ) {
			return $parts;
		}

		if ( ! isset( $parts['accountAuth'] ) || ! is_array( $parts['accountAuth'] ) ) {
			$parts['accountAuth'] = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			);
		}

		$parts['accountAuth']['registerAjaxAction']  = self::AJAX_ACTION;
		$parts['accountAuth']['registerRedirectUrl'] = self::registration_success_redirect_url();
		$parts['accountAuth']['registerI18n']        = array(
			'usernameRequired'   => __( 'Please enter a username.', 'woocommerce' ),
			'emailRequired'      => __( 'Please enter a valid email address.', 'woocommerce' ),
			'passwordRequired'   => __( 'Please enter a password.', 'woocommerce' ),
			'passwordMismatch'   => __( 'Passwords do not match.', 'tailwindscore' ),
			'registerFailed'     => __( 'Registration failed. Please try again.', 'tailwindscore' ),
			'networkError'       => __( 'Network error. Please try again.', 'tailwindscore' ),
		);

		return $parts;
	}
}
