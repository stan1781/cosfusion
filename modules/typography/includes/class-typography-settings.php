<?php
declare(strict_types=1);

/**
 * Typography theme_mod accessors.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Heading and body font selections.
 */
class Tailwindscore_Typography_Settings {

	/**
	 * @return string
	 */
	public static function get_heading_font_slug(): string {
		return self::sanitize_slug(
			tailwindscore_mod( 'typography_heading_font', 'system' )
		);
	}

	/**
	 * @return string
	 */
	public static function get_body_font_slug(): string {
		return self::sanitize_slug(
			tailwindscore_mod( 'typography_body_font', 'system' )
		);
	}
	

	/**
	 * @param mixed $value Raw theme_mod.
	 * @return string
	 */
	private static function sanitize_slug( $value ): string {
		$slug     = is_string( $value ) ? sanitize_key( $value ) : 'system';
		$manifest = Tailwindscore_Typography_Fonts::get_manifest();

		if ( ! isset( $manifest[ $slug ] ) ) {
			return 'system';
		}

		return $slug;
	}
}
