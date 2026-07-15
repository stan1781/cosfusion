<?php
declare(strict_types=1);

/**
 * 账户认证模块 — theme_mod 设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 账户认证页面的 Kirki theme_mod 访问器。
 */
class Tailwindscore_Account_Auth_Settings {

	/**
	 * @return string
	 */
	public static function get_login_welcome_text(): string {
		$default = sprintf(
			/* translators: %s: site name */
			__( 'Welcome to %s', 'tailwindscore' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$text = (string) tailwindscore_mod( 'account_auth_login_welcome', $default );

		return str_replace( '%site_name%', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $text );
	}

	/**
	 * @return string
	 */
	public static function get_register_welcome_text(): string {
		$default = __( 'Create your account', 'tailwindscore' );
		return (string) tailwindscore_mod( 'account_auth_register_welcome', $default );
	}

	/**
	 * @return string Hero 图片 URL，无则为空。
	 */
	public static function get_hero_image_url(): string {
		$url = (string) tailwindscore_mod( 'account_auth_hero_image', '' );
		$url = trim( $url );

		if ( '' !== $url ) {
			return $url;
		}

		$path = get_template_directory() . '/assets/img/account-auth-hero.png';
		if ( is_readable( $path ) ) {
			return get_template_directory_uri() . '/assets/img/account-auth-hero.png';
		}

		return '';
	}

	/**
	 * @return string
	 */
	public static function get_hero_heading(): string {
		return (string) tailwindscore_mod(
			'account_auth_hero_heading',
			__( 'Shop with confidence', 'tailwindscore' )
		);
	}

	/**
	 * @return string
	 */
	public static function get_hero_text(): string {
		return (string) tailwindscore_mod(
			'account_auth_hero_text',
			__( 'Sign in to track orders, save details, and checkout faster.', 'tailwindscore' )
		);
	}

	/**
	 * @return string 注册页 hero 标题。
	 */
	public static function get_register_hero_heading(): string {
		$value = (string) tailwindscore_mod( 'account_auth_register_hero_heading', '' );
		return '' !== trim( $value ) ? $value : self::get_hero_heading();
	}

	/**
	 * @return string 注册页 hero 文案。
	 */
	public static function get_register_hero_text(): string {
		$value = (string) tailwindscore_mod( 'account_auth_register_hero_text', '' );
		return '' !== trim( $value ) ? $value : self::get_hero_text();
	}

	/**
	 * 自定义 hero 背景色或空（回退 SCSS token）。
	 *
	 * @return string
	 */
	public static function get_hero_bg_color(): string {
		$color = (string) tailwindscore_mod( 'account_auth_hero_bg', '' );
		$color = sanitize_hex_color( $color );

		return is_string( $color ) ? $color : '';
	}

	/**
	 * @return bool
	 */
	public static function show_remember_me(): bool {
		return self::to_bool( tailwindscore_mod( 'account_auth_show_remember_me', true ), true );
	}

	/**
	 * @return bool
	 */
	public static function show_login_google(): bool {
		return self::to_bool( tailwindscore_mod( 'account_auth_google_enabled', false ), false );
	}

	/**
	 * @return bool
	 */
	public static function show_register_google(): bool {
		return self::to_bool( tailwindscore_mod( 'account_auth_register_show_google', false ), false );
	}

	/**
	 * 独立 /register/ 页面为主题注册路径（非 My Account 内联）。
	 *
	 * @return bool
	 */
	public static function is_registration_available(): bool {
		$page = get_page_by_path( 'register' );

		return $page instanceof WP_Post && 'publish' === $page->post_status;
	}

	/**
	 * @return string
	 */
	public static function register_page_url(): string {
		$page = get_page_by_path( 'register' );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return (string) get_permalink( $page );
		}

		return home_url( '/register/' );
	}

	/**
	 * @return string
	 */
	public static function login_page_url(): string {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			return (string) wc_get_page_permalink( 'myaccount' );
		}

		return wp_login_url( home_url( '/' ) );
	}

	/**
	 * @param mixed $value   原始 theme_mod。
	 * @param bool  $default 为空时的默认值。
	 * @return bool
	 */
	private static function to_bool( $value, bool $default ): bool {
		if ( null === $value || '' === $value ) {
			return $default;
		}

		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
