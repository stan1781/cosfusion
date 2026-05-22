<?php
declare(strict_types=1);

/**
 * Build parent product gallery payloads for the frontend.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param int    $image_id Attachment ID.
 * @param bool   $is_main  Main slide flag.
 * @param string $thumb_size Thumbnail size slug.
 * @param string $gallery_size Single/gallery slide size slug.
 * @return array<string, mixed>|null
 */
function tailwindscore_gallery_build_image_item(
	int $image_id,
	bool $is_main = false,
	string $thumb_size = 'woocommerce_thumbnail',
	string $gallery_size = 'woocommerce_single'
): ?array {
	if ( $image_id <= 0 ) {
		return null;
	}

	$full = wp_get_attachment_image_src( $image_id, 'full' );
	if ( ! $full ) {
		return null;
	}

	$thumb = wp_get_attachment_image_src( $image_id, $thumb_size )
		?: wp_get_attachment_image_src( $image_id, 'thumbnail' )
		?: $full;
	$gallery = wp_get_attachment_image_src( $image_id, $gallery_size )
		?: wp_get_attachment_image_src( $image_id, 'large' )
		?: $full;

	$srcset      = wp_get_attachment_image_srcset( $image_id, 'full' );
	$sizes       = wp_get_attachment_image_sizes( $image_id, 'full' );
	$thumb_srcset = wp_get_attachment_image_srcset( $image_id, $thumb_size );
	$thumb_sizes  = wp_get_attachment_image_sizes( $image_id, $thumb_size );
	$gallery_srcset = wp_get_attachment_image_srcset( $image_id, $gallery_size );
	$gallery_sizes  = wp_get_attachment_image_sizes( $image_id, $gallery_size );

	return array(
		'id'              => $image_id,
		'src'             => esc_url_raw( (string) $full[0] ),
		'thumbnail'       => esc_url_raw( (string) $thumb[0] ),
		'gallery'         => esc_url_raw( (string) $gallery[0] ),
		'gallery_thumbnail_src' => esc_url_raw( (string) $thumb[0] ),
		'alt'             => (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
		'srcset'          => $srcset ? (string) wp_strip_all_tags( $srcset ) : '',
		'sizes'           => $sizes ? (string) wp_strip_all_tags( $sizes ) : '',
		'thumb_srcset'    => $thumb_srcset ? (string) wp_strip_all_tags( $thumb_srcset ) : '',
		'thumb_sizes'     => $thumb_sizes ? (string) wp_strip_all_tags( $thumb_sizes ) : '',
		'gallery_srcset'  => $gallery_srcset ? (string) wp_strip_all_tags( $gallery_srcset ) : '',
		'gallery_sizes'   => $gallery_sizes ? (string) wp_strip_all_tags( $gallery_sizes ) : '',
		'width'           => ! empty( $full[1] ) ? absint( $full[1] ) : 0,
		'height'          => ! empty( $full[2] ) ? absint( $full[2] ) : 0,
		'is_main'         => $is_main,
	);
}

/**
 * Collect parent product gallery image IDs (featured first, then gallery).
 *
 * @param WC_Product $product Product.
 * @return int[]
 */
function tailwindscore_gallery_collect_attachment_ids( WC_Product $product ): array {
	$ids = array();

	$featured = $product->get_image_id();
	if ( $featured ) {
		$ids[] = absint( $featured );
	}

	$gallery_ids = $product->get_gallery_image_ids();
	foreach ( $gallery_ids as $gid ) {
		$gid = absint( $gid );
		if ( $gid > 0 && ! in_array( $gid, $ids, true ) ) {
			$ids[] = $gid;
		}
	}

	return $ids;
}

/**
 * When the parent variable product has no featured/gallery images, use visible variation images.
 *
 * @param WC_Product_Variable $product Variable product.
 * @return int[]
 */
function tailwindscore_gallery_variation_fallback_ids( WC_Product_Variable $product ): array {
	$ids = array();

	foreach ( $product->get_visible_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			continue;
		}

		$image_id = $variation->get_image_id();
		if ( $image_id > 0 && ! in_array( $image_id, $ids, true ) ) {
			$ids[] = $image_id;
		}
	}

	return $ids;
}

/**
 * WooCommerce placeholder slide when no attachments exist.
 *
 * @return array<string, mixed>
 */
function tailwindscore_gallery_placeholder_item(): array {
	$src = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '';
	if ( ! $src ) {
		return array();
	}

	return array(
		'id'                    => 0,
		'src'                   => esc_url_raw( $src ),
		'thumbnail'             => esc_url_raw( $src ),
		'gallery'               => esc_url_raw( $src ),
		'gallery_thumbnail_src' => esc_url_raw( $src ),
		'alt'                   => '',
		'srcset'                => '',
		'sizes'                 => '',
		'thumb_srcset'          => '',
		'thumb_sizes'           => '',
		'gallery_srcset'        => '',
		'gallery_sizes'         => '',
		'width'                 => 0,
		'height'                => 0,
		'is_main'               => true,
	);
}

/**
 * @param WC_Product $product Product.
 * @return list<array<string, mixed>>
 */
function tailwindscore_gallery_parent_images( WC_Product $product ): array {
	$images   = array();
	$ids      = tailwindscore_gallery_collect_attachment_ids( $product );
	$is_first = true;

	if ( empty( $ids ) && $product->is_type( 'variable' ) && $product instanceof WC_Product_Variable ) {
		$ids = tailwindscore_gallery_variation_fallback_ids( $product );
	}

	foreach ( $ids as $image_id ) {
		$item = tailwindscore_gallery_build_image_item( $image_id, $is_first );
		if ( $item ) {
			$images[] = $item;
			$is_first = false;
		}
	}

	if ( empty( $images ) ) {
		$placeholder = tailwindscore_gallery_placeholder_item();
		if ( ! empty( $placeholder ) ) {
			$images[] = $placeholder;
		}
	}

	/**
	 * Filter parent gallery slides before localize.
	 *
	 * @param list<array<string, mixed>> $images  Gallery items.
	 * @param WC_Product                 $product Product.
	 */
	return apply_filters( 'tailwindscore/gallery/parent_images', $images, $product );
}

