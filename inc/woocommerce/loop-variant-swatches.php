<?php
declare(strict_types=1);

/**
 * WooCommerce product loop variant swatches (archive cards).
 *
 * Single row per card: pa_color/pa_colour > pa_style. Only variations with image_id.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TAILWINDSCORE_LOOP_SWATCH_MAX' ) ) {
	define( 'TAILWINDSCORE_LOOP_SWATCH_MAX', 5 );
}

/**
 * Resolve swatch dimension for a variable product.
 *
 * @param WC_Product $product Product instance.
 * @return array{taxonomy: string, attr_key: string, kind: string}|false
 */
function tailwindscore_get_loop_swatch_dimension( WC_Product $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return false;
	}

	/**
	 * Filter swatch taxonomy groups.
	 *
	 * @param array<string, string[]> $taxes   Taxonomy lists by kind.
	 * @param WC_Product              $product Current product.
	 */
	$taxes = apply_filters(
		'tailwindscore/swatch_taxonomies',
		array(
			'color' => array( 'pa_color', 'pa_colour' ),
			'style' => array( 'pa_style' ),
		),
		$product
	);

	$attrs = $product->get_variation_attributes();
	if ( empty( $attrs ) ) {
		return false;
	}

	$keys = array_map( 'strtolower', array_keys( $attrs ) );

	foreach ( array( 'color', 'style' ) as $kind ) {
		if ( empty( $taxes[ $kind ] ) || ! is_array( $taxes[ $kind ] ) ) {
			continue;
		}
		foreach ( $taxes[ $kind ] as $tx ) {
			$tx_lc = strtolower( (string) $tx );
			if ( in_array( $tx_lc, $keys, true ) ) {
				return array(
					'taxonomy' => $tx_lc,
					'attr_key' => 'attribute_' . $tx_lc,
					'kind'     => $kind,
				);
			}
		}
	}

	return false;
}

/**
 * Build thumbnail image payload for loop swatch switching.
 *
 * @param int $image_id Attachment ID.
 * @return array{src: string, srcset: string, sizes: string, alt: string}|null
 */
function tailwindscore_loop_swatch_thumb( int $image_id ): ?array {
	if ( $image_id <= 0 ) {
		return null;
	}

	$src = wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' );
	if ( ! $src ) {
		return null;
	}

	$srcset = wp_get_attachment_image_srcset( $image_id, 'woocommerce_thumbnail' );
	$sizes  = wp_get_attachment_image_sizes( $image_id, 'woocommerce_thumbnail' );
	$alt    = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );

	return array(
		'src'    => esc_url_raw( (string) $src[0] ),
		'srcset' => $srcset ? (string) wp_strip_all_tags( $srcset ) : '',
		'sizes'  => $sizes ? (string) wp_strip_all_tags( $sizes ) : '',
		'alt'    => $alt,
	);
}

/**
 * Collect deduplicated swatch items (first variation per slug with an image).
 *
 * @param WC_Product_Variable $product Variable product.
 * @param array               $dim     Dimension from tailwindscore_get_loop_swatch_dimension().
 * @return list<array{slug: string, label: string, swatch_color: string, swatch_thumb: string, image: array}>
 */
