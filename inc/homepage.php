<?php
declare(strict_types=1);

/**
 * Homepage Kirki settings and helpers.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default featured products shortcode (WooCommerce).
 */
function tailwindscore_homepage_default_products_shortcode(): string {
	return '[products limit="8" columns="4" orderby="date"]';
}

/**
 * Default homepage products section title.
 */
function tailwindscore_homepage_default_products_title(): string {
	return __( 'Featured products', 'tailwindscore' );
}

/**
 * Allowed hero CTA button styles.
 *
 * @return list<string>
 */
function tailwindscore_homepage_hero_link_styles(): array {
	return array( 'solid', 'outline' );
}

/**
 * Allowed hero text color modes.
 *
 * @return list<string>
 */
function tailwindscore_homepage_hero_text_colors(): array {
	return array( 'light', 'dark' );
}

/**
 * Sanitize hero slide CTA button style.
 */
function tailwindscore_homepage_sanitize_hero_link_style( $value ): string {
	$style = is_string( $value ) ? sanitize_key( $value ) : 'solid';

	return in_array( $style, tailwindscore_homepage_hero_link_styles(), true ) ? $style : 'solid';
}

/**
 * Sanitize hero slide text color mode.
 */
function tailwindscore_homepage_sanitize_hero_text_color( $value ): string {
	$color = is_string( $value ) ? sanitize_key( $value ) : 'light';

	return in_array( $color, tailwindscore_homepage_hero_text_colors(), true ) ? $color : 'light';
}

/**
 * Resolve a hero image value (attachment ID or legacy URL) to an attachment ID.
 *
 * @param mixed $image Raw value from theme_mod.
 */
function tailwindscore_homepage_hero_resolve_attachment_id( $image ): int {
	if ( is_numeric( $image ) ) {
		$attachment_id = absint( $image );

		return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
	}

	$url = is_string( $image ) ? trim( $image ) : '';

	if ( '' === $url ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( $url );

	return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
}

/**
 * Responsive hero slide image markup (srcset when the image is in the media library).
 *
 * @param array<string, mixed> $slide       Normalized slide data.
 * @param int                  $slide_index Zero-based slide index.
 */
function tailwindscore_homepage_hero_slide_image_markup( array $slide, int $slide_index, string $alt ): string {
	$attrs = array(
		'class'    => 'ts-home-hero__image',
		'alt'      => $alt,
		'decoding' => 'async',
		'loading'  => 0 === $slide_index ? 'eager' : 'lazy',
		'sizes'    => '100vw',
	);

	if ( 0 === $slide_index ) {
		$attrs['fetchpriority'] = 'high';
	}

	$image_id = isset( $slide['image_id'] ) ? absint( $slide['image_id'] ) : 0;

	if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
		return (string) wp_get_attachment_image( $image_id, 'full', false, $attrs );
	}

	$src = isset( $slide['image'] ) ? trim( (string) $slide['image'] ) : '';

	if ( '' === $src ) {
		return '';
	}

	$html = sprintf( '<img src="%s"', esc_url( $src ) );

	foreach ( $attrs as $name => $value ) {
		$html .= sprintf( ' %s="%s"', esc_attr( (string) $name ), esc_attr( (string) $value ) );
	}

	$html .= '>';

	return $html;
}

/**
 * Normalized hero slides from theme_mod (skips rows without an image).
 *
 * @return array<int, array{image: string, image_id: int, title: string, subtitle: string, description: string, link_url: string, link_label: string, link_style: string, text_color: string}>
 */
function tailwindscore_homepage_hero_slides(): array {
	$slides = tailwindscore_mod( 'homepage_hero_slides', array() );

	if ( ! is_array( $slides ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $slides as $slide ) {
		if ( ! is_array( $slide ) ) {
			continue;
		}

		$image_raw = $slide['image'] ?? '';
		$image_id  = tailwindscore_homepage_hero_resolve_attachment_id( $image_raw );
		$image_url = '';

		if ( $image_id > 0 ) {
			$image_url = (string) wp_get_attachment_image_url( $image_id, 'full' );
		}

		if ( '' === $image_url && is_string( $image_raw ) ) {
			$image_url = trim( $image_raw );
		}

		if ( '' === $image_url ) {
			continue;
		}

		$normalized[] = array(
			'image'       => $image_url,
			'image_id'    => $image_id,
			'title'       => isset( $slide['title'] ) ? trim( (string) $slide['title'] ) : '',
			'subtitle'    => isset( $slide['subtitle'] ) ? trim( (string) $slide['subtitle'] ) : '',
			'description' => isset( $slide['description'] ) ? trim( (string) $slide['description'] ) : '',
			'link_url'    => isset( $slide['link_url'] ) ? trim( (string) $slide['link_url'] ) : '',
			'link_label'  => isset( $slide['link_label'] ) ? trim( (string) $slide['link_label'] ) : '',
			'link_style'  => tailwindscore_homepage_sanitize_hero_link_style( $slide['link_style'] ?? 'solid' ),
			'text_color'  => tailwindscore_homepage_sanitize_hero_text_color( $slide['text_color'] ?? 'light' ),
		);
	}

	return $normalized;
}

/**
 * Whether the homepage products block is enabled.
 */
function tailwindscore_homepage_products_enabled(): bool {
	return (bool) tailwindscore_mod( 'homepage_products_enable', true );
}

/**
 * Homepage products section heading.
 */
function tailwindscore_homepage_products_title(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_products_title',
			tailwindscore_homepage_default_products_title()
		)
	);
}

