<?php
declare(strict_types=1);

/**
 * 购物车页面
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

// 购物车页是备用的摘要/编辑界面，不是主要购物流程。

do_action( 'woocommerce_before_cart' );

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}
?>
<section class="wc-cart stack" data-ts-cart-page>
	<header class="wc-cart__header stack">
		<h1 class="wc-cart__title"><?php esc_html_e( 'Cart', 'tailwindscore' ); ?></h1>
		<p class="wc-cart__summary" data-ts-cart-summary><?php echo esc_html( tailwindscore_cart_summary_text() ); ?></p>
	</header>

	<?php tailwindscore_render_cart_feedback( array( 'surface' => 'page' ) ); ?>

	<div data-ts-cart-page-inner>
		<?php tailwindscore_render_cart_page_content(); ?>
	</div>
</section>
<?php
do_action( 'woocommerce_after_cart' );
