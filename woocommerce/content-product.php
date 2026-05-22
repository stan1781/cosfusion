<?php
declare(strict_types=1);

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
<li <?php wc_product_class( 'ts-card ts-wc-product-card', $product ); ?> data-product-card>
	<div class="ts-wc-product-card__media-wrap">
		<a href="<?php the_permalink(); ?>" class="ts-wc-product-card__media-link" aria-label="<?php echo esc_attr( $permalink_label ); ?>">
			<?php tailwindscore_render_loop_product_images( $product ); ?>
		</a>
		<div class="ts-wc-product-card__actions" data-product-card-actions>
			<?php woocommerce_template_loop_add_to_cart(); ?>
		</div>
	</div>
	<a href="<?php the_permalink(); ?>" class="ts-wc-product-card__link">
		<h2 class="ts-wc-product-card__title"><?php the_title(); ?></h2>
		<div class="ts-wc-product-card__meta">
			<span class="ts-wc-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			<?php tailwindscore_render_loop_rating( $product ); ?>
		</div>
	</a>
	<div class="ts-wc-product-card__footer">
		<?php tailwindscore_render_loop_variant_swatches( $product ); ?>
	</div>
</li>
