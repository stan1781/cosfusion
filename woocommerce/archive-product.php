<?php
declare(strict_types=1);

/**
 * 展示商品归档的模板，包括作为主文章类型归档的商店首页
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );
?>
<section class="ts-wc-archive ts-stack">
	<div class="ts-wc-archive__notices">
		<?php woocommerce_output_all_notices(); ?>
	</div>

	<?php if ( woocommerce_product_loop() ) : ?>
		<div class="ts-wc-archive__toolbar">
			<?php do_action( 'woocommerce_before_shop_loop' ); ?>
		</div>
		<ul class="ts-wc-product-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php wc_get_template_part( 'content', 'product' ); ?>
			<?php endwhile; ?>
		</ul>
		<div class="ts-wc-archive__pagination">
			<?php do_action( 'woocommerce_after_shop_loop' ); ?>
		</div>
	<?php else : ?>
		<?php do_action( 'woocommerce_no_products_found' ); ?>
	<?php endif; ?>
</section>
<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
