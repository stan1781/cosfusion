<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<section class="ts-wc-cart ts-stack">
	<form class="woocommerce-cart-form ts-wc-cart__form ts-stack" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>
		<?php wc_get_template( 'cart/cart-table.php' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>
	<div class="ts-wc-cart__collaterals">
		<?php do_action( 'woocommerce_cart_collaterals' ); ?>
	</div>
</section>
