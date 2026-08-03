<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 内容区块 — [block id="slug|post_id"] 短代码（Flatsome 兼容）。
 */
final class Tailwindscore_Content_Block_Shortcode {

	public static function init(): void {
		add_shortcode( 'block', array( self::class, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'block'
		);

		return Tailwindscore_Content_Block_Renderer::render( (string) $atts['id'] );
	}
}
