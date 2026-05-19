<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );
?>
<header class="ts-stack" style="margin-bottom: 2rem;">
	<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
		<h1 class="ts-heading"><?php woocommerce_page_title(); ?></h1>
	<?php endif; ?>
	<?php do_action( 'woocommerce_archive_description' ); ?>
</header>

<?php if ( woocommerce_product_loop() ) : ?>
	<?php do_action( 'woocommerce_before_shop_loop' ); ?>
	<ul class="ts-grid" style="grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php wc_get_template_part( 'content', 'product' ); ?>
		<?php endwhile; ?>
	</ul>
	<?php do_action( 'woocommerce_after_shop_loop' ); ?>
<?php else : ?>
	<?php do_action( 'woocommerce_no_products_found' ); ?>
<?php endif; ?>
<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );

