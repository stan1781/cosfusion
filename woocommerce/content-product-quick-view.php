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
			<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'ts-quick-view-product__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
		</div>
	</div>
</div>
