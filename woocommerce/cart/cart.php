<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// The cart page is a fallback summary/edit surface, not the primary cart workflow.

do_action( 'woocommerce_before_cart' );

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$cart = WC()->cart;
?>
<section class="ts-wc-cart ts-stack">
	<header class="ts-wc-cart__header ts-stack">
		<h1 class="ts-wc-cart__title"><?php esc_html_e( 'Cart', 'tailwindscore' ); ?></h1>
		<p class="ts-wc-cart__summary"><?php echo esc_html( tailwindscore_cart_summary_text() ); ?></p>
	</header>

	<?php tailwindscore_render_cart_feedback( array( 'surface' => 'page' ) ); ?>

	<?php if ( $cart->is_empty() ) : ?>
		<div class="ts-cart-drawer-cart__empty ts-stack">
			<p><?php esc_html_e( 'Your cart is empty.', 'tailwindscore' ); ?></p>
			<a class="ts-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'tailwindscore' ); ?></a>
		</div>
	<?php else : ?>
		<form class="woocommerce-cart-form ts-wc-cart__form ts-stack" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
			<?php do_action( 'woocommerce_before_cart_table' ); ?>
			<ul class="ts-cart-drawer-cart__items ts-wc-cart__items">
				<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
					<?php
					tailwindscore_render_cart_line_item(
						array(
							'cart_item_key' => $cart_item_key,
							'cart_item'     => $cart_item,
							'context'       => 'cart',
						)
					);
					?>
				<?php endforeach; ?>
			</ul>
			<?php do_action( 'woocommerce_after_cart_table' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			<button type="submit" class="screen-reader-text" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Update cart', 'woocommerce' ); ?>
			</button>
		</form>
		<?php tailwindscore_render_cart_summary( array( 'context' => 'cart' ) ); ?>
	<?php endif; ?>
</section>
<?php
do_action( 'woocommerce_after_cart' );
