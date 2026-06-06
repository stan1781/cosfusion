<?php
/**
 * Quick View modal product content.
 *
 * @package TailwindScore
 * @version 0.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( isset( $args['product'] ) && $args['product'] instanceof WC_Product ) {
	$product = $args['product'];
}

if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
	return;
}
?>

<div <?php wc_product_class( 'ts-quick-view-product', $product ); ?>>
	<div class="ts-quick-view-product__grid">
		<div class="ts-quick-view-product__media">
			<?php
			// 获取第二张图ID（复用loop-image.php的逻辑）
			$featured_id = (int) $product->get_image_id();
			$gallery_ids = array_values(
				array_filter(
					array_map( 'absint', (array) $product->get_gallery_image_ids() )
				)
			);
			$secondary_id = 0;

			foreach ( $gallery_ids as $gallery_id ) {
				if ( $gallery_id > 0 && $gallery_id !== $featured_id ) {
					$secondary_id = $gallery_id;
					break;
				}
			}

			$media_classes = 'ts-quick-view-product__media-inner';
			if ( $secondary_id > 0 ) {
				$media_classes .= ' has-secondary-image';
			}

			echo '<div class="' . esc_attr( $media_classes ) . '">';

			// 主图
			echo $product->get_image(
				'woocommerce_thumbnail',
				array(
					'class'   => 'ts-quick-view-product__image ts-quick-view-product__image--primary',
					'loading' => 'eager',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// 第二张图
			if ( $secondary_id > 0 ) {
				echo wp_get_attachment_image(
					$secondary_id,
					'woocommerce_thumbnail',
					false,
					array(
						'class'   => 'ts-quick-view-product__image ts-quick-view-product__image--secondary',
						'loading' => 'lazy',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</div>';
			?>
		</div>

		<div class="ts-quick-view-product__summary">
			<h3 class="ts-quick-view-product__title"><?php echo esc_html( $product->get_name() ); ?></h3>

			<div class="ts-quick-view-product__price price">
				<?php echo wp_kses_post( $product->get_price_html() ); ?>
			</div>

			<div class="ts-quick-view-product__cart">
				<?php
				if ( $product->is_type( 'variable' ) ) {
					// Compute available variations once; passed into variable-quick-view.php.
					$qv_attributes           = $product->get_variation_attributes();
					$qv_available_variations = $product->get_available_variations();
					wc_get_template(
						'single-product/add-to-cart/variable-quick-view.php',
						array(
							'attributes'                    => $qv_attributes,
							'available_variations'          => $qv_available_variations,
							'selected_variation'            => false,
							'selected_variation_attributes' => $product->get_default_attributes(),
						)
					);
				} else {
					woocommerce_template_single_add_to_cart();
				}
				?>
			</div>

			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="ts-button ts-button--secondary ts-quick-view-product__view-details">
				<?php esc_html_e( 'View Product', 'tailwindscore' ); ?>
			</a>
		</div>
	</div>
</div>
