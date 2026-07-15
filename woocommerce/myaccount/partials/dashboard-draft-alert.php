<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var WC_Order[] $draft_orders 草稿订单列表 */
$draft_orders = isset( $draft_orders ) && is_array( $draft_orders ) ? $draft_orders : array();

if ( empty( $draft_orders ) ) {
	return;
}
?>
<section class="wc-account-draft-alert stack" aria-label="<?php esc_attr_e( 'Draft orders', 'tailwindscore' ); ?>">
	<p class="wc-account-draft-alert__notice">
		<?php esc_html_e( 'Draft orders are automatically deleted after 24 hours. Please complete your order before it expires.', 'tailwindscore' ); ?>
	</p>
	<?php foreach ( $draft_orders as $draft_order ) : ?>
		<?php
		if ( ! $draft_order instanceof WC_Order ) {
			continue;
		}
		$item_count  = $draft_order->get_item_count();
		$time_info   = WC_Order_Process_Frontend::get_draft_order_time_info( $draft_order );
		$restore_url = add_query_arg( 'restore_draft_order', $draft_order->get_id(), wc_get_checkout_url() );
		?>
		<article class="wc-account-draft-alert__item">
			<div class="wc-account-draft-alert__meta">
				<strong>
					<?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $draft_order->get_order_number() ); ?>
				</strong>
				<span class="badge badge-warning"><?php esc_html_e( 'Draft', 'tailwindscore' ); ?></span>
				<time datetime="<?php echo esc_attr( $draft_order->get_date_created()->date( 'c' ) ); ?>">
					<?php echo esc_html( wc_format_datetime( $draft_order->get_date_created() ) ); ?>
				</time>
				<span><?php echo esc_html( $time_info['text'] ); ?></span>
			</div>
			<div class="wc-account-draft-alert__actions">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: formatted order total 2: total order items */
							_n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce' ),
							$draft_order->get_formatted_order_total(),
							(string) $item_count
						)
					);
					?>
				</p>
				<a class="btn btn-sm" href="<?php echo esc_url( $restore_url ); ?>">
					<?php esc_html_e( 'Continue checkout', 'tailwindscore' ); ?>
				</a>
			</div>
		</article>
	<?php endforeach; ?>
</section>
