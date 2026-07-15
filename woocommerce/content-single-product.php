<?php
declare(strict_types=1);

/**
 * 在 single-product.php 模板中展示商品内容的模板
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'wc-product-detail', wc_get_product() ); ?>>
	<div class="wc-product-detail__media stack">
		<?php get_template_part( 'template-parts/woocommerce/product-gallery' ); ?>
	</div>
	<div class="wc-product-detail__summary">
		<?php do_action( 'woocommerce_single_product_summary' ); ?>
	</div>
</div>
<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
