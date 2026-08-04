<?php
declare(strict_types=1);

/**
 * 归档商品循环 + 分页。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$archive_context = function_exists( 'tailwindscore_archive_get_context' ) ? tailwindscore_archive_get_context() : array();
$has_mobile_filters = ! empty( $archive_context['sidebar_groups'] ) && is_array( $archive_context['sidebar_groups'] );
?>
<?php if ( woocommerce_product_loop() ) : ?>
	<div class="wc-archive__toolbar">
		<?php get_template_part( 'template-parts/woocommerce/archive/filters-mobile-toggle' ); ?>
		<?php do_action( 'woocommerce_before_shop_loop' ); ?>
	</div>
	<ul class="wc-product-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			wc_get_template_part( 'content', 'product' );
		endwhile;
		?>
	</ul>
	<div class="wc-archive__pagination">
		<?php do_action( 'woocommerce_after_shop_loop' ); ?>
	</div>
<?php else : ?>
	<?php if ( $has_mobile_filters ) : ?>
		<div class="wc-archive__toolbar">
			<?php get_template_part( 'template-parts/woocommerce/archive/filters-mobile-toggle' ); ?>
		</div>
	<?php endif; ?>
	<?php do_action( 'woocommerce_no_products_found' ); ?>
<?php endif; ?>
