<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'ts-card ts-wc-product-card', $product ); ?>>
	<a href="<?php the_permalink(); ?>" class="ts-wc-product-card__link">
		<?php echo woocommerce_get_product_thumbnail(); ?>
		<h2 class="ts-wc-product-card__title"><?php the_title(); ?></h2>
		<span class="ts-wc-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
	</a>
	<div class="ts-wc-product-card__actions">
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</div>
</li>
