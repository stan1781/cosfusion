<?php
/**
 * Quick View 弹窗商品内容。
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

<div <?php wc_product_class( 'wc-product-detail quick-view-product', $product ); ?>>
	<?php
	if ( function_exists( 'tailwindscore_print_size_guide_config_script' ) ) {
		tailwindscore_print_size_guide_config_script( (int) $product->get_id() );
	}
	?>
	<div class="wc-product-detail__media">
		<?php tailwindscore_render_loop_product_images( $product ); ?>
	</div>

	<div class="wc-product-detail__summary">
		<h2 class="product_title"><?php echo esc_html( $product->get_name() ); ?></h2>

		<div class="price">
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</div>

		<div class="wc-product-detail__cart">
			<?php
			if ( $product->is_type( 'variable' ) ) {
				// 仅计算一次可用变体，并传入 variable-quick-view.php。
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

		<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="btn btn-secondary quick-view-product__view-details">
			<?php esc_html_e( 'View Product', 'tailwindscore' ); ?>
		</a>
	</div>
</div>
