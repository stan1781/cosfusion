<?php
declare(strict_types=1);

/**
 * Enqueue bundled fonts and runtime typography CSS variables.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end typography output.
 */
class Tailwindscore_Typography_Frontend {

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_typography' ), 15 );
	}

	/**
	 * @return void
	 */
	public function enqueue_typography(): void {
		$body_slug    = Tailwindscore_Typography_Settings::get_body_font_slug();
		$heading_slug = Tailwindscore_Typography_Settings::get_heading_font_slug();

		$css  = Tailwindscore_Typography_Fonts::build_font_face_css( array( $body_slug, $heading_slug ) );
		$css .= Tailwindscore_Typography_Fonts::build_css_variables( $body_slug, $heading_slug );

		if ( '' === $css ) {
			return;
		}

		$handle = wp_style_is( 'tailwindscore-app', 'enqueued' ) ? 'tailwindscore-app' : 'wp-block-library';

		wp_add_inline_style( $handle, $css );
	}
}
