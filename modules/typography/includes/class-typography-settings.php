<?php
declare(strict_types=1);

/**
 * 排版 theme_mod 访问器。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 标题与正文字体选择。
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
	 * @param mixed $value 原始 theme_mod。
	 * @return string
	 */
	public static function sanitize_slug( $value ): string {
		$slug     = is_string( $value ) ? sanitize_title( $value ) : 'system';
		$manifest = Tailwindscore_Typography_Fonts::get_manifest();

		if ( ! isset( $manifest[ $slug ] ) ) {
			return 'system';
		}

		return $slug;
	}
}
