<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$sku         = $product->get_sku();
$show_sku    = current_user_can( 'manage_options' )
	&& wc_product_sku_enabled()
	&& is_string( $sku )
	&& '' !== $sku;
$sale_badge  = function_exists( 'tailwindscore_get_sale_badge_html' )
	? tailwindscore_get_sale_badge_html( $product, 'wc-product-detail__sale-badge' )
	: '';
?>
<div class="wc-product-detail__price-row">
	<div class="wc-product-detail__price-main">
		<p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>">
			<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>
		<?php
		if ( '' !== $sale_badge ) {
			echo $sale_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper 已转义。
		}
		?>
	</div>
	<?php if ( $show_sku ) : ?>
		<span class="wc-product-detail__sku">
			<?php esc_html_e( 'SKU:', 'woocommerce' ); ?>
			<?php echo esc_html( $sku ); ?>
		</span>
	<?php endif; ?>
</div>