/**
 * Shortcode string for the homepage products block.
 */
function tailwindscore_homepage_products_shortcode(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_products_shortcode',
			tailwindscore_homepage_default_products_shortcode()
		)
	);
}

/**
 * Whether the homepage products block should render.
 */
function tailwindscore_homepage_should_render_products(): bool {
	if ( ! tailwindscore_homepage_products_enabled() ) {
		return false;
	}

	return '' !== tailwindscore_homepage_products_shortcode();
}

/**
 * Snap a slide-count value to 0.5 steps within 1–6.
 */
function tailwindscore_homepage_sanitize_slide_count( $value ): float {
	$number = is_numeric( $value ) ? (float) $value : 1.0;
	$number = max( 1.0, min( 6.0, $number ) );

	return round( $number * 2.0 ) / 2.0;
}

/**
 * Product carousel gap in pixels (0–48).
 */
function tailwindscore_homepage_sanitize_slide_gap( $value ): int {
	return max( 0, min( 48, absint( $value ) ) );
}

/**
 * Whether the hero banner should autoplay (only applies when there are 2+ slides).
 */
function tailwindscore_homepage_hero_autoplay(): bool {
	return (bool) tailwindscore_mod( 'homepage_hero_autoplay', false );
}

/**
 * Visible product slides on viewports below the md breakpoint (supports 2.5 peek).
 */
function tailwindscore_homepage_products_slides_mobile(): float {
	return tailwindscore_homepage_sanitize_slide_count(
		tailwindscore_mod( 'homepage_products_slides_mobile', 2.5 )
	);
}

/**
 * Visible product slides on md breakpoint and up.
 */
function tailwindscore_homepage_products_slides_desktop(): float {
	return tailwindscore_homepage_sanitize_slide_count(
		tailwindscore_mod( 'homepage_products_slides_desktop', 4.0 )
	);
}

/**
 * Gap between product slides in pixels.
 */
function tailwindscore_homepage_products_slide_gap(): int {
	return tailwindscore_homepage_sanitize_slide_gap(
		tailwindscore_mod( 'homepage_products_slide_gap', 24 )
	);
}

/**
 * Swiper runtime config for the front page.
 *
 * @return array<string, mixed>
 */
function tailwindscore_homepage_swiper_config(): array {
	return array(
		'hero'     => array(
			'autoplay'      => tailwindscore_homepage_hero_autoplay(),
			'autoplayDelay' => 5000,
		),
		'products' => array(
			'slidesMobile'  => tailwindscore_homepage_products_slides_mobile(),
			'slidesDesktop' => tailwindscore_homepage_products_slides_desktop(),
			'spaceBetween'  => tailwindscore_homepage_products_slide_gap(),
			'breakpoint'    => 768,
		),
	);
}

/**
 * @param array<string, mixed> $parts Runtime config fragments.
 * @return array<string, mixed>
 */
