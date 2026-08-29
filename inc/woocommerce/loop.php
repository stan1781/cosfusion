<?php
declare(strict_types=1);

/**
 * 归档商品卡片：徽章、评分、主副图、简单商品圆形加购图标。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 商品列表卡片的促销徽章（具体折扣 % / 金额回退）。
 */
function tailwindscore_render_loop_sale_badge( WC_Product $product ): void {
	$flash = tailwindscore_get_sale_badge_html( $product );

	if ( '' === $flash ) {
		return;
	}

	echo '<div class="wc-product-card__badges">';
	echo $flash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已在 helper 中转义。
	echo '</div>';
}

/**
 * 列表评分用实心星 SVG（使用 currentColor）。
 */
function tailwindscore_loop_rating_star_svg(): string {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="5.8 2 12.4 19" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.5l2.89 5.86 6.46.94-4.68 4.56 1.1 6.43L12 17.77l-5.77 3.02 1.1-6.43-4.68-4.56 6.46-.94L12 2.5z"/></svg>';
}

/**
 * 1–5 索引的星标状态：full、half 或 empty。
 *
 * @return 'full'|'half'|'empty'
 */
function tailwindscore_loop_rating_star_state( float $rating, int $index ): string {
	if ( $rating >= (float) $index ) {
		return 'full';
	}

	if ( $rating >= ( (float) $index - 0.5 ) ) {
		return 'half';
	}

	return 'empty';
}

/**
 * 为归档商品卡片渲染五星评分。
 */
function tailwindscore_render_loop_rating( WC_Product $product ): void {
	if ( ! wc_review_ratings_enabled() ) {
		return;
	}

	$rating = (float) $product->get_average_rating();

	if ( $rating <= 0 ) {
		return;
	}

	$label = sprintf(
		/* translators: %s: rating value */
		__( 'Rated %s out of 5', 'woocommerce' ),
		$rating
	);

	echo '<div class="wc-product-card__rating" role="img" aria-label="' . esc_attr( $label ) . '">';

	$star_svg = tailwindscore_loop_rating_star_svg();

	for ( $index = 1; $index <= 5; $index++ ) {
		$state = tailwindscore_loop_rating_star_state( $rating, $index );

		if ( 'half' === $state ) {
			echo '<span class="wc-product-card__star wc-product-card__star--half">';
			echo '<span class="wc-product-card__star-layer wc-product-card__star-layer--empty"><span class="wc-product-card__star-icon">' . $star_svg . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="wc-product-card__star-layer wc-product-card__star-layer--filled"><span class="wc-product-card__star-icon">' . $star_svg . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</span>';
			continue;
		}

		$class = 'empty' === $state ? 'wc-product-card__star--empty' : 'wc-product-card__star--full';

		echo '<span class="wc-product-card__star ' . esc_attr( $class ) . '">';
		echo '<span class="wc-product-card__star-icon">' . $star_svg . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</span>';
	}

	echo '</div>';
}

/**
 * 商品列表缩略图，悬停时切换至首张图库图片。
 */
function tailwindscore_loop_product_secondary_image_id( WC_Product $product ): int {
	$featured_id = (int) $product->get_image_id();
	$gallery_ids = array_values(
		array_filter(
			array_map( 'absint', (array) $product->get_gallery_image_ids() )
		)
	);

	foreach ( $gallery_ids as $gallery_id ) {
		if ( $gallery_id > 0 && $gallery_id !== $featured_id ) {
			return $gallery_id;
		}
	}

	return 0;
}

/**
 * 循环主图 loading：默认 lazy；Shop/分类主查询且无归档 Hero 图时前 4 张 eager。
 */
function tailwindscore_loop_primary_image_loading(): string {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_taxonomy() ) ) {
		return 'lazy';
	}

	if ( ! in_the_loop() || ! is_main_query() ) {
		return 'lazy';
	}

	if ( function_exists( 'wc_get_loop_prop' ) && wc_get_loop_prop( 'is_shortcode' ) ) {
		return 'lazy';
	}

	// 归档 Hero 已是 LCP，卡片一律 lazy，避免抢 fetchpriority。
	if ( function_exists( 'tailwindscore_wc_archive_has_hero_image' ) && tailwindscore_wc_archive_has_hero_image() ) {
		return 'lazy';
	}

	global $wp_query;
	$index = isset( $wp_query->current_post ) ? (int) $wp_query->current_post : -1;

	return $index >= 0 && $index < 4 ? 'eager' : 'lazy';
}

/**
 * 为归档卡片渲染主图 + 副图。
 */
function tailwindscore_render_loop_product_images( WC_Product $product ): void {
	$secondary_id = tailwindscore_loop_product_secondary_image_id( $product );

	$media_classes = 'wc-product-card__media';
	if ( $secondary_id > 0 ) {
		$media_classes .= ' has-secondary-image';
	}

	echo '<div class="' . esc_attr( $media_classes ) . '" data-product-card-media>';

	tailwindscore_render_loop_sale_badge( $product );

	$primary_html = $product->get_image(
		'woocommerce_thumbnail',
		array(
			'class'    => 'wc-product-card__image wc-product-card__image--primary',
			'loading'  => tailwindscore_loop_primary_image_loading(),
			'decoding' => 'async',
		)
	);

	echo $primary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( $secondary_id > 0 ) {
		$secondary_html = wp_get_attachment_image(
			$secondary_id,
			'woocommerce_thumbnail',
			false,
			array(
				'class'    => 'wc-product-card__image wc-product-card__image--secondary',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		echo $secondary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}

/**
 * 归档卡片：简单商品加购改为圆形购物车图标（与 QV / Wishlist 一致）。
 *
 * @param string               $html    加购链接 HTML。
 * @param WC_Product|mixed     $product 商品。
 * @param array<string, mixed> $args    循环参数。
 * @return string
 */
function tailwindscore_filter_loop_add_to_cart_icon( string $html, $product, array $args ): string {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
		return $html;
	}

	if ( ! $product->is_purchasable() || ! $product->is_in_stock() || ! $product->supports( 'ajax_add_to_cart' ) ) {
		return $html;
	}

	$quantity = isset( $args['quantity'] ) ? $args['quantity'] : 1;
	$label    = $product->add_to_cart_description();
	$icon     = function_exists( 'tailwindscore_icon' ) ? tailwindscore_icon( 'cart' ) : '';

	// 无 ?add-to-cart= URL：仅 Store API（loop-add-to-cart.ts）；避免 bot 扫描 GET 加购。
	return sprintf(
		'<button type="button" data-quantity="%1$s" class="%2$s" data-product_id="%3$s" data-product_sku="%4$s" aria-label="%5$s"><span class="icon" aria-hidden="true">%6$s</span></button>',
		esc_attr( (string) $quantity ),
		esc_attr( 'btn-icon product-card-action product-card-action--add-to-cart button product_type_simple add_to_cart_button ajax_add_to_cart' ),
		esc_attr( (string) $product->get_id() ),
		esc_attr( $product->get_sku() ),
		esc_attr( $label ),
		$icon
	);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'tailwindscore_filter_loop_add_to_cart_icon', 15, 3 );
