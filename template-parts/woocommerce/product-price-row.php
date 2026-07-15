<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$sku      = $product->get_sku();
$show_sku = wc_product_sku_enabled() && is_string( $sku ) && '' !== $sku;
?>
<div class="wc-product-detail__price-row">	
	<p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>">
		<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>
	<?php if ( $show_sku ) : ?>
		<span class="wc-product-detail__sku">
			<?php esc_html_e( 'SKU:', 'woocommerce' ); ?>
			<?php echo esc_html( $sku ); ?>
		</span>
	<?php endif; ?>
</div>
