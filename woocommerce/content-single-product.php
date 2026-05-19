<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'ts-grid', wc_get_product() ); ?> style="grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr)); align-items: start;">
	<div class="ts-stack">
		<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
	</div>
	<div class="ts-stack">
		<?php do_action( 'woocommerce_single_product_summary' ); ?>
	</div>
</div>
<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

