<?php
declare(strict_types=1);

/**
 * The template for displaying product content within loops
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}

$permalink_label = sprintf(
	/* translators: %s: product title */
	__( 'View product: %s', 'tailwindscore' ),
	wp_strip_all_tags( get_the_title() )
);
?>
<li <?php wc_product_class( 'ts-wc-product-card', $product ); ?> data-product-card data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
	<div class="ts-wc-product-card__media-wrap">
		<a href="<?php the_permalink(); ?>" class="ts-wc-product-card__media-link" aria-label="<?php echo esc_attr( $permalink_label ); ?>">
			<?php tailwindscore_render_loop_product_images( $product ); ?>
		</a>
		<div class="ts-wc-product-card__actions" data-product-card-actions>
			<?php woocommerce_template_loop_add_to_cart(); ?>
		</div>
	</div>
	<?php
	$ts_title_tag = apply_filters( 'woocommerce_loop_product_title_tag', 'h2' );
	$ts_title_tag = in_array( $ts_title_tag, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $ts_title_tag : 'h2';
	?>
	<a href="<?php the_permalink(); ?>" class="ts-wc-product-card__link">
		<?php echo '<' . esc_attr( $ts_title_tag ) . ' class="ts-wc-product-card__title">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tag name validated above. ?>
		<?php the_title(); ?>
		<?php echo '</' . esc_attr( $ts_title_tag ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tag name validated above. ?>
		<div class="ts-wc-product-card__meta">
			<span class="ts-wc-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			<?php tailwindscore_render_loop_rating( $product ); ?>
		</div>
	</a>
	<div class="ts-wc-product-card__footer">
		<?php tailwindscore_render_loop_variant_swatches( $product ); ?>
	</div>
</li>
