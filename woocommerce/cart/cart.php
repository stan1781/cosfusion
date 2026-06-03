<?php
declare(strict_types=1);

/**
 * Cart Page
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

// The cart page is a fallback summary/edit surface, not the primary cart workflow.

do_action( 'woocommerce_before_cart' );

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}
?>
<section class="ts-wc-cart ts-stack" data-ts-cart-page>
	<header class="ts-wc-cart__header ts-stack">
		<h1 class="ts-wc-cart__title"><?php esc_html_e( 'Cart', 'tailwindscore' ); ?></h1>
		<p class="ts-wc-cart__summary" data-ts-cart-summary><?php echo esc_html( tailwindscore_cart_summary_text() ); ?></p>
	</header>

	<?php tailwindscore_render_cart_feedback( array( 'surface' => 'page' ) ); ?>

	<div data-ts-cart-page-inner>
		<?php tailwindscore_render_cart_page_content(); ?>
	</div>
</section>
<?php
do_action( 'woocommerce_after_cart' );
