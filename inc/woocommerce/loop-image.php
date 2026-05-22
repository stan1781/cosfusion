<?php
declare(strict_types=1);

/**
 * Product loop thumbnail with hover swap to first gallery image.
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
 * Render primary + secondary loop images for archive cards.
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
