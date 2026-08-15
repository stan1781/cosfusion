<?php
declare(strict_types=1);

/**
 * 相关产品模块 — theme_mod 设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 相关产品的 Kirki theme_mod 访问器。
 */
class Tailwindscore_Related_Products_Settings {

	public const ENGINE_VERSION = '1.0.0';

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$value = tailwindscore_mod( 'product_related_enabled', true );
		return self::to_bool( $value, true );
	}

	/**
	 * 单品页相关产品是否使用 Swiper 轮播（否则 Woo 默认网格）。
	 *
	 * @return bool
	 */
	public static function is_carousel_enabled(): bool {
		$value = tailwindscore_mod( 'product_related_carousel', true );
		return self::to_bool( $value, true );
	}

	/**
	 * @return string simple|tag_based|category_based|hybrid
	 */
	public static function get_algorithm(): string {
		$algo = (string) tailwindscore_mod( 'product_related_algorithm', 'hybrid' );
		$allowed = array( 'simple', 'tag_based', 'category_based', 'hybrid' );
		return in_array( $algo, $allowed, true ) ? $algo : 'hybrid';
	}

	/**
	 * @return int
	 */
	public static function get_max_products(): int {
		$max = (int) tailwindscore_mod( 'product_related_max_products', 12 );
		return max( 1, min( 50, $max ) );
	}

	/**
	 * @return bool
	 */
	public static function exclude_out_of_stock(): bool {
		$value = tailwindscore_mod( 'product_related_exclude_out_of_stock', true );
		return self::to_bool( $value, true );
	}

	/**
	 * @return bool
	 */
	public static function randomize_results(): bool {
		$value = tailwindscore_mod( 'product_related_randomize_results', true );
		return self::to_bool( $value, true );
	}

	/**
	 * @return bool
	 */
	public static function title_match_enabled(): bool {
		$value = tailwindscore_mod( 'product_related_title_match_enabled', true );
		return self::to_bool( $value, true );
	}

	/**
	 * @return int
	 */
	public static function get_title_min_token_length(): int {
		$len = (int) tailwindscore_mod( 'product_related_title_min_token_length', 3 );
		return max( 2, min( 10, $len ) );
	}

	/**
	 * transient 的缓存指纹。
	 *
	 * @return string
	 */
	public static function get_cache_fingerprint(): string {
		return md5(
			wp_json_encode(
				array(
					'v'        => self::ENGINE_VERSION,
					'enabled'  => self::is_enabled(),
					'algo'     => self::get_algorithm(),
					'max'      => self::get_max_products(),
					'oos'      => self::exclude_out_of_stock(),
					'rand'     => self::randomize_results(),
					'title'    => self::title_match_enabled(),
					'titleLen' => self::get_title_min_token_length(),
				)
			) ?: ''
		);
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
