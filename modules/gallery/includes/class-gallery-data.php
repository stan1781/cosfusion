<?php
declare(strict_types=1);

/**
 * 为前台构建父商品画廊 payload。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param int    $image_id 附件 ID。
 * @param bool   $is_main  主图幻灯片标记。
 * @param string $thumb_size 缩略图尺寸 slug。
 * @param string $gallery_size 单图/画廊幻灯片尺寸 slug。
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
 * 收集父商品画廊图片 ID（特色图优先，再画廊）。
 *
 * @param WC_Product $product 商品。
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
 * 父可变商品无特色图/画廊图时，使用可见变体图片。
 *
 * @param WC_Product_Variable $product 可变商品。
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
 * 无附件时的 WooCommerce 占位幻灯片。
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
 * @param WC_Product $product 商品。
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
	 * 本地化前过滤父画廊幻灯片。
	 *
	 * @param list<array<string, mixed>> $images  画廊项。
	 * @param WC_Product                 $product 商品。
	 */
	return apply_filters( 'tailwindscore/gallery/parent_images', $images, $product );
}

/**
 * 为画廊层规范化 WooCommerce variation.image。
 *
 * @param array<string, mixed> $variation 可用变体行。
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
 * 单品画廊前台配置（window.tailwindscoreProduct）。
 *
 * @param int|null $product_id 商品 ID。
 * @return array<string, mixed>
 */
function tailwindscore_product_gallery_config( ?int $product_id = null ): array {
	$product_id = $product_id ?? get_queried_object_id();
	$product    = wc_get_product( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$parent           = tailwindscore_gallery_parent_images( $product );
	$attribute_labels = array();

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_variation_attributes() as $attribute_name => $values ) {
			$attribute_labels[ sanitize_title( (string) $attribute_name ) ] = wc_attribute_label( (string) $attribute_name );
		}
	}

	return array(
		'productId'        => $product->get_id(),
		'productTitle'     => $product->get_name(),
		'isVariable'       => $product->is_type( 'variable' ),
		'attributeLabels'  => $attribute_labels,
		'parent'           => $parent,
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
 * 渲染一条 Swiper 幻灯片（SSR + 无 JS 降级）。
 *
 * @param array<string, mixed> $img   图片项。
 * @param int                  $index 幻灯片索引。
 * @param bool                 $eager     首图 LCP。
 * @param bool                 $for_thumb 缩略图条幻灯片。
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
