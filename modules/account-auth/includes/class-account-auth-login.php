<?php
declare(strict_types=1);

/**
 * 账户认证页面的 AJAX 登录。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 处理 nopriv AJAX 登录及前台处理器的运行时配置。
 */
class Tailwindscore_Account_Auth_Login {

	public const AJAX_ACTION = 'ts_account_login';

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_login' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle_login' ) );
		add_filter( 'tailwindscore_runtime_config_parts', array( $this, 'filter_runtime_config' ) );
	}

	/**
	 * @return void
	 */
	public function handle_login(): void {
		if ( ! isset( $_POST['woocommerce-login-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error(
				array(
					'message' => __( 'Login failed. Please refresh the page and try again.', 'tailwindscore' ),
				)
			);
		}

		check_ajax_referer( 'woocommerce-login', 'woocommerce-login-nonce' );

		if ( ! did_action( 'woocommerce_init' ) ) {
			WC()->initialize_session();
		}

		if ( is_user_logged_in() ) {
			wp_send_json_success(
				array(
					'redirect' => $this->resolve_redirect( $this->get_redirect_from_request() ),
				)
			);
		}

		$username = isset( $_POST['username'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST['username'] ) ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$remember = isset( $_POST['rememberme'] );

		try {
			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			);

			$validation_error = apply_filters(
				'woocommerce_process_login_errors',
				new WP_Error(),
				$creds['user_login'],
				$creds['user_password']
			);

			if ( $validation_error instanceof WP_Error && $validation_error->get_error_code() ) {
				throw new Exception( wp_strip_all_tags( $validation_error->get_error_message() ) );
			}

			if ( '' === $creds['user_login'] ) {
				throw new Exception(
					wp_strip_all_tags(
						__( 'Username is required.', 'woocommerce' )
					)
				);
			}

			if ( is_multisite() ) {
				$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

				if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
					add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
				}
			}

			$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

			if ( is_wp_error( $user ) ) {
				throw new Exception( wp_strip_all_tags( $user->get_error_message() ) );
			}

			wp_send_json_success(
				array(
					'redirect' => $this->resolve_redirect( $this->get_redirect_from_request(), $user ),
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
	private function get_redirect_from_request(): string {
		if ( ! isset( $_POST['redirect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}

		return esc_url_raw( wp_unslash( (string) $_POST['redirect'] ) );
	}

	/**
	 * @param string        $redirect 请求的跳转 URL。
	 * @param WP_User|null  $user     已登录用户。
	 * @return string
	 */
	private function resolve_redirect( string $redirect, ?WP_User $user = null ): string {
		$redirect = trim( $redirect );

		if ( '' === $redirect ) {
			$redirect = function_exists( 'wc_get_page_permalink' )
				? (string) wc_get_page_permalink( 'myaccount' )
				: home_url( '/' );
		}

		$redirect = remove_query_arg( array( 'wc_error', 'password-reset' ), $redirect );

		if ( $user instanceof WP_User ) {
			$redirect = (string) apply_filters( 'woocommerce_login_redirect', $redirect, $user );
		}

		return wp_validate_redirect( $redirect, home_url( '/' ) );
	}

	/**
	 * @param array<string, mixed> $parts 运行时配置片段。
	 * @return array<string, mixed>
	 */
	public function filter_runtime_config( array $parts ): array {
		if ( ! tailwindscore_account_auth_should_enqueue_ajax_login() ) {
			return $parts;
		}

		$parts['accountAuth'] = array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'ajaxAction'  => self::AJAX_ACTION,
			'redirectUrl' => tailwindscore_account_auth_login_redirect_url(),
			'i18n'        => array(
				'usernameRequired'      => __( 'Please enter your username or email address.', 'woocommerce' ),
				'passwordRequired'      => __( 'Please enter your password.', 'woocommerce' ),
				'loginFailed'           => __( 'Login failed. Please try again.', 'tailwindscore' ),
				'networkError'          => __( 'Network error. Please try again.', 'tailwindscore' ),
				'registrationSuccess'   => __( 'Registration successful. You can now sign in.', 'tailwindscore' ),
				'showPassword'          => __( 'Show password', 'woocommerce' ),
				'hidePassword'          => __( 'Hide password', 'woocommerce' ),
			),
		);

		return $parts;
	}
}
