<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 分栏 Hero（左文右图）：短代码 [ts_home_split_hero] + Kirki。
 * Spoutini 首页默认用此区块；全图轮播仍用 [ts_home_hero]。
 */

/**
 * @return array{
 *   enable: bool,
 *   eyebrow: string,
 *   title: string,
 *   text: string,
 *   cta_label: string,
 *   cta_url: string,
 *   image_id: int,
 *   image_url: string
 * }
 */
function tailwindscore_get_home_split_hero_config(): array {
	$image_raw = tailwindscore_mod( 'home_split_hero_image', '' );
	$image_id  = function_exists( 'tailwindscore_hero_resolve_image_id' )
		? tailwindscore_hero_resolve_image_id( $image_raw )
		: 0;
	$image_url = function_exists( 'tailwindscore_hero_resolve_image_url' )
		? tailwindscore_hero_resolve_image_url( $image_raw )
		: '';

	return array(
		'enable'    => (bool) tailwindscore_mod( 'home_split_hero_enable', true ),
		'eyebrow'   => sanitize_text_field( (string) tailwindscore_mod( 'home_split_hero_eyebrow', 'Outdoor & Adventure Gear' ) ),
		'title'     => sanitize_text_field( (string) tailwindscore_mod( 'home_split_hero_title', 'Gear up. Get out.' ) ),
		'text'      => sanitize_textarea_field( (string) tailwindscore_mod( 'home_split_hero_text', 'Everyday essentials for wherever adventure takes you.' ) ),
		'cta_label' => sanitize_text_field( (string) tailwindscore_mod( 'home_split_hero_cta_label', 'Shop all gear' ) ),
		'cta_url'   => esc_url_raw( (string) tailwindscore_mod( 'home_split_hero_cta_url', '' ) ),
		'image_id'  => $image_id,
		'image_url' => $image_url,
	);
}

/**
 * @param array<string, mixed> $args 覆盖配置（可选）。
 */
function tailwindscore_render_home_split_hero( array $args = array() ): void {
	$config = array_merge( tailwindscore_get_home_split_hero_config(), $args );

	if ( empty( $config['enable'] ) ) {
		return;
	}

	$title = trim( (string) ( $config['title'] ?? '' ) );
	if ( '' === $title ) {
		return;
	}

	get_template_part(
		'template-parts/home/split-hero',
		null,
		array(
			'eyebrow'   => (string) ( $config['eyebrow'] ?? '' ),
			'title'     => $title,
			'text'      => (string) ( $config['text'] ?? '' ),
			'cta_label' => (string) ( $config['cta_label'] ?? '' ),
			'cta_url'   => (string) ( $config['cta_url'] ?? '' ),
			'image_id'  => (int) ( $config['image_id'] ?? 0 ),
			'image_url' => (string) ( $config['image_url'] ?? '' ),
		)
	);
}

/**
 * @param array<string, string>|string $atts Shortcode attributes.
 */
function tailwindscore_home_split_hero_shortcode( $atts ): string {
	$config = tailwindscore_get_home_split_hero_config();

	if ( ! $config['enable'] ) {
		return '';
	}

	$parsed = shortcode_atts(
		array(
			'eyebrow'   => '',
			'title'     => '',
			'text'      => '',
			'cta_label' => '',
			'cta_url'   => '',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_home_split_hero'
	);

	$overrides = array();
	foreach ( array( 'eyebrow', 'title', 'text', 'cta_label', 'cta_url' ) as $key ) {
		if ( '' !== trim( (string) $parsed[ $key ] ) ) {
			$overrides[ $key ] = 'text' === $key
				? sanitize_textarea_field( (string) $parsed[ $key ] )
				: ( 'cta_url' === $key
					? esc_url_raw( (string) $parsed[ $key ] )
					: sanitize_text_field( (string) $parsed[ $key ] ) );
		}
	}

	ob_start();
	tailwindscore_render_home_split_hero( $overrides );

	return (string) ob_get_clean();
}
add_shortcode( 'ts_home_split_hero', 'tailwindscore_home_split_hero_shortcode' );
