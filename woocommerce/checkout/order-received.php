<?php
declare(strict_types=1);

/**
 * 「订单已收到」Hero。
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TailwindScore
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="wc-thankyou__hero">
	<div class="wc-thankyou__hero-icon" aria-hidden="true">
		<span class="icon"><?php echo tailwindscore_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	</div>
	<h1 class="wc-thankyou__title woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
		<?php echo esc_html( tailwindscore_get_thankyou_hero_title() ); ?>
	</h1>
	<?php if ( $order instanceof WC_Order ) : ?>
		<div class="wc-thankyou__subtitle">
			<?php echo wp_kses_post( nl2br( esc_html( tailwindscore_get_thankyou_hero_subtitle() ) ) ); ?>
		</div>
		<?php tailwindscore_render_order_success_partial( 'overview-stats', array( 'order' => $order ) ); ?>
	<?php else : ?>
		<p class="wc-thankyou__subtitle">
			<?php
			echo esc_html(
				apply_filters(
					'woocommerce_thankyou_order_received_text',
					__( 'Thank you. Your order has been received.', 'woocommerce' ),
					false
				)
			);
			?>
		</p>
	<?php endif; ?>
</header>
