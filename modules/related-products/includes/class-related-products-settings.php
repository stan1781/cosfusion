<?php
declare(strict_types=1);

/**
 * Related products module — theme_mod settings.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kirki theme_mod accessors for related products.
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
	 * Cache fingerprint for transients.
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
	 * @param mixed $value   Raw theme_mod.
	 * @param bool  $default Default when empty.
	 * @return bool
	 */
	private static function to_bool( $value, bool $default ): bool {
		if ( null === $value || '' === $value ) {
			return $default;
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
