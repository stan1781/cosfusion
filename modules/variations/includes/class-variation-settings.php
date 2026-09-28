<?php
declare(strict_types=1);

/**
 * 变体选择器设置与属性辅助函数。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 变体选择器配置与规则。
 */
class Tailwindscore_Variation_Settings {

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$value = get_theme_mod( 'variation_selector_enabled', true );
		if ( null === $value || '' === $value ) {
			return true;
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @return string[]
	 */
	public static function get_color_attributes(): array {
		$raw = (string) tailwindscore_mod( 'variation_color_attributes', 'pa_color,color,pa_style,style' );
		return array_map( 'trim', explode( ',', $raw ) );
	}

	/**
	 * @return string[]
	 */
	public static function get_size_attributes(): array {
		$raw = (string) tailwindscore_mod( 'variation_size_attributes', 'pa_size,size' );
		return array_map( 'trim', explode( ',', $raw ) );
	}

	/**
	 * @param string[] $values 列表。
	 * @return string[]
	 */
	private static function normalize_lowercase_list( array $values ): array {
		return array_map(
			static function ( string $v ): string {
				return strtolower( trim( $v ) );
			},
			$values
		);
	}

	/**
	 * @param string   $attribute_name 属性键。
	 * @param int|null $product_id     商品 ID。
	 * @return bool
	 */
	public static function is_color_attribute( string $attribute_name, ?int $product_id = null ): bool {
		$attribute_name_lower = strtolower( $attribute_name );

		if ( $product_id ) {
			$disabled = self::normalize_lowercase_list( self::get_product_disabled_image_attributes( $product_id ) );
			if ( in_array( $attribute_name_lower, $disabled, true ) ) {
				return false;
			}
		}

		$color_attributes = self::normalize_lowercase_list( self::get_color_attributes() );
		$is_color         = in_array( $attribute_name_lower, $color_attributes, true );

		/**
		 * 过滤某属性是否按颜色/图像色块展示。
		 *
		 * @param bool     $is_color       是否颜色属性。
		 * @param string   $attribute_name 属性键。
		 * @param int|null $product_id     商品 ID。
		 */
		return (bool) apply_filters( 'tailwindscore/variations/is_color_attribute', $is_color, $attribute_name, $product_id );
	}

	/**
	 * @param string $attribute_name 属性键。
	 * @return bool
	 */
	public static function is_size_attribute( string $attribute_name ): bool {
		$size_attributes = self::normalize_lowercase_list( self::get_size_attributes() );
		return in_array( strtolower( $attribute_name ), $size_attributes, true );
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return string[]
	 */
	public static function get_product_disabled_image_attributes( int $product_id ): array {
		$disabled = get_post_meta( $product_id, '_wc_variation_selector_disabled_image_attributes', true );
		if ( empty( $disabled ) || ! is_array( $disabled ) ) {
			return array();
		}
		return array_map( 'trim', $disabled );
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return string ''|'grid'|'list'
	 */
	public static function get_product_color_selector_default_mode_raw( int $product_id ): string {
		$raw = get_post_meta( $product_id, '_wc_variation_selector_color_default_mode', true );
		if ( 'grid' === $raw || 'list' === $raw ) {
			return $raw;
		}
		return '';
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return string 'grid'|'list'
	 */
	public static function get_effective_color_selector_default_mode( int $product_id ): string {
		$global = (string) tailwindscore_mod( 'variation_color_default_mode', 'grid' );
		if ( ! in_array( $global, array( 'grid', 'list' ), true ) ) {
			$global = 'grid';
		}
		$override = self::get_product_color_selector_default_mode_raw( $product_id );
		if ( 'grid' === $override || 'list' === $override ) {
			return $override;
		}
		return $global;
	}

	/**
	 * @return string 'square'|'round'
	 */
	public static function get_color_selector_shape(): string {
		$shape = (string) tailwindscore_mod( 'variation_color_shape', 'square' );
		return in_array( $shape, array( 'square', 'round' ), true ) ? $shape : 'square';
	}

	/**
	 * @return bool
	 */
	public static function grid_show_text(): bool {
		return (bool) tailwindscore_mod( 'variation_grid_show_text', false );
	}

	/**
	 * window.tailwindscoreProduct.variations 的前台配置。
	 *
	 * @param int|null $product_id 商品 ID。
	 * @return array<string, mixed>
	 */
	public static function get_frontend_config( ?int $product_id = null ): array {
		$product_id = $product_id ?? 0;
		if ( $product_id < 1 && function_exists( 'wc_get_product' ) ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				$product_id = $product->get_id();
			}
		}

		$global_mode = (string) tailwindscore_mod( 'variation_color_default_mode', 'grid' );
		if ( ! in_array( $global_mode, array( 'grid', 'list' ), true ) ) {
			$global_mode = 'grid';
		}

		return array(
			'enabled'                   => self::is_enabled(),
			'colorAttributes'           => self::get_color_attributes(),
			'sizeAttributes'            => self::get_size_attributes(),
			'colorSelectorDefaultMode'  => $product_id > 0 ? self::get_effective_color_selector_default_mode( $product_id ) : $global_mode,
			'colorSelectorShape'        => self::get_color_selector_shape(),
			'gridShowText'              => self::grid_show_text(),
			'priceSelector'             => '.wc-product-detail__summary .price, .entry-summary .price',
		);
	}
}

/**
 * @return bool
 */
function tailwindscore_variations_enabled(): bool {
	return Tailwindscore_Variation_Settings::is_enabled();
}

/**
 * @param string   $attribute_name Attribute.
 * @param int|null $product_id     商品 ID。
 * @return bool
 */
	function tailwindscore_is_color_variation_attribute( string $attribute_name, ?int $product_id = null ): bool {
		return Tailwindscore_Variation_Settings::is_color_attribute( $attribute_name, $product_id );
	}

	/**
	 * 是否视觉隐藏该变体属性行（select 仍保留）。
	 *
	 * @param string   $attribute_name 属性键。
	 * @param int|null $product_id     商品 ID。
	 */
	function tailwindscore_should_hide_variation_attribute_row( string $attribute_name, ?int $product_id = null ): bool {
		$product_id = (int) ( $product_id ?? 0 );

		/**
		 * @param bool   $hide           是否隐藏。
		 * @param string $attribute_name 属性键。
		 * @param int    $product_id     商品 ID。
		 */
		return (bool) apply_filters( 'tailwindscore/variations/hide_attribute_row', false, $attribute_name, $product_id );
	}

	/**
	 * @param string $attribute_name 属性。
	 * @return bool
	 */
	function tailwindscore_is_size_variation_attribute( string $attribute_name ): bool {
	return Tailwindscore_Variation_Settings::is_size_attribute( $attribute_name );
}

/**
 * @param int|null $product_id 商品 ID。
 * @return array<string, mixed>
 */
function tailwindscore_variations_config( ?int $product_id = null ): array {
	return array(
		'variations' => Tailwindscore_Variation_Settings::get_frontend_config( $product_id ),
		'i18n'       => array(
			'list'            => __( 'List', 'tailwindscore' ),
			'grid'            => __( 'Grid', 'tailwindscore' ),
			'switchToList'    => __( 'Switch to list mode', 'tailwindscore' ),
			'switchToGrid'    => __( 'Switch to grid mode', 'tailwindscore' ),
			'selectAttribute' => __( 'Select %s', 'tailwindscore' ),
		),
	);
}

/**
 * 合并单品页的 gallery + variations payload。
 *
 * @param int|null $product_id 商品 ID。
 * @return array<string, mixed>
 */
function tailwindscore_product_page_config( ?int $product_id = null ): array {
	$config = array();

	if ( function_exists( 'tailwindscore_product_gallery_config' ) ) {
		$config = tailwindscore_product_gallery_config( $product_id );
	}

	$variations = tailwindscore_variations_config( $product_id );
	$config['variations'] = $variations['variations'];
	$config['variationsI18n'] = $variations['i18n'];

	if ( function_exists( 'tailwindscore_review_images_config' ) ) {
		$review = tailwindscore_review_images_config();
		$config['reviewImages'] = $review['reviewImages'];
		$config['reviewImagesI18n'] = $review['i18n'];
	}

	$product_id = (int) ( $product_id ?? 0 );
	if ( $product_id < 1 && function_exists( 'is_product' ) && is_product() ) {
		$product_id = (int) get_queried_object_id();
	}
	if ( $product_id < 1 && function_exists( 'wc_get_product' ) ) {
		global $product;
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
		}
	}

	return apply_filters( 'tailwindscore_product_page_config', $config, $product_id );
}
