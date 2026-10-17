<?php
declare(strict_types=1);

/**
 * 变体相册：从 WooCommerce 官方 gallery 组装前台 variation_images。
 *
 * 后台编辑与存储由 WC 11.1+ 原生 Variation Gallery 负责（_product_image_gallery）。
 * 主题仅把 image_id + gallery_image_ids 转成 Swiper 用的 GalleryImage[]。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 官方变体相册 → 主题前台 payload。
 */
class Tailwindscore_Gallery_Variation_Images {

	/**
	 * @return void
	 */
	public function __construct() {
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_images_to_variation_data' ), 10, 3 );
	}

	/**
	 * 有附加相册时注入 variation_images（主图 + 附加图）。
	 *
	 * @param array                $variation_data 变体数据。
	 * @param WC_Product           $product        父商品。
	 * @param WC_Product_Variation $variation      变体商品。
	 * @return array
	 */
	public function add_variation_images_to_variation_data( array $variation_data, WC_Product $product, $variation ): array {
		unset( $product );

		if ( ! $variation instanceof WC_Product_Variation ) {
			return $variation_data;
		}

		$gallery_ids = $this->resolve_gallery_attachment_ids( $variation_data, $variation );
		if ( empty( $gallery_ids ) ) {
			return $variation_data;
		}

		$images_data = array();
		$seen        = array();

		$main_id = (int) $variation->get_image_id();
		if ( $main_id > 0 ) {
			$main = tailwindscore_gallery_build_image_item( $main_id, true );
			if ( $main ) {
				$images_data[]    = $main;
				$seen[ $main_id ] = true;
			}
		}

		foreach ( $gallery_ids as $image_id ) {
			$image_id = absint( $image_id );
			if ( $image_id < 1 || isset( $seen[ $image_id ] ) ) {
				continue;
			}
			$item = tailwindscore_gallery_build_image_item( $image_id, false );
			if ( $item ) {
				$images_data[]     = $item;
				$seen[ $image_id ] = true;
			}
		}

		if ( ! empty( $images_data ) ) {
			$variation_data['variation_images'] = $images_data;
		}

		return $variation_data;
	}

	/**
	 * 附加图 ID：payload / get_gallery_image_ids / 旧 _variation_images。
	 *
	 * @param array                $variation_data available_variation 行。
	 * @param WC_Product_Variation $variation      变体。
	 * @return list<int>
	 */
	private function resolve_gallery_attachment_ids( array $variation_data, WC_Product_Variation $variation ): array {
		if ( ! empty( $variation_data['gallery_image_ids'] ) && is_array( $variation_data['gallery_image_ids'] ) ) {
			$ids = array_values( array_filter( array_map( 'absint', $variation_data['gallery_image_ids'] ) ) );
			if ( ! empty( $ids ) ) {
				return $ids;
			}
		}

		if ( method_exists( $variation, 'get_gallery_image_ids' ) ) {
			$ids = $variation->get_gallery_image_ids();
			if ( is_array( $ids ) && ! empty( $ids ) ) {
				return array_values( array_filter( array_map( 'absint', $ids ) ) );
			}
		}

		$legacy = get_post_meta( $variation->get_id(), '_variation_images', true );
		if ( ! is_array( $legacy ) || empty( $legacy ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $legacy ) ) );
	}
}