/**
 * Normalize WooCommerce variation.image for the gallery layer.
 *
 * @param array<string, mixed> $variation Available variation row.
 * @return array<string, mixed>|null
 */
function tailwindscore_gallery_image_from_wc_variation( array $variation ): ?array {
	$image = isset( $variation['image'] ) && is_array( $variation['image'] ) ? $variation['image'] : null;
	if ( ! $image || empty( $image['src'] ) ) {
		return null;
	}

	$image_id = isset( $variation['image_id'] ) ? absint( $variation['image_id'] ) : 0;
	$thumb    = ! empty( $image['gallery_thumbnail_src'] ) ? (string) $image['gallery_thumbnail_src'] : (string) $image['src'];
	$gallery  = ! empty( $image['full_src'] ) ? (string) $image['full_src'] : (string) $image['src'];

	return array(
		'id'                    => $image_id,
		'src'                   => esc_url_raw( (string) $image['src'] ),
		'thumbnail'             => esc_url_raw( $thumb ),
		'gallery'               => esc_url_raw( $gallery ),
		'gallery_thumbnail_src' => esc_url_raw( $thumb ),
		'alt'                   => isset( $image['alt'] ) ? (string) $image['alt'] : '',
		'srcset'                => isset( $image['srcset'] ) ? (string) $image['srcset'] : '',
		'sizes'                 => isset( $image['sizes'] ) ? (string) $image['sizes'] : '',
		'thumb_srcset'          => isset( $image['srcset'] ) ? (string) $image['srcset'] : '',
		'thumb_sizes'           => isset( $image['sizes'] ) ? (string) $image['sizes'] : '',
		'gallery_srcset'        => isset( $image['full_srcset'] ) ? (string) $image['full_srcset'] : '',
		'gallery_sizes'         => isset( $image['full_src_sizes'] ) ? (string) $image['full_src_sizes'] : '',
		'width'                 => isset( $image['src_w'] ) ? absint( $image['src_w'] ) : 0,
		'height'                => isset( $image['src_h'] ) ? absint( $image['src_h'] ) : 0,
		'is_main'               => true,
	);
}

/**
 * Frontend config for single product gallery (window.tailwindscoreProduct).
 *
 * @param int|null $product_id Product ID.
 * @return array<string, mixed>
 */
function tailwindscore_product_gallery_config( ?int $product_id = null ): array {
	$product_id = $product_id ?? get_queried_object_id();
	$product    = wc_get_product( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$parent = tailwindscore_gallery_parent_images( $product );

	return array(
		'productId'   => $product->get_id(),
		'isVariable'  => $product->is_type( 'variable' ),
		'parent'      => $parent,
		'layout'    => array(
			'desktopThumbs' => 'left',
			'mobileThumbs'  => false,
		),
		'showCounter' => (bool) tailwindscore_mod( 'product_gallery_show_counter', true ),
		'thumbLimit'  => max( 3, min( 12, (int) tailwindscore_mod( 'product_gallery_thumb_limit', 6 ) ) ),
		'i18n'        => array(
			'prev'    => __( 'Previous image', 'tailwindscore' ),
			'next'    => __( 'Next image', 'tailwindscore' ),
			'counter' => __( '%1$d / %2$d', 'tailwindscore' ),
		),
	);
}

/**
 * Render one Swiper slide (SSR + no-JS fallback).
 *
 * @param array<string, mixed> $img   Image item.
 * @param int                  $index Slide index.
 * @param bool                 $eager     First slide LCP.
 * @param bool                 $for_thumb Thumbnail strip slide.
 * @return string
 */
function tailwindscore_gallery_render_slide_html( array $img, int $index, bool $eager = false, bool $for_thumb = false ): string {
	if ( $for_thumb ) {
		$src    = ! empty( $img['thumbnail'] ) ? (string) $img['thumbnail'] : (string) ( $img['src'] ?? '' );
		$srcset = ! empty( $img['thumb_srcset'] ) ? (string) $img['thumb_srcset'] : '';
		$sizes  = ! empty( $img['thumb_sizes'] ) ? (string) $img['thumb_sizes'] : '';
	} else {
		$src    = ! empty( $img['gallery'] ) ? (string) $img['gallery'] : (string) ( $img['src'] ?? '' );
		$srcset = ! empty( $img['gallery_srcset'] ) ? (string) $img['gallery_srcset'] : (string) ( $img['srcset'] ?? '' );
		$sizes  = ! empty( $img['gallery_sizes'] ) ? (string) $img['gallery_sizes'] : (string) ( $img['sizes'] ?? '' );
	}
	$alt    = isset( $img['alt'] ) ? (string) $img['alt'] : '';

	if ( '' === $src ) {
		return '';
	}

	$loading = $eager ? 'eager' : 'lazy';
	$fetch   = $eager ? ' fetchpriority="high"' : '';

	$html = '<div class="swiper-slide" data-slide-index="' . esc_attr( (string) $index ) . '">';
	$html .= '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading ) . '" decoding="async"' . $fetch;
	if ( $srcset ) {
		$html .= ' srcset="' . esc_attr( $srcset ) . '"';
	}
	if ( $sizes ) {
		$html .= ' sizes="' . esc_attr( $sizes ) . '"';
	}
	$html .= ' />';
	$html .= '</div>';

	return $html;
}
