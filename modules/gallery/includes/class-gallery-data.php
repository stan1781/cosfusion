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
 * 判断属性键是否为颜色类（与 variations 模块配置对齐；无该类时用启发式）。
 *
 * @param string $attribute_name 属性名（如 pa_color / Color）。
 * @param int    $product_id     商品 ID。
 * @return bool
 */
function tailwindscore_gallery_is_color_attribute( string $attribute_name, int $product_id = 0 ): bool {
	if ( function_exists( 'tailwindscore_is_color_variation_attribute' ) ) {
		return tailwindscore_is_color_variation_attribute( $attribute_name, $product_id > 0 ? $product_id : null );
	}

	$key = strtolower( $attribute_name );
	$key = preg_replace( '/^attribute_/', '', $key ) ?? $key;
	$key = preg_replace( '/^pa_/', '', $key ) ?? $key;
	return in_array( $key, array( 'color', 'colour', 'style' ), true );
}

/**
 * 规范化变体属性映射键，便于与 default_attributes 比对。
 *
 * @param array<string, string> $attributes 变体 attributes。
 * @return array<string, string> 小写键 => 小写值。
 */
function tailwindscore_gallery_normalize_attr_map( array $attributes ): array {
	$out = array();
	foreach ( $attributes as $key => $value ) {
		$k = strtolower( (string) $key );
		$k = preg_replace( '/^attribute_/', '', $k ) ?? $k;
		$out[ $k ] = strtolower( (string) $value );
	}
	return $out;
}

/**
 * 解析可变商品的「默认」变体：优先 Woo default_attributes 全匹配；
 * 否则按颜色类属性的第一个选项匹配；再否则第一个有图的可见变体。
 *
 * @param WC_Product_Variable $product 可变商品。
 * @return WC_Product_Variation|null
 */
function tailwindscore_gallery_resolve_default_variation( WC_Product_Variable $product ): ?WC_Product_Variation {
	$children = $product->get_visible_children();
	if ( empty( $children ) ) {
		return null;
	}

	$defaults_raw = $product->get_default_attributes();
	$defaults     = is_array( $defaults_raw )
		? tailwindscore_gallery_normalize_attr_map( array_map( 'strval', $defaults_raw ) )
		: array();

	if ( ! empty( $defaults ) ) {
		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}
			$attrs = tailwindscore_gallery_normalize_attr_map( $variation->get_attributes() );
			$match = true;
			foreach ( $defaults as $key => $expected ) {
				if ( '' === $expected ) {
					continue;
				}
				$actual = $attrs[ $key ] ?? $attrs[ 'pa_' . $key ] ?? '';
				// Empty variation value = "any" for that attribute → matches default.
				if ( '' !== $actual && $actual !== $expected ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				return $variation;
			}
		}
	}

	// 颜色属性第一个选项。
	$product_id = $product->get_id();
	foreach ( $product->get_variation_attributes() as $attribute_name => $options ) {
		if ( ! is_array( $options ) || empty( $options ) ) {
			continue;
		}
		if ( ! tailwindscore_gallery_is_color_attribute( (string) $attribute_name, $product_id ) ) {
			continue;
		}
		$first_opt = strtolower( (string) reset( $options ) );
		$attr_key  = sanitize_title( (string) $attribute_name );
		$keys      = array(
			strtolower( (string) $attribute_name ),
			'pa_' . $attr_key,
			$attr_key,
			'attribute_' . $attr_key,
			'attribute_pa_' . $attr_key,
		);

		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}
			$attrs = tailwindscore_gallery_normalize_attr_map( $variation->get_attributes() );
			foreach ( $keys as $k ) {
				$k = strtolower( preg_replace( '/^attribute_/', '', $k ) ?? $k );
				$val = $attrs[ $k ] ?? '';
				if ( '' !== $val && $val === $first_opt ) {
					return $variation;
				}
			}
		}
		break;
	}

	foreach ( $children as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( $variation instanceof WC_Product_Variation && $variation->get_image_id() ) {
			return $variation;
		}
	}

	$first = wc_get_product( (int) $children[0] );
	return $first instanceof WC_Product_Variation ? $first : null;
}

/**
 * 变体主图 + `_variation_images`（与 available_variation / REST 一致）。
 *
 * @param WC_Product_Variation $variation 变体。
 * @return int[]
 */
function tailwindscore_gallery_variation_attachment_ids( WC_Product_Variation $variation ): array {
	$ids  = array();
	$seen = array();

	$main_id = (int) $variation->get_image_id();
	if ( $main_id > 0 ) {
		$ids[]            = $main_id;
		$seen[ $main_id ] = true;
	}

	$extra = get_post_meta( $variation->get_id(), '_variation_images', true );
	if ( is_array( $extra ) ) {
		foreach ( $extra as $attachment_id ) {
			$attachment_id = absint( $attachment_id );
			if ( ! $attachment_id || isset( $seen[ $attachment_id ] ) ) {
				continue;
			}
			$ids[]                      = $attachment_id;
			$seen[ $attachment_id ] = true;
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
 * 可变商品：初始画廊 = 默认色变体相册（主图 + `_variation_images`），不用父商品图库。
 * 简单商品：仍用特色图 + 图库。
 *
 * @param WC_Product $product 商品。
 * @return list<array<string, mixed>>
 */
function tailwindscore_gallery_parent_images( WC_Product $product ): array {
	$images   = array();
	$ids      = array();
	$is_first = true;

	if ( $product->is_type( 'variable' ) && $product instanceof WC_Product_Variable ) {
		$default_variation = tailwindscore_gallery_resolve_default_variation( $product );
		if ( $default_variation instanceof WC_Product_Variation ) {
			$ids = tailwindscore_gallery_variation_attachment_ids( $default_variation );
		}
	} else {
		$ids = tailwindscore_gallery_collect_attachment_ids( $product );
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
	$nolazy  = $eager ? ' data-no-lazy="1"' : '';

	$html = '<div class="swiper-slide" data-slide-index="' . esc_attr( (string) $index ) . '">';
	$html .= '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading ) . '" decoding="async"' . $fetch . $nolazy;
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

/**
 * 单品页：在 <head> 预加载画廊首张（可变商品为默认色变体相册首图）。
 * 不 preload 占位图。
 */
function tailwindscore_gallery_preload_lcp_in_head(): void {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = null;
	if ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) {
		$product = $GLOBALS['product'];
	}

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_queried_object_id() );
	}

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$images = tailwindscore_gallery_parent_images( $product );
	if ( empty( $images[0] ) || ! is_array( $images[0] ) ) {
		return;
	}

	$first = $images[0];
	$id    = isset( $first['id'] ) ? (int) $first['id'] : 0;
	$src   = ! empty( $first['gallery'] ) ? (string) $first['gallery'] : '';

	if ( $id < 1 || '' === $src ) {
		return;
	}

	$srcset = ! empty( $first['gallery_srcset'] ) ? (string) $first['gallery_srcset'] : $src;
	$sizes  = '(max-width: 63.999rem) 100vw, min(50vw, 36rem)';

	printf(
		'<link rel="preload" as="image" href="%1$s" imagesrcset="%2$s" imagesizes="%3$s" fetchpriority="high" />' . "\n",
		esc_url( $src ),
		esc_attr( $srcset ),
		esc_attr( $sizes )
	);
}
add_action( 'wp_head', 'tailwindscore_gallery_preload_lcp_in_head', 2 );
