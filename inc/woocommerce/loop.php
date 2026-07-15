<?php
declare(strict_types=1);

/**
 * 归档商品卡片：徽章、评分、主副图。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 商品列表卡片的促销徽章。
 */
function tailwindscore_render_loop_sale_badge( WC_Product $product ): void {
	if ( ! $product->is_on_sale() ) {
		return;
	}

	global $post;

	$post = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	if ( ! $post ) {
		return;
	}

	$flash = apply_filters(
		'woocommerce_sale_flash',
		'<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>',
		$post,
		$product
	);

	if ( '' === $flash ) {
		return;
	}

	echo '<div class="ts-wc-product-card__badges">';
	echo $flash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

	echo '<div class="ts-wc-product-card__rating" role="img" aria-label="' . esc_attr( $label ) . '">';

	$star_svg = tailwindscore_loop_rating_star_svg();

	for ( $index = 1; $index <= 5; $index++ ) {
		$state = tailwindscore_loop_rating_star_state( $rating, $index );

		if ( 'half' === $state ) {
			echo '<span class="ts-wc-product-card__star ts-wc-product-card__star--half">';
			echo '<span class="ts-wc-product-card__star-layer ts-wc-product-card__star-layer--empty"><span class="ts-wc-product-card__star-icon">' . $star_svg . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="ts-wc-product-card__star-layer ts-wc-product-card__star-layer--filled"><span class="ts-wc-product-card__star-icon">' . $star_svg . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</span>';
			continue;
		}

		$class = 'empty' === $state ? 'ts-wc-product-card__star--empty' : 'ts-wc-product-card__star--full';

		echo '<span class="ts-wc-product-card__star ' . esc_attr( $class ) . '">';
		echo '<span class="ts-wc-product-card__star-icon">' . $star_svg . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
 * 为归档卡片渲染主图 + 副图。
 */
function tailwindscore_render_loop_product_images( WC_Product $product ): void {
	$secondary_id = tailwindscore_loop_product_secondary_image_id( $product );

	$media_classes = 'ts-wc-product-card__media';
	if ( $secondary_id > 0 ) {
		$media_classes .= ' has-secondary-image';
	}

	echo '<div class="' . esc_attr( $media_classes ) . '" data-product-card-media>';

	tailwindscore_render_loop_sale_badge( $product );

	$primary_html = $product->get_image(
		'woocommerce_thumbnail',
		array(
			'class'   => 'ts-wc-product-card__image ts-wc-product-card__image--primary',
			'loading' => 'eager',
		)
	);

	echo $primary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( $secondary_id > 0 ) {
		$secondary_html = wp_get_attachment_image(
			$secondary_id,
			'woocommerce_thumbnail',
			false,
			array(
				'class'   => 'ts-wc-product-card__image ts-wc-product-card__image--secondary',
				'loading' => 'lazy',
			)
		);

		echo $secondary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}
