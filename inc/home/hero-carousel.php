<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 将 Kirki / 附件字段解析为图片 URL。
 *
 * @param mixed $value Attachment ID、URL 或 Kirki image 数组。
 */
function tailwindscore_hero_resolve_image_url( $value ): string {
	if ( is_array( $value ) ) {
		if ( isset( $value['url'] ) ) {
			return tailwindscore_hero_resolve_image_url( $value['url'] );
		}
		if ( isset( $value['id'] ) ) {
			return tailwindscore_hero_resolve_image_url( $value['id'] );
		}

		return '';
	}

	if ( is_numeric( $value ) ) {
		$id = (int) $value;
		if ( $id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $id, 'full' );

		return is_string( $url ) ? $url : '';
	}

	$url = esc_url_raw( trim( (string) $value ) );

	return '' !== $url ? $url : '';
}

/**
 * Hero slidesPerView：1–4，0.5 步进。
 *
 * @param mixed $value Raw value.
 */
function tailwindscore_sanitize_hero_carousel_slide_count( $value ): float {
	$number = is_numeric( $value ) ? (float) $value : 1.0;
	$number = max( 1.0, min( 4.0, $number ) );

	return round( $number * 2.0 ) / 2.0;
}

/**
 * @param mixed $value Raw delay ms.
 */
function tailwindscore_sanitize_hero_autoplay_delay( $value ): int {
	$delay = is_numeric( $value ) ? (int) $value : 5000;

	return max( 2000, min( 10000, $delay ) );
}

/**
 * @param mixed $raw Repeater rows.
 * @return list<array{image: string, image_mobile: string, title: string, link_url: string, link_target: string, alt: string}>
 */
function tailwindscore_parse_hero_slides( $raw ): array {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$slides = array();

	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$image = tailwindscore_hero_resolve_image_url( $row['image'] ?? '' );
		if ( '' === $image ) {
			continue;
		}

		$target = sanitize_key( (string) ( $row['link_target'] ?? '_self' ) );
		if ( ! in_array( $target, array( '_self', '_blank' ), true ) ) {
			$target = '_self';
		}

		$link_url = esc_url_raw( trim( (string) ( $row['link_url'] ?? '' ) ) );

		$slides[] = array(
			'image'        => $image,
			'image_mobile' => tailwindscore_hero_resolve_image_url( $row['image_mobile'] ?? '' ),
			'title'        => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'link_url'     => $link_url,
			'link_target'  => $target,
			'alt'          => sanitize_text_field( (string) ( $row['alt'] ?? '' ) ),
		);
	}

	return $slides;
}

/**
 * @return array{
 *   enable: bool,
 *   autoplay: bool,
 *   autoplay_delay: int,
 *   columns_mobile: float,
 *   columns_desktop: float,
 *   gap: int,
 *   slides: list<array{image: string, image_mobile: string, title: string, link_url: string, link_target: string, alt: string}>
 * }
 */
function tailwindscore_get_home_hero_config(): array {
	$enable = (bool) tailwindscore_mod( 'home_hero_enable', true );
	$slides = $enable ? tailwindscore_parse_hero_slides( tailwindscore_mod( 'home_hero_slides', array() ) ) : array();

	$gap = function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
		? tailwindscore_sanitize_products_carousel_gap( tailwindscore_mod( 'home_hero_gap', 0 ) )
		: max( 0, min( 48, (int) tailwindscore_mod( 'home_hero_gap', 0 ) ) );

	return array(
		'enable'          => $enable,
		'autoplay'        => (bool) tailwindscore_mod( 'home_hero_autoplay', true ),
		'autoplay_delay'  => tailwindscore_sanitize_hero_autoplay_delay( tailwindscore_mod( 'home_hero_autoplay_delay', 5000 ) ),
		'columns_mobile'  => tailwindscore_sanitize_hero_carousel_slide_count( tailwindscore_mod( 'home_hero_columns_mobile', '1' ) ),
		'columns_desktop' => tailwindscore_sanitize_hero_carousel_slide_count( tailwindscore_mod( 'home_hero_columns_desktop', '1' ) ),
		'gap'             => $gap,
		'slides'          => $slides,
	);
}

