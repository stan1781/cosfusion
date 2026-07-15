<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var WC_Order[] $orders 订单列表 */
$orders = isset( $orders ) && is_array( $orders ) ? $orders : array();
$show_view_all = ! isset( $show_view_all ) || $show_view_all;
?>
<section class="wc-account-order-feed stack">
	<div class="wc-account-order-feed__header">
		<h2 class="heading"><?php esc_html_e( 'Your orders', 'tailwindscore' ); ?></h2>
		<?php if ( $show_view_all ) : ?>
			<a class="wc-account-order-feed__all-link" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
				<?php esc_html_e( 'View all orders', 'tailwindscore' ); ?>
				<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); ?></span>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( empty( $orders ) ) : ?>
		<div class="empty-state">
			<p><?php esc_html_e( 'No orders in this view yet.', 'tailwindscore' ); ?></p>
			<a class="btn" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Browse products', 'tailwindscore' ); ?>
			</a>
		</div>
	<?php else : ?>
		<?php foreach ( $orders as $feed_order ) : ?>
			<?php
			wc_get_template(
				'myaccount/partials/order-card.php',
				array( 'order' => $feed_order )
			);
			?>
		<?php endforeach; ?>
	<?php endif; ?>
</section>
