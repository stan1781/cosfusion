<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'ts-card ts-stack', $product ); ?>>
	<a href="<?php the_permalink(); ?>" class="ts-stack">
		<?php echo woocommerce_get_product_thumbnail(); ?>
		<h2><?php the_title(); ?></h2>
		<span><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
	</a>
	<div>
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</div>
</li>