/**
 * @return list<array{image: string, image_mobile: string, title: string, link_url: string, link_target: string, alt: string}>
 */
function tailwindscore_get_home_hero_slides(): array {
	$config = tailwindscore_get_home_hero_config();

	return $config['slides'];
}

/**
 * 共用 Hero 轮播输出（首页短代码 / 后续分类 term 可复用）。
 *
 * @param list<array{image?: string, image_mobile?: string, title?: string, link_url?: string, link_target?: string, alt?: string}> $slides
 * @param array{
 *   autoplay?: bool,
 *   autoplay_delay?: int,
 *   columns_mobile?: float|int|string,
 *   columns_desktop?: float|int|string,
 *   gap?: int|string,
 *   section_class?: string
 * } $args
 */
function tailwindscore_render_hero_carousel( array $slides, array $args = array() ): void {
	$slides = tailwindscore_parse_hero_slides( $slides );

	if ( array() === $slides ) {
		return;
	}

	$defaults = tailwindscore_get_home_hero_config();

	$autoplay       = array_key_exists( 'autoplay', $args ) ? (bool) $args['autoplay'] : $defaults['autoplay'];
	$autoplay_delay = array_key_exists( 'autoplay_delay', $args )
		? tailwindscore_sanitize_hero_autoplay_delay( $args['autoplay_delay'] )
		: $defaults['autoplay_delay'];
	$columns_mobile = array_key_exists( 'columns_mobile', $args )
		? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_mobile'] )
		: $defaults['columns_mobile'];
	$columns_desktop = array_key_exists( 'columns_desktop', $args )
		? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_desktop'] )
		: $defaults['columns_desktop'];

	if ( array_key_exists( 'gap', $args ) ) {
		$gap = function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
			? tailwindscore_sanitize_products_carousel_gap( $args['gap'] )
			: max( 0, min( 48, (int) $args['gap'] ) );
	} else {
		$gap = $defaults['gap'];
	}

	get_template_part(
		'template-parts/home/hero-carousel',
		null,
		array(
			'slides'          => $slides,
			'autoplay'        => $autoplay,
			'autoplay_delay'  => $autoplay_delay,
			'columns_mobile'  => $columns_mobile,
			'columns_desktop' => $columns_desktop,
			'gap'             => $gap,
			'section_class'   => isset( $args['section_class'] ) ? trim( (string) $args['section_class'] ) : '',
		)
	);
}

/**
 * @param array<string, string>|string $atts Shortcode attributes.
 */
function tailwindscore_home_hero_shortcode( $atts ): string {
	$config = tailwindscore_get_home_hero_config();

	if ( ! $config['enable'] || array() === $config['slides'] ) {
		return '';
	}

	$parsed = shortcode_atts(
		array(
			'columns_mobile'  => '',
			'columns_desktop' => '',
			'gap'             => '',
			'autoplay'        => '',
			'autoplay_delay'  => '',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_home_hero'
	);

	$args = array();

	if ( '' !== (string) $parsed['columns_mobile'] ) {
		$args['columns_mobile'] = $parsed['columns_mobile'];
	}
	if ( '' !== (string) $parsed['columns_desktop'] ) {
		$args['columns_desktop'] = $parsed['columns_desktop'];
	}
	if ( '' !== (string) $parsed['gap'] ) {
		$args['gap'] = $parsed['gap'];
	}
	if ( '' !== (string) $parsed['autoplay'] ) {
		$args['autoplay'] = in_array(
			strtolower( (string) $parsed['autoplay'] ),
			array( '1', 'true', 'yes', 'on' ),
			true
		);
	}
	if ( '' !== (string) $parsed['autoplay_delay'] ) {
		$args['autoplay_delay'] = $parsed['autoplay_delay'];
	}

	ob_start();
	tailwindscore_render_hero_carousel( $config['slides'], $args );

	return (string) ob_get_clean();
}
add_shortcode( 'ts_home_hero', 'tailwindscore_home_hero_shortcode' );
