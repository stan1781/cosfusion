<?php
declare(strict_types=1);

/**
 * 「订单已收到」消息。
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TailwindScore
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

/**
 * 筛选结账完成后显示的消息。
 *
 * @since 2.2.0
 *
 * @param string         $message 消息文本。
 * @param WC_Order|false $order   结账时创建的订单；若订单数据不可用则为 false。
 */
$message = apply_filters(
	'woocommerce_thankyou_order_received_text',
	esc_html( __( 'Thank you. Your order has been received.', 'woocommerce' ) ),
	$order
);
?>
<header class="ts-wc-thankyou__hero">
	<h1 class="ts-wc-thankyou__title woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $message;
		?>
	</h1>
	<?php if ( $order instanceof WC_Order ) : ?>
		<p class="ts-wc-thankyou__subtitle">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: order number */
					__( 'Order #%s is confirmed. We will send you updates by email.', 'tailwindscore' ),
					'<strong>' . esc_html( $order->get_order_number() ) . '</strong>'
				)
			);
			?>
		</p>
	<?php endif; ?>
</header>
