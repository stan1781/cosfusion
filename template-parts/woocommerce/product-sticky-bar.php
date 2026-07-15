<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$ts_is_variable   = $product->is_type( 'variable' );
$ts_thumb_html    = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'product-sticky-bar__img' ) );
$ts_price_html    = $product->get_price_html();
$ts_attrs_label   = $ts_is_variable
	? __( 'Select options', 'tailwindscore' )
	: '';
$ts_button_label  = $product->is_purchasable() && $product->is_in_stock()
	? esc_html( $product->single_add_to_cart_text() )
	: esc_html__( 'Read more', 'tailwindscore' );
?>
<div
	class="product-sticky-bar"
	role="region"
	aria-label="<?php esc_attr_e( 'Quick purchase', 'tailwindscore' ); ?>"
	data-ts-product-sticky-bar
>
	<div class="product-sticky-bar__inner">
		<div class="product-sticky-bar__thumb" data-ts-product-sticky-thumb>
			<?php echo wp_kses_post( $ts_thumb_html ); ?>
		</div>
		<div class="product-sticky-bar__meta" aria-live="polite">
			<?php if ( $ts_is_variable ) : ?>
				<p class="product-sticky-bar__attrs" data-ts-product-sticky-attrs><?php echo esc_html( $ts_attrs_label ); ?></p>
			<?php endif; ?>
			<div class="product-sticky-bar__price" data-ts-product-sticky-price><?php echo wp_kses_post( $ts_price_html ); ?></div>
		</div>
		<div class="product-sticky-bar__atc-wrapper">
			<button
				type="button"
				class="button single_add_to_cart_button product-sticky-bar__atc"
				data-ts-product-sticky-atc
			>
				<?php echo esc_html( $ts_button_label ); ?>
			</button>
		</div>
	</div>
</div>
