<?php
declare(strict_types=1);

/**
 * 账户认证 Hook — 模板、Site Kit、注册页引导。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 注册 account-auth 的 WordPress / WooCommerce Hook。
 */
class Tailwindscore_Account_Auth_Hooks {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'filter_template_include' ), 99 );
		add_action( 'after_switch_theme', array( $this, 'ensure_register_page' ) );
		add_action( 'init', array( $this, 'ensure_register_page_once' ), 20 );
		add_action( 'woocommerce_init', array( $this, 'configure_auth_notices' ), 1000 );
		add_action( 'woocommerce_init', array( $this, 'detach_sitekit_default_login_button' ), 999 );
		add_action( 'woocommerce_init', array( $this, 'guard_wc_enhanced_user_register_link' ), 999 );
	}

	/**
	 * 仅在认证壳 wrapper 内渲染通知（避免重复 wrapper / 布局偏移）。
	 *
	 * @return void
	 */
	public function configure_auth_notices(): void {
		if ( ! tailwindscore_is_account_auth_surface() ) {
			return;
		}

		$hooks = array(
			array( 'woocommerce_account_content', 5 ),
			array( 'woocommerce_before_customer_login_form', 10 ),
			array( 'woocommerce_before_lost_password_form', 10 ),
			array( 'woocommerce_before_reset_password_form', 10 ),
		);

		foreach ( $hooks as $hook ) {
			remove_action( $hook[0], 'woocommerce_output_all_notices', $hook[1] );
		}
	}

	/**
	 * @param string $template 当前模板路径。
	 * @return string
	 */
	public function filter_template_include( string $template ): string {
		if ( tailwindscore_is_register_page() ) {
			$custom = get_template_directory() . '/page-register.php';
			return is_readable( $custom ) ? $custom : $template;
		}

		if ( tailwindscore_is_guest_account_page() ) {
			$custom = get_template_directory() . '/woocommerce/page-account-guest.php';
			return is_readable( $custom ) ? $custom : $template;
		}

		return $template;
	}

	/**
	 * @return void
	 */
	public function ensure_register_page_once(): void {
		if ( get_option( 'tailwindscore_account_auth_register_page_ready' ) ) {
			return;
		}

		$this->ensure_register_page();
		update_option( 'tailwindscore_account_auth_register_page_ready', 1, false );
	}

	/**
	 * @return void
	 */
	public function ensure_register_page(): void {
		$existing = get_page_by_path( 'register' );
		if ( $existing instanceof WP_Post ) {
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Register', 'tailwindscore' ),
				'post_name'    => 'register',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return;
		}

		update_post_meta( (int) $page_id, '_ts_account_register_page', '1' );
	}

	/**
	 * 移除 woocommerce_login_form_start 上的 Site Kit 默认按钮（策略 B 使用 auth-google-slot）。
	 *
	 * @return void
	 */
	public function detach_sitekit_default_login_button(): void {
		global $wp_filter;

		if ( empty( $wp_filter['woocommerce_login_form_start'] ) ) {
			return;
		}

		$hook = $wp_filter['woocommerce_login_form_start'];

		if ( ! $hook instanceof WP_Hook ) {
			return;
		}

		foreach ( $hook->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'] ?? null;

				if ( $this->is_sitekit_sign_in_callback( $function ) ) {
					remove_action( 'woocommerce_login_form_start', $function, (int) $priority );
				}
			}
		}
	}

	/**
	 * @param mixed $function Hook 回调。
	 * @return bool
	 */
	private function is_sitekit_sign_in_callback( $function ): bool {
		if ( is_string( $function ) && str_contains( $function, 'googlesitekit' ) ) {
			return true;
		}

		if ( ! is_array( $function ) || ! isset( $function[0] ) ) {
			return false;
		}

		$target = $function[0];
		$class  = is_object( $target ) ? get_class( $target ) : (string) $target;

		return str_contains( $class, 'Sign_In_With_Google' )
			|| str_contains( $class, 'Google\\Site_Kit' );
	}

	/**
	 * wc-enhanced-user 启用时避免重复注册链接。
	 *
	 * @return void
	 */
	public function guard_wc_enhanced_user_register_link(): void {
		global $wp_filter;

		if ( empty( $wp_filter['woocommerce_login_form_end'] ) || ! $wp_filter['woocommerce_login_form_end'] instanceof WP_Hook ) {
			return;
		}

		foreach ( $wp_filter['woocommerce_login_form_end']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'] ?? null;

				if ( is_array( $function )
					&& isset( $function[1] )
					&& 'add_registration_link' === $function[1]
					&& is_object( $function[0] )
					&& is_a( $function[0], 'WC_Registration_Page' ) ) {
					remove_action( 'woocommerce_login_form_end', $function, (int) $priority );
				}
			}
		}
	}
}
