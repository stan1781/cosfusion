<?php
declare(strict_types=1);

/**
 * Standalone registration page handler.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registration shortcode, POST handling, and login-page cross links.
 */
class Tailwindscore_Account_Registration {

	public function __construct() {
		add_shortcode( 'ts_registration_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_loaded', array( $this, 'process_registration' ), 20 );
	}

	/**
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( array $atts = array() ): string {
		if ( is_user_logged_in() ) {
			$account_url = tailwindscore_account_login_url();
			return '<div class="woocommerce-info" role="status">' .
				sprintf(
					/* translators: %s: account URL */
					esc_html__( 'You are already logged in. %s', 'tailwindscore' ),
					'<a href="' . esc_url( $account_url ) . '">' . esc_html__( 'Go to your account', 'tailwindscore' ) . '</a>'
				) .
				'</div>';
		}

		ob_start();
		$this->render_register_surface();
		return (string) ob_get_clean();
	}

	/**
	 * @return void
	 */
	public function render_register_surface(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		wc_get_template(
			'myaccount/partials/auth-shell-open.php',
			array(
				'split_hero' => true,
				'subtitle'   => Tailwindscore_Account_Auth_Settings::get_register_welcome_text(),
				'context'    => 'register',
			)
		);

		wc_get_template( 'myaccount/partials/register-form.php' );

		wc_get_template( 'myaccount/partials/auth-shell-close.php' );
	}

	/**
	 * @return void
	 */
	public function process_registration(): void {
		if ( ! isset( $_POST['register'], $_POST['woocommerce-register-nonce'] ) ) {
			return;
		}

		if ( ! tailwindscore_is_register_page() && ! $this->is_registration_shortcode_request() ) {
			return;
		}

		wc_clear_notices();

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['woocommerce-register-nonce'] ) ), 'woocommerce-register' ) ) {
			wc_add_notice( __( 'Registration failed. Please try again.', 'tailwindscore' ), 'error' );
			return;
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( (string) $_POST['username'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

		$result = self::create_customer( $username, $email, $password );

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			return;
		}

		wp_safe_redirect( Tailwindscore_Account_Auth_Register::registration_success_redirect_url() );
		exit;
	}

	/**
	 * @param string $username Username.
	 * @param string $email    Email address.
	 * @param string $password Password.
	 * @return int|WP_Error Customer ID on success.
	 */
	public static function create_customer( string $username, string $email, string $password ): int|WP_Error {
		$username = trim( $username );

		if ( '' === $username ) {
			return new WP_Error( 'registration-error', __( 'Please enter a username.', 'woocommerce' ) );
		}

		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'registration-error', __( 'Please enter a valid email address.', 'woocommerce' ) );
		}

		if ( '' === $password ) {
			return new WP_Error( 'registration-error', __( 'Please enter a password.', 'woocommerce' ) );
		}

		$validation_error = apply_filters(
			'woocommerce_registration_errors',
			new WP_Error(),
			$username,
			$email
		);

		if ( $validation_error instanceof WP_Error && $validation_error->get_error_code() ) {
			return $validation_error;
		}

		$customer_id = wc_create_new_customer( $email, $username, $password );

		if ( is_wp_error( $customer_id ) ) {
			return $customer_id;
		}

		return (int) $customer_id;
	}

	/**
	 * @return bool
	 */
	private function is_registration_shortcode_request(): bool {
		global $post;

		return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ts_registration_form' );
	}
}
