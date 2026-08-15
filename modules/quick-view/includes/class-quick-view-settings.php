<?php
declare(strict_types=1);

/**
 * Quick View 模块 — theme_mod 设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quick View 的 Kirki theme_mod 访问器。
 */
class Tailwindscore_Quick_View_Settings {

	public const ALLOWED_TARGET_PAGES = array( 'shop_archive', 'single_product', 'homepage', 'cart', 'not_found' );

	/**
	 * @return string[]
	 */
	public static function default_target_pages(): array {
		return array( 'shop_archive', 'homepage', 'single_product', 'cart', 'not_found' );
	}

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$value = tailwindscore_mod( 'product_quick_view_enabled', true );
		return self::to_bool( $value, true );
	}

	/**
	 * @return string[]
	 */
	public static function get_target_pages(): array {
		$value = tailwindscore_mod( 'product_quick_view_target_pages', self::default_target_pages() );
		$values = self::sanitize_target_pages( $value );

		// 未改过「生效页面」的旧站：原默认三项时并入购物车 / 404 轮播页。
		$legacy_default = array( 'shop_archive', 'homepage', 'single_product' );
		if ( $values === $legacy_default ) {
			return self::default_target_pages();
		}

		return $values;
	}

	/**
	 * @param mixed $value 原始 theme_mod。
	 * @return string[]
	 */
	public static function sanitize_target_pages( $value ): array {
		$values = array();

		if ( is_array( $value ) ) {
			$values = $value;
		} elseif ( is_string( $value ) && '' !== $value ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				$values = $decoded;
			} else {
				$values = array_map( 'trim', explode( ',', $value ) );
			}
		}

		$values = array_values( array_intersect( $values, self::ALLOWED_TARGET_PAGES ) );

		if ( empty( $values ) ) {
			$values = array( 'shop_archive' );
		}

		return $values;
	}

	/**
	 * @param mixed $value   设置值。
	 * @param bool  $default 未设置时的默认值。
	 * @return bool
	 */
	private static function to_bool( $value, bool $default ): bool {
		if ( null === $value || '' === $value ) {
			return $default;
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_numeric( $value ) ) {
			return (int) $value !== 0;
		}
		$str = strtolower( (string) $value );
		if ( in_array( $str, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}
		if ( in_array( $str, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}
		return $default;
	}

		/**
		 * 当前请求是否应加载 Quick View 资源与弹层。
		 *
		 * @return bool
		 */
		public static function should_load_frontend(): bool {
			if ( ! self::is_enabled() || ! class_exists( 'WooCommerce' ) ) {
				return false;
			}

			return self::is_target_page();
		}

		/**
		 * 循环卡片是否输出 QV 触发器。
		 *
		 * 与 should_load_frontend() 分离：Recently viewed 等经 REST 水合时无 is_shop / is_product
		 * 上下文，但仍须输出 data-ts-quick-view；弹层与 JS 仍只在目标页加载。
		 *
		 * @return bool
		 */
		public static function should_emit_loop_trigger(): bool {
			if ( ! self::is_enabled() || ! class_exists( 'WooCommerce' ) ) {
				return false;
			}

			if ( self::is_target_page() ) {
				return true;
			}

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return true;
			}

			return (bool) apply_filters( 'tailwindscore_quick_view_emit_loop_trigger', false );
		}

	/**
	 * @return bool
	 */
	public static function is_target_page(): bool {
		$target_pages = self::get_target_pages();
		$matched      = false;

		if ( in_array( 'shop_archive', $target_pages, true ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
			$matched = true;
		}

		if ( ! $matched && in_array( 'single_product', $target_pages, true ) && function_exists( 'is_product' ) && is_product() ) {
			$matched = true;
		}

		if ( ! $matched && in_array( 'homepage', $target_pages, true ) && self::is_homepage_context() ) {
			$matched = true;
		}

		if ( ! $matched && in_array( 'cart', $target_pages, true ) && function_exists( 'is_cart' ) && is_cart() ) {
			$matched = true;
		}

		if ( ! $matched && in_array( 'not_found', $target_pages, true ) && is_404() ) {
			$matched = true;
		}

		return (bool) apply_filters( 'tailwindscore_quick_view_is_target_page', $matched );
	}

	/**
	 * @return bool
	 */
	private static function is_homepage_context(): bool {
		if ( is_front_page() || is_home() ) {
			return true;
		}

		$queried_id = get_queried_object_id();
		if ( ! $queried_id ) {
			return false;
		}

		$page_on_front  = (int) get_option( 'page_on_front' );
		$page_for_posts = (int) get_option( 'page_for_posts' );

		return $queried_id === $page_on_front || $queried_id === $page_for_posts;
	}
}
