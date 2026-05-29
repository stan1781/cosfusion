<?php
declare(strict_types=1);

/**
 * Theme font manifest and @font-face generation.
 *
 * Add fonts from https://gwfh.mranftl.com/fonts (latin subset, woff2, weights 400 + 600).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bundled font registry.
 */
class Tailwindscore_Typography_Fonts {

	/**
	 * @return array<string, array{label: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}>
	 */
	public static function get_manifest(): array {
		$manifest = array(
			'system' => array(
				'label'  => __( 'System UI', 'tailwindscore' ),
				'family' => 'ui-sans-serif, system-ui, sans-serif',
				'faces'  => array(),
			),
			'inter' => array(
				'label'  => 'Inter',
				'family' => '"Inter", ui-sans-serif, system-ui, sans-serif',
				'faces'  => array(
					array(
						'family' => 'Inter',
						'weight' => 400,
						'style'  => 'normal',
						'file'   => 'inter-v20-latin-regular.woff2',
					),
					array(
						'family' => 'Inter',
						'weight' => 600,
						'style'  => 'normal',
						'file'   => 'inter-v20-latin-600.woff2',
					),
				),
			),			
			'playfair-display' => array(
				'label'  => 'Playfair Display',
				'family' => '"Playfair Display", Georgia, "Times New Roman", serif',
				'faces'  => array(
					array(
						'family' => 'Playfair Display',
						'weight' => 400,
						'style'  => 'normal',
						'file'   => 'playfair-display-v40-latin-regular.woff2',
					),
					array(
						'family' => 'Playfair Display',
						'weight' => 700,
						'style'  => 'normal',
						'file'   => 'playfair-display-v40-latin-700.woff2',
					),
				),
			),
		);

		/**
		 * Register or override bundled fonts.
		 *
		 * @param array<string, array{label: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}> $manifest
		 */
		return apply_filters( 'tailwindscore_typography_font_manifest', $manifest );
	}

	/**
	 * @return array<string, string> slug => label
	 */
	public static function get_choices(): array {
		$choices = array();

		foreach ( self::get_manifest() as $slug => $font ) {
			$choices[ $slug ] = (string) $font['label'];
		}

		return $choices;
	}

	/**
	 * @param string $slug Font slug.
	 * @return array{label: string, family: string, faces: list<array{family: string, weight: int, style: string, file: string}>}|null
	 */
	public static function get_font( string $slug ): ?array {
		$manifest = self::get_manifest();

		return isset( $manifest[ $slug ] ) ? $manifest[ $slug ] : null;
	}

	/**
	 * @param string[] $slugs Font slugs to load.
	 * @return string CSS @font-face rules.
	 */
	public static function build_font_face_css( array $slugs ): string {
		$css         = '';
		$loaded_keys = array();

		foreach ( array_unique( $slugs ) as $slug ) {
			if ( 'system' === $slug ) {
				continue;
			}

			$font = self::get_font( $slug );
			if ( null === $font || empty( $font['faces'] ) ) {
				continue;
			}

			foreach ( $font['faces'] as $face ) {
				$file = isset( $face['file'] ) ? (string) $face['file'] : '';
				if ( '' === $file ) {
					continue;
				}

				$path = get_template_directory() . '/assets/fonts/' . ltrim( $file, '/' );
				if ( ! is_readable( $path ) ) {
					continue;
				}

				$key = md5( $file );
				if ( isset( $loaded_keys[ $key ] ) ) {
					continue;
				}
				$loaded_keys[ $key ] = true;

				$family = isset( $face['family'] ) ? (string) $face['family'] : '';
				$weight = isset( $face['weight'] ) ? (int) $face['weight'] : 400;
				$style  = isset( $face['style'] ) ? (string) $face['style'] : 'normal';
				$url    = get_template_directory_uri() . '/assets/fonts/' . ltrim( $file, '/' );

				$css .= sprintf(
					"@font-face{font-family:%s;font-style:%s;font-weight:%d;font-display:swap;src:url('%s') format('woff2');}",
					self::css_string( $family ),
					self::css_string( $style ),
					$weight,
					esc_url_raw( $url )
				);
			}
		}

		return $css;
	}

	/**
	 * @param string $body_slug    Body font slug.
	 * @param string $heading_slug Heading font slug.
	 * @return string CSS custom properties for :root.
	 */
	public static function build_css_variables( string $body_slug, string $heading_slug ): string {
		$body_font    = self::resolve_family( $body_slug );
		$heading_font = self::resolve_family( $heading_slug );

		return sprintf(
			':root{--ts-type-body:%s;--ts-type-heading:%s;}',
			$body_font,
			$heading_font
		);
	}

	/**
	 * @param string $slug Font slug.
	 * @return string CSS font-family value.
	 */
	private static function resolve_family( string $slug ): string {
		$font = self::get_font( $slug );
		if ( null === $font ) {
			$system = self::get_font( 'system' );
			return null !== $system ? (string) $system['family'] : 'ui-sans-serif, system-ui, sans-serif';
		}

		return (string) $font['family'];
	}

	/**
	 * @param string $value Raw CSS string.
	 * @return string Quoted when needed.
	 */
	private static function css_string( string $value ): string {
		if ( preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
			return $value;
		}

		return '"' . str_replace( '"', '\\"', $value ) . '"';
	}
}
