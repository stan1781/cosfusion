<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var WC_Order $order */
if ( ! isset( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$item_count  = $order->get_item_count() - $order->get_item_count_refunded();
$order_items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$is_draft    = 'checkout-draft' === $order->get_status();
$order_url   = $is_draft
	? add_query_arg( 'restore_draft_order', $order->get_id(), wc_get_checkout_url() )
	: $order->get_view_order_url();
$bucket      = class_exists( 'WC_Order_Process_Customer_View' )
	? WC_Order_Process_Customer_View::get_bucket_for_order( $order )
	: '';
$steps       = class_exists( 'WC_Order_Process_Customer_View' ) ? WC_Order_Process_Customer_View::get_customer_steps() : array();
$status_label = $is_draft
	? __( 'Draft', 'tailwindscore' )
	: ( isset( $steps[ $bucket ]['label'] ) ? $steps[ $bucket ]['label'] : wc_get_order_status_name( $order->get_status() ) );
$status_msg  = class_exists( 'WC_Order_Process_Customer_View' )
	? WC_Order_Process_Customer_View::get_order_status_message( $order )
	: '';
$badge_mod   = $is_draft ? 'warning' : ( 'completed' === $bucket ? 'success' : ( 'shipping' === $bucket ? 'primary' : ( 'packing' === $bucket ? 'info' : 'neutral' ) ) );
$thumb_limit = 3;
$thumb_index = 0;
?>
<article class="ts-wc-account-order-card">
	<div class="ts-wc-account-order-card__main">
		<?php if ( ! empty( $order_items ) ) : ?>
			<ul class="ts-wc-account-order-card__thumbs" aria-label="<?php esc_attr_e( 'Order items', 'tailwindscore' ); ?>">
				<?php foreach ( $order_items as $item ) : ?>
					<?php
					if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
						continue;
					}
					if ( $thumb_index >= $thumb_limit ) {
						break;
					}
					$product = $item->get_product();
					if ( $product ) {
						$image_id  = $product->get_image_id();
						$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
					} else {
						$image_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
					}
					++$thumb_index;
					?>
					<li>
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $item->get_name() ); ?>" width="56" height="56" loading="lazy" />
					</li>
				<?php endforeach; ?>
				<?php if ( count( $order_items ) > $thumb_limit ) : ?>
					<li class="ts-wc-account-order-card__more">+<?php echo esc_html( (string) ( count( $order_items ) - $thumb_limit ) ); ?></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<div class="ts-wc-account-order-card__content">
			<div class="ts-wc-account-order-card__summary">
				<a class="ts-wc-account-order-card__number" href="<?php echo esc_url( $order_url ); ?>">
					<?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ); ?>
				</a>
				<span class="ts-wc-account-badge ts-wc-account-badge--<?php echo esc_attr( $badge_mod ); ?>">
					<?php echo esc_html( $status_label ); ?>
				</span>
			</div>

			<div class="ts-wc-account-order-card__meta">
				<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
					<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
				</time>
				<span aria-hidden="true">&middot;</span>
				<span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
				<span aria-hidden="true">&middot;</span>
				<span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: item count */
							_n( '%s item', '%s items', $item_count, 'woocommerce' ),
							(string) $item_count
						)
					);
					?>
				</span>
			</div>

			<?php if ( '' !== $status_msg ) : ?>
				<p class="ts-wc-account-order-card__message"><?php echo esc_html( $status_msg ); ?></p>
			<?php endif; ?>

			<?php if ( $is_draft ) : ?>
				<a class="ts-wc-account-order-card__action" href="<?php echo esc_url( $order_url ); ?>">
					<?php esc_html_e( 'Continue checkout', 'tailwindscore' ); ?>
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); ?></span>
				</a>
			<?php else : ?>
				<a class="ts-wc-account-order-card__action" href="<?php echo esc_url( $order_url ); ?>">
					<?php esc_html_e( 'View order', 'tailwindscore' ); ?>
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<a class="ts-wc-account-order-card__chevron" href="<?php echo esc_url( $order_url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View order %s', 'tailwindscore' ), $order->get_order_number() ) ); ?>">
		<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); ?></span>
	</a>
</article>
