<?php
declare(strict_types=1);

/**
 * Homepage "Shop by Color" block — WooCommerce attribute terms.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default section title.
 */
function tailwindscore_homepage_default_shop_by_color_title(): string {
	return __( 'Shop by color', 'tailwindscore' );
}

/**
 * Section title from Customizer.
 */
function tailwindscore_homepage_shop_by_color_title(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_shop_by_color_title',
			tailwindscore_homepage_default_shop_by_color_title()
		)
	);
}

/**
 * Whether the block is enabled in Customizer.
 */
function tailwindscore_homepage_shop_by_color_enabled(): bool {
	return (bool) tailwindscore_mod( 'homepage_shop_by_color_enable', true );
}

/**
 * Resolve the color attribute taxonomy (pa_color preferred over pa_colour).
 */
function tailwindscore_resolve_color_attribute_taxonomy(): string {
	$candidates = apply_filters(
		'tailwindscore/homepage_color_taxonomies',
		array( 'pa_color', 'pa_colour' )
	);

	foreach ( $candidates as $taxonomy ) {
		$taxonomy = (string) $taxonomy;
		if ( taxonomy_exists( $taxonomy ) ) {
			return $taxonomy;
		}
	}

	return '';
}

/**
 * Single-word color name → hex map for swatches without term meta.
 *
 * @return array<string, string>
 */
function tailwindscore_pure_color_hex_map(): array {
	$map = array(
		'black'  => '#000000',
		'red'    => '#c41e3a',
		'blue'   => '#1e3a5f',
		'green'  => '#2d5016',
		'pink'   => '#f4c2c2',
		'white'  => '#ffffff',
		'gold'   => '#c5a028',
		'silver' => '#c0c0c0',
		'purple' => '#6b2d5c',
		'nude'   => '#d4b896',
		'navy'   => '#001f3f',
		'grey'   => '#808080',
		'gray'   => '#808080',
		'beige'  => '#f5f5dc',
		'brown'  => '#8b4513',
		'orange' => '#ff6600',
		'yellow' => '#ffd700',
		'cream'  => '#fffdd0',
		'ivory'  => '#fffff0',
		'tan'    => '#d2b48c',
		'teal'   => '#008080',
		'maroon' => '#800000',
		'burgundy' => '#800020',
		'coral'  => '#ff7f50',
		'mint'   => '#98ff98',
		'lavender' => '#e6e6fa',
		'charcoal' => '#36454f',
	);

	/**
	 * Filter the pure-color hex map used for homepage swatches.
	 *
	 * @param array<string, string> $map Color name (lowercase) => hex.
	 */
	return apply_filters( 'tailwindscore/homepage_pure_color_hex_map', $map );
}

/**
 * Normalize a color term name for lookup (lowercase, no punctuation).
 */
function tailwindscore_normalize_color_term_name( string $name ): string {
	$normalized = strtolower( trim( $name ) );
	$normalized = preg_replace( '/[^a-z0-9\s-]/', '', $normalized ) ?? $normalized;
	$normalized = str_replace( '-', ' ', $normalized );
	$normalized = preg_replace( '/\s+/', ' ', $normalized ) ?? $normalized;

	return trim( $normalized );
}

/**
 * Whether a term name is a single mappable word (not "Sky Blue", etc.).
 */
function tailwindscore_is_single_word_color_name( string $name ): bool {
	$normalized = tailwindscore_normalize_color_term_name( $name );

	return $normalized !== '' && ! str_contains( $normalized, ' ' );
}

/**
 * Resolve swatch hex for a color attribute term.
 */
function tailwindscore_get_color_term_swatch_hex( WP_Term $term, string $taxonomy ): string {
	$dim = array(
		'taxonomy' => $taxonomy,
		'attr_key' => 'attribute_' . $taxonomy,
		'kind'     => 'color',
	);

	$color_meta_key = apply_filters( 'tailwindscore/swatch_color_meta_key', 'product_attribute_color', $dim );
	$meta_hex       = (string) get_term_meta( $term->term_id, $color_meta_key, true );

	if ( $meta_hex !== '' ) {
		$sanitized = sanitize_hex_color( $meta_hex );
		if ( $sanitized ) {
			return $sanitized;
		}
	}

	if ( ! tailwindscore_is_single_word_color_name( $term->name ) ) {
		return '';
	}

	$key = tailwindscore_normalize_color_term_name( $term->name );
	$map = tailwindscore_pure_color_hex_map();

	return $map[ $key ] ?? '';
}

/**
 * Whether a term can be rendered as a solid-color swatch.
 */
function tailwindscore_is_mappable_color_term( WP_Term $term, string $taxonomy ): bool {
	return tailwindscore_get_color_term_swatch_hex( $term, $taxonomy ) !== '';
}

/**
 * Build a shop URL filtered by this color term.
 */
function tailwindscore_get_color_filter_url( WP_Term $term, string $taxonomy ): string {
	$shop_url = function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'shop' )
		: home_url( '/' );

	if ( ! is_string( $shop_url ) || '' === $shop_url ) {
		$shop_url = home_url( '/' );
	}

	if ( function_exists( 'wc_attribute_taxonomy_slug' ) ) {
		$filter_key = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );
		$url        = add_query_arg( $filter_key, $term->slug, $shop_url );

		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	$term_link = get_term_link( $term, $taxonomy );

	return is_wp_error( $term_link ) ? $shop_url : (string) $term_link;
}

/**
 * Shop page URL for the "More" swatch.
 */
function tailwindscore_homepage_shop_by_color_more_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( '/' );
}

/**
 * Collect homepage color swatch items sorted by product count.
 *
 * @return list<array{slug: string, label: string, hex: string, url: string, count: int, is_light: bool}>
 */
function tailwindscore_homepage_shop_by_color_items( int $limit = 10 ): array {
	static $cached = null;

	if ( null !== $cached ) {
		return array_slice( $cached, 0, max( 0, $limit ) );
	}

	$cached = array();

	if ( ! function_exists( 'WC' ) ) {
		return array();
	}

	$taxonomy = tailwindscore_resolve_color_attribute_taxonomy();
	if ( '' === $taxonomy ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		if ( ! tailwindscore_is_mappable_color_term( $term, $taxonomy ) ) {
			continue;
		}

		$hex = tailwindscore_get_color_term_swatch_hex( $term, $taxonomy );
		if ( '' === $hex ) {
			continue;
		}

		$cached[] = array(
			'slug'     => $term->slug,
			'label'    => $term->name,
			'hex'      => $hex,
			'url'      => tailwindscore_get_color_filter_url( $term, $taxonomy ),
			'count'    => (int) $term->count,
			'is_light' => tailwindscore_is_light_swatch_hex( $hex ),
		);

		if ( count( $cached ) >= $limit ) {
			break;
		}
	}

	return $cached;
}

/**
 * Heuristic: light swatches need a visible border on pale backgrounds.
 */
function tailwindscore_is_light_swatch_hex( string $hex ): bool {
	$hex = ltrim( $hex, '#' );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
		return false;
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	// Relative luminance approximation.
	$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;

	return $luminance > 0.85;
}

/**
 * Whether the Shop by Color block should render.
 */
function tailwindscore_homepage_should_render_shop_by_color(): bool {
	if ( ! tailwindscore_homepage_shop_by_color_enabled() ) {
		return false;
	}

	return ! empty( tailwindscore_homepage_shop_by_color_items() );
}
