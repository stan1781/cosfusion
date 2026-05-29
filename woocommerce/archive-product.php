<?php
declare(strict_types=1);

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
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
	<header class="ts-wc-archive__header ts-stack">
		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
			<h1 class="ts-heading"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>
		<?php do_action( 'woocommerce_archive_description' ); ?>
	</header>

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