function tailwindscore_collect_loop_swatch_items( WC_Product_Variable $product, array $dim ): array {
	$items    = array();
	$seen     = array();
	$taxonomy = $dim['taxonomy'];
	$attr_key = $dim['attr_key'];

	foreach ( $product->get_available_variations() as $variation ) {
		if ( empty( $variation['variation_is_visible'] ) ) {
			continue;
		}

		$attributes = isset( $variation['attributes'] ) ? (array) $variation['attributes'] : array();
		$slug       = '';

		foreach ( $attributes as $k => $v ) {
			if ( strtolower( (string) $k ) === $attr_key ) {
				$slug = (string) $v;
				break;
			}
		}

		if ( $slug === '' || isset( $seen[ $slug ] ) ) {
			continue;
		}

		$image_id = isset( $variation['image_id'] ) ? absint( $variation['image_id'] ) : 0;
		if ( $image_id <= 0 ) {
			continue;
		}

		$image = tailwindscore_loop_swatch_thumb( $image_id );
		if ( ! $image ) {
			continue;
		}

		$term         = get_term_by( 'slug', $slug, $taxonomy );
		$label        = $term ? $term->name : ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );
		$swatch_color = '';
		$swatch_thumb = '';

		if ( $term ) {
			/** @var array{taxonomy: string, attr_key: string, kind: string} $dim */
			$color_meta_key = apply_filters( 'tailwindscore/swatch_color_meta_key', 'product_attribute_color', $dim );
			$thumb_meta_key = apply_filters( 'tailwindscore/swatch_thumb_meta_key', 'product_attribute_image', $dim );

			$swatch_color = (string) get_term_meta( $term->term_id, $color_meta_key, true );

			$thumb_id = absint( get_term_meta( $term->term_id, $thumb_meta_key, true ) );
			if ( $thumb_id > 0 ) {
				$thumb_src = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
				if ( $thumb_src ) {
					$swatch_thumb = esc_url_raw( (string) $thumb_src[0] );
				}
			}
		}

		$seen[ $slug ] = true;

		$items[] = array(
			'slug'         => sanitize_title( $slug ),
			'label'        => $label,
			'swatch_color' => $swatch_color,
			'swatch_thumb' => $swatch_thumb,
			'image'        => $image,
		);
	}

	/**
	 * Filter swatch items before render.
	 *
	 * @param list<array>             $items   Swatch items.
	 * @param WC_Product_Variable     $product Product.
	 * @param array                   $dim     Dimension.
	 */
	return apply_filters( 'tailwindscore/loop_swatch_items', $items, $product, $dim );
}

/**
 * Render loop card variant swatches.
 *
 * @param WC_Product|null $product Product instance.
 * @return void
 */
function tailwindscore_render_loop_variant_swatches( ?WC_Product $product = null ): void {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	$dim = tailwindscore_get_loop_swatch_dimension( $product );
	if ( ! $dim ) {
		return;
	}

	/** @var WC_Product_Variable $product */
	$items = tailwindscore_collect_loop_swatch_items( $product, $dim );
	if ( empty( $items ) ) {
		return;
	}

	$max = (int) apply_filters( 'tailwindscore/loop_swatch_max', TAILWINDSCORE_LOOP_SWATCH_MAX, $product, $dim );

	$total   = count( $items );
	$visible = $max > 0 ? array_slice( $items, 0, $max ) : $items;
	$hidden  = $max > 0 ? max( 0, $total - $max ) : 0;

	$kind_class = 'ts-wc-product-card__swatch--' . sanitize_html_class( $dim['kind'] );

	echo '<div class="ts-wc-product-card__swatches" data-product-card-swatches data-kind="' . esc_attr( $dim['kind'] ) . '">';

	foreach ( $visible as $item ) {
		$data_image = wp_json_encode( $item['image'] );
		$label_attr = esc_attr( $item['label'] );

		if ( in_array( $dim['kind'], array( 'color', 'style' ), true ) && $item['swatch_thumb'] === '' && ! empty( $item['image']['src'] ) ) {
			$item['swatch_thumb'] = (string) $item['image']['src'];
		}

		$styles = array();
		if ( $item['swatch_color'] !== '' ) {
			$styles[] = '--swatch-color:' . esc_attr( $item['swatch_color'] );
		}
		if ( $item['swatch_thumb'] !== '' ) {
			$styles[] = '--swatch-thumb:url(' . esc_url( $item['swatch_thumb'] ) . ')';
		}
		$style_attr = ! empty( $styles ) ? ' style="' . implode( ';', $styles ) . '"' : '';

		$has_visual   = ( $item['swatch_color'] !== '' || $item['swatch_thumb'] !== '' );
		$inner        = $has_visual ? '' : esc_html( $item['label'] );
		$button_class = trim( $kind_class . ( $has_visual ? ' ts-wc-product-card__swatch--visual' : '' ) );

		printf(
			'<button type="button" class="ts-wc-product-card__swatch %1$s" data-swatch-slug="%2$s" data-image=\'%3$s\' aria-label="%4$s" title="%4$s" aria-pressed="false"%5$s>%6$s</button>',
			esc_attr( $button_class ),
			esc_attr( $item['slug'] ),
			esc_attr( (string) $data_image ),
			$label_attr,
			$style_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html when used.
		);
	}

	if ( $hidden > 0 ) {
		echo '<span class="ts-wc-product-card__swatch-more" aria-hidden="true">+' . esc_html( (string) $hidden ) . '</span>';
	}

	echo '</div>';
}
