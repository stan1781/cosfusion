<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 分类磁贴短代码 [ts_category_tiles]：动态 product_cat。
 */

/**
 * @param mixed $raw 逗号分隔 ID / slug。
 * @return list<string>
 */
function tailwindscore_parse_category_tiles_list( $raw ): array {
	$parts = array_map( 'trim', explode( ',', (string) $raw ) );
	$out   = array();

	foreach ( $parts as $part ) {
		if ( '' === $part ) {
			continue;
		}
		$out[] = is_numeric( $part ) ? (string) absint( $part ) : sanitize_title( $part );
	}

	return array_values( array_unique( $out ) );
}

/**
 * @param array<string, mixed> $atts 已解析属性。
 * @return list<WP_Term>
 */
function tailwindscore_get_category_tiles_terms( array $atts ): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$limit   = max( 1, min( 12, (int) ( $atts['limit'] ?? 4 ) ) );
	$include = is_array( $atts['ids'] ?? null ) ? $atts['ids'] : array();

	$terms = array();

	if ( array() !== $include ) {
		foreach ( $include as $token ) {
			if ( is_numeric( $token ) ) {
				$term = get_term( (int) $token, 'product_cat' );
			} else {
				$term = get_term_by( 'slug', (string) $token, 'product_cat' );
			}
			if ( $term instanceof WP_Term && ! is_wp_error( $term ) && 'uncategorized' !== $term->slug ) {
				$terms[] = $term;
			}
			if ( count( $terms ) >= $limit ) {
				break;
			}
		}

		return $terms;
	}

	$fetched = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => $limit + 2,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $fetched ) || ! is_array( $fetched ) ) {
		return array();
	}

	foreach ( $fetched as $term ) {
		if ( ! $term instanceof WP_Term || 'uncategorized' === $term->slug ) {
			continue;
		}
		$terms[] = $term;
		if ( count( $terms ) >= $limit ) {
			break;
		}
	}

	return $terms;
}

/**
 * @param WP_Term $term
 * @return array{html: string, url: string}
 */
function tailwindscore_category_tiles_term_image( WP_Term $term ): array {
	$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	$url      = get_term_link( $term );
	$url      = is_wp_error( $url ) ? '' : (string) $url;

	$html = '';
	if ( $thumb_id > 0 ) {
		$html = wp_get_attachment_image(
			$thumb_id,
			'large',
			false,
			array(
				'class'    => 'category-tiles__img',
				'alt'      => '',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		$html = is_string( $html ) ? $html : '';
	}

	return array(
		'html' => $html,
		'url'  => $url,
	);
}

/**
 * @param array<string, mixed> $atts
 */
function tailwindscore_render_category_tiles( array $atts ): void {
	$terms = tailwindscore_get_category_tiles_terms( $atts );
	if ( array() === $terms ) {
		return;
	}

	$columns = max( 1, min( 6, (int) ( $atts['columns'] ?? 4 ) ) );

	get_template_part(
		'template-parts/home/category-tiles',
		null,
		array(
			'terms'   => $terms,
			'columns' => $columns,
			'title'   => (string) ( $atts['title'] ?? '' ),
		)
	);
}

/**
 * @param array<string, string>|string $atts
 */
function tailwindscore_category_tiles_shortcode( $atts ): string {
	$parsed = shortcode_atts(
		array(
			'ids'     => '',
			'slugs'   => '',
			'columns' => '4',
			'limit'   => '4',
			'title'   => '',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_category_tiles'
	);

	$list = tailwindscore_parse_category_tiles_list( $parsed['ids'] );
	if ( array() === $list ) {
		$list = tailwindscore_parse_category_tiles_list( $parsed['slugs'] );
	}

	$config = array(
		'ids'     => $list,
		'columns' => max( 1, min( 6, absint( $parsed['columns'] ) ) ),
		'limit'   => max( 1, min( 12, absint( $parsed['limit'] ) ) ),
		'title'   => sanitize_text_field( (string) $parsed['title'] ),
	);

	ob_start();
	tailwindscore_render_category_tiles( $config );

	return (string) ob_get_clean();
}
add_shortcode( 'ts_category_tiles', 'tailwindscore_category_tiles_shortcode' );
