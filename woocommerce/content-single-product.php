<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'ts-wc-product-detail', wc_get_product() ); ?>>
	<div class="ts-wc-product-detail__media ts-stack">
		<?php get_template_part( 'template-parts/woocommerce/product-gallery' ); ?>
	</div>
	<div class="ts-wc-product-detail__summary ts-stack">
		<?php do_action( 'woocommerce_single_product_summary' ); ?>
	</div>
</div>
<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
