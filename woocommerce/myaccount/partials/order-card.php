<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var WC_Order $order 订单对象 */
if ( ! isset( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$order_items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$line_items  = array();

foreach ( $order_items as $item ) {
	if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
		$line_items[] = $item;
	}
}

$is_draft  = 'checkout-draft' === $order->get_status();
$order_url = $is_draft
	? add_query_arg( 'restore_draft_order', $order->get_id(), wc_get_checkout_url() )
	: $order->get_view_order_url();
$bucket    = class_exists( 'WC_Order_Process_Customer_View' )
	? WC_Order_Process_Customer_View::get_bucket_for_order( $order )
	: '';
$steps     = class_exists( 'WC_Order_Process_Customer_View' ) ? WC_Order_Process_Customer_View::get_customer_steps() : array();
$status_label = $is_draft
	? __( 'Draft', 'tailwindscore' )
	: ( isset( $steps[ $bucket ]['label'] ) ? $steps[ $bucket ]['label'] : wc_get_order_status_name( $order->get_status() ) );
$status_msg = class_exists( 'WC_Order_Process_Customer_View' )
	? WC_Order_Process_Customer_View::get_order_status_message( $order )
	: '';
$badge_mod  = $is_draft ? 'warning' : ( 'completed' === $bucket ? 'success' : ( 'shipping' === $bucket ? 'primary' : ( 'packing' === $bucket ? 'info' : 'neutral' ) ) );
$line_limit = 3;
$line_total = count( $line_items );
$hit_label  = $is_draft
	? sprintf(
		/* translators: %s: order number */
		__( 'Continue checkout for order %s', 'tailwindscore' ),
		$order->get_order_number()
	)
	: sprintf(
		/* translators: %s: order number */
		__( 'View order %s', 'tailwindscore' ),
		$order->get_order_number()
	);

/**
 * 行项第二行：变体摘要 · ×数量。
 *
 * @param WC_Order_Item_Product $item 行项。
 */
$format_line_meta = static function ( WC_Order_Item_Product $item ): string {
	$parts = array();

	foreach ( $item->get_formatted_meta_data( '' ) as $meta ) {
		$key   = wp_strip_all_tags( (string) $meta->display_key );
		$value = wp_strip_all_tags( (string) $meta->display_value );
		if ( '' === $key && '' === $value ) {
			continue;
		}
		$parts[] = '' !== $key ? $key . ': ' . $value : $value;
	}

	$qty = '×' . (string) max( 1, (int) $item->get_quantity() );

	if ( array() === $parts ) {
		return $qty;
	}

	return implode( ' · ', $parts ) . ' · ' . $qty;
};
?>
<article class="wc-account-order-card">
	<a class="wc-account-order-card__hit" href="<?php echo esc_url( $order_url ); ?>" aria-label="<?php echo esc_attr( $hit_label ); ?>"></a>

	<header class="wc-account-order-card__header">
		<div class="wc-account-order-card__id">
			<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
				<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
			</time>
			<span aria-hidden="true">&middot;</span>
			<span class="wc-account-order-card__number">
				<?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ); ?>
			</span>
		</div>
		<div class="wc-account-order-card__total">
			<?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
		</div>
	</header>

	<div class="wc-account-order-card__body">
		<?php if ( $line_total > 0 ) : ?>
			<ul class="wc-account-order-card__lines" aria-label="<?php esc_attr_e( 'Order items', 'tailwindscore' ); ?>">
				<?php
				$shown = 0;
				foreach ( $line_items as $item ) :
					if ( $shown >= $line_limit ) {
						break;
					}
					$product = $item->get_product();
					if ( $product ) {
						$image_id  = $product->get_image_id();
						$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
					} else {
						$image_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
					}
					++$shown;
					?>
					<li class="wc-account-order-card__line">
						<img
							class="wc-account-order-card__thumb"
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $item->get_name() ); ?>"
							width="56"
							height="56"
							loading="lazy"
						/>
						<div class="wc-account-order-card__line-body">
							<div class="wc-account-order-card__line-name"><?php echo esc_html( $item->get_name() ); ?></div>
							<div class="wc-account-order-card__line-meta"><?php echo esc_html( $format_line_meta( $item ) ); ?></div>
						</div>
					</li>
				<?php endforeach; ?>
				<?php if ( $line_total > $line_limit ) : ?>
					<li class="wc-account-order-card__more" aria-hidden="true">+<?php echo esc_html( (string) ( $line_total - $line_limit ) ); ?></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<aside class="wc-account-order-card__status">
			<span class="badge badge-<?php echo esc_attr( $badge_mod ); ?>">
				<?php echo esc_html( $status_label ); ?>
			</span>
			<?php if ( '' !== $status_msg ) : ?>
				<p class="wc-account-order-card__message"><?php echo esc_html( $status_msg ); ?></p>
			<?php endif; ?>
		</aside>
	</div>
</article>
