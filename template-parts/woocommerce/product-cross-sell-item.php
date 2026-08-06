<?php
declare(strict_types=1);

/**
 * 单品 Cross-sells 列表行。
 *
 * @package TailwindScore
 *
 * @var array<string, mixed> $args {
 *     @type WC_Product $product 商品。
 * }
 */

defined( 'ABSPATH' ) || exit;

$product = isset( $args['product'] ) && $args['product'] instanceof WC_Product
	? $args['product']
	: null;

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_id = (int) $product->get_id();
$permalink  = $product->get_permalink();
$title      = $product->get_name();
$view_label = sprintf(
	/* translators: %s: product title */
	__( 'View product: %s', 'tailwindscore' ),
	wp_strip_all_tags( $title )
);
?>
<li
	class="product-cross-sells__item"
	data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
>
	<a
		class="product-cross-sells__media"
		href="<?php echo esc_url( $permalink ); ?>"
		aria-label="<?php echo esc_attr( $view_label ); ?>"
	>
		<?php echo $product->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'product-cross-sells__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC product image HTML. ?>
	</a>

	<div class="product-cross-sells__body">
		<a class="product-cross-sells__name" href="<?php echo esc_url( $permalink ); ?>">
			<?php echo esc_html( $title ); ?>
		</a>
		<span class="product-cross-sells__price">
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</span>
	</div>

	<div class="product-cross-sells__actions" data-product-card-actions>
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</div>
</li>