function tailwindscore_homepage_runtime_config_parts( array $parts ): array {
	if ( ! is_front_page() ) {
		return $parts;
	}

	$parts['home'] = tailwindscore_homepage_swiper_config();

	return $parts;
}
add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_homepage_runtime_config_parts' );

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_homepage_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_homepage_display',
		array(
			'title'       => esc_html__( 'Homepage display', 'tailwindscore' ),
			'description' => esc_html__( 'Hero banner slides and product blocks for the front page.', 'tailwindscore' ),
			'priority'    => 125,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'repeater',
			'settings'    => 'homepage_hero_slides',
			'label'       => esc_html__( 'Hero slides', 'tailwindscore' ),
			'description' => esc_html__( 'Add one or more banner slides. Rows without an image are ignored on the front page.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 10,
			'row_label'   => array(
				'type'  => 'field',
				'value' => esc_html__( 'Slide', 'tailwindscore' ),
				'field' => 'title',
			),
			'default'     => array(),
			'fields'      => array(
				'image'      => array(
					'type'    => 'image',
					'label'   => esc_html__( 'Image', 'tailwindscore' ),
					'default' => '',
					'choices' => array(
						'save_as' => 'id',
					),
				),
				'subtitle'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Eyebrow', 'tailwindscore' ),
					'default' => '',
				),
				'title'      => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Headline', 'tailwindscore' ),
					'default' => '',
				),
				'description' => array(
					'type'    => 'textarea',
					'label'   => esc_html__( 'Description', 'tailwindscore' ),
					'default' => '',
				),
				'text_color' => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Text color', 'tailwindscore' ),
					'default' => 'light',
					'choices' => array(
						'light' => esc_html__( 'Light', 'tailwindscore' ),
						'dark'  => esc_html__( 'Dark', 'tailwindscore' ),
					),
				),
				'link_url'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Link URL', 'tailwindscore' ),
					'default' => '',
				),
				'link_label' => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Button label', 'tailwindscore' ),
					'default' => '',
				),
				'link_style' => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Button style', 'tailwindscore' ),
					'default' => 'solid',
					'choices' => array(
						'solid'   => esc_html__( 'Solid', 'tailwindscore' ),
						'outline' => esc_html__( 'Outline', 'tailwindscore' ),
					),
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'homepage_hero_autoplay',
			'label'       => esc_html__( 'Hero autoplay', 'tailwindscore' ),
			'description' => esc_html__( 'Rotate hero slides automatically when there are two or more banners.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 15,
			'default'     => false,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'homepage_products_enable',
			'label'    => esc_html__( 'Show products block', 'tailwindscore' ),
			'section'  => 'tailwindscore_homepage_display',
			'priority' => 20,
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'homepage_products_title',
			'label'           => esc_html__( 'Products block title', 'tailwindscore' ),
			'section'         => 'tailwindscore_homepage_display',
			'priority'        => 30,
			'default'         => tailwindscore_homepage_default_products_title(),
			'active_callback' => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'homepage_products_shortcode',
			'label'           => esc_html__( 'Products shortcode', 'tailwindscore' ),
			'description'     => esc_html__( 'WooCommerce product shortcode, e.g. [products limit="8" columns="4"].', 'tailwindscore' ),
			'section'         => 'tailwindscore_homepage_display',
			'priority'        => 40,
			'default'         => tailwindscore_homepage_default_products_shortcode(),
			'active_callback' => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'slider',
			'settings'          => 'homepage_products_slides_mobile',
			'label'             => esc_html__( 'Products visible (mobile)', 'tailwindscore' ),
			'description'       => esc_html__( 'Slides per view below 768px. Use decimals (e.g. 2.5) to peek the next card.', 'tailwindscore' ),
			'section'           => 'tailwindscore_homepage_display',
			'priority'          => 50,
			'default'           => 2.5,
			'choices'           => array(
				'min'  => 1,
				'max'  => 6,
				'step' => 0.5,
			),
			'sanitize_callback' => 'tailwindscore_homepage_sanitize_slide_count',
			'active_callback'   => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'slider',
			'settings'          => 'homepage_products_slides_desktop',
			'label'             => esc_html__( 'Products visible (desktop)', 'tailwindscore' ),
			'description'       => esc_html__( 'Slides per view from 768px up. Use decimals (e.g. 4.5) to peek the next card.', 'tailwindscore' ),
			'section'           => 'tailwindscore_homepage_display',
			'priority'          => 51,
			'default'           => 4,
			'choices'           => array(
				'min'  => 1,
				'max'  => 6,
				'step' => 0.5,
			),
			'sanitize_callback' => 'tailwindscore_homepage_sanitize_slide_count',
			'active_callback'   => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'slider',
			'settings'          => 'homepage_products_slide_gap',
			'label'             => esc_html__( 'Product slide gap', 'tailwindscore' ),
			'description'       => esc_html__( 'Space between product cards in pixels.', 'tailwindscore' ),
			'section'           => 'tailwindscore_homepage_display',
			'priority'          => 52,
			'default'           => 24,
			'choices'           => array(
				'min'  => 0,
				'max'  => 48,
				'step' => 4,
			),
			'sanitize_callback' => 'tailwindscore_homepage_sanitize_slide_gap',
			'active_callback'   => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_homepage_kirki_register', 10, 2 );
