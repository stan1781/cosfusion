<?php
declare(strict_types=1);

/**
 * The template for displaying product content in the single-product.php template
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'ts-wc-product-detail', wc_get_product() ); ?>>
	<div class="ts-wc-product-detail__media ts-stack">
		<?php get_template_part( 'template-parts/woocommerce/product-gallery' ); ?>
	</div>
	<div class="ts-wc-product-detail__summary">
		<?php do_action( 'woocommerce_single_product_summary' ); ?>
	</div>
</div>
<div class="ts-product-sticky-sentinel" aria-hidden="true"></div>
<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
