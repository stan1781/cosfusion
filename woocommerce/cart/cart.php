<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<form class="woocommerce-cart-form ts-stack" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>
	<?php wc_get_template( 'cart/cart-table.php' ); ?>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>
<div style="margin-top: 2.5rem;">
	<?php do_action( 'woocommerce_cart_collaterals' ); ?>
</div>

