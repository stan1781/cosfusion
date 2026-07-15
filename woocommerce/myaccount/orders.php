<?php
declare(strict_types=1);

/**
 * 订单列表
 *
 * @package TailwindScore
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Order_Process_Customer_View' ) ) {
	// 回退到短代码中的 WooCommerce 默认行为变量。
	do_action( 'woocommerce_before_account_orders', $has_orders );

	if ( $has_orders ) {
		?>
		<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<tbody>
				<?php foreach ( $customer_orders->orders as $customer_order ) : ?>
					<?php $order = wc_get_order( $customer_order ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr>
						<td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	} else {
		wc_print_notice( esc_html__( 'No order has been made yet.', 'woocommerce' ), 'notice' );
	}

	do_action( 'woocommerce_after_account_orders', $has_orders );
	return;
}

$user_id      = get_current_user_id();
$bucket       = tailwindscore_account_current_bucket();
$current_page = isset( $current_page ) ? max( 1, (int) $current_page ) : 1;
$result       = WC_Order_Process_Customer_View::get_orders_for_bucket( $user_id, $bucket, 10, $current_page );
$orders       = $result['orders'];
$has_orders   = ! empty( $orders );
$dashboard    = WC_Order_Process_Customer_View::get_dashboard_data( $user_id, array( 'active_limit' => 1 ) );

do_action( 'woocommerce_before_account_orders', $has_orders );

wc_get_template(
	'myaccount/partials/dashboard-status-grid.php',
	array(
		'counts'          => $dashboard['counts'],
		'bucket_endpoint' => 'orders',
	)
);

if ( $has_orders ) :
	?>
	<div class="wc-account-order-feed stack">
		<?php foreach ( $orders as $list_order ) : ?>
			<?php
			wc_get_template(
				'myaccount/partials/order-card.php',
				array( 'order' => $list_order )
			);
			?>
		<?php endforeach; ?>
	</div>

	<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

	<?php if ( $result['max_pages'] > 1 ) : ?>
		<nav class="wc-account-pagination woocommerce-pagination" aria-label="<?php esc_attr_e( 'Orders pagination', 'woocommerce' ); ?>">
			<?php if ( 1 !== $current_page ) : ?>
				<?php
				$prev_url = wc_get_endpoint_url( 'orders', $current_page - 1 );
				if ( '' !== $bucket ) {
					$prev_url = add_query_arg( 'bucket', $bucket, $prev_url );
				}
				?>
				<a class="btn btn-outline" href="<?php echo esc_url( $prev_url ); ?>">
					<?php esc_html_e( 'Previous', 'woocommerce' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( (int) $result['max_pages'] !== $current_page ) : ?>
				<?php
				$next_url = wc_get_endpoint_url( 'orders', $current_page + 1 );
				if ( '' !== $bucket ) {
					$next_url = add_query_arg( 'bucket', $bucket, $next_url );
				}
				?>
				<a class="btn btn-outline" href="<?php echo esc_url( $next_url ); ?>">
					<?php esc_html_e( 'Next', 'woocommerce' ); ?>
				</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>

<?php else : ?>

	<div class="empty-state">
		<p><?php esc_html_e( 'No order has been made yet.', 'woocommerce' ); ?></p>
		<a class="btn" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
			<?php esc_html_e( 'Browse products', 'woocommerce' ); ?>
		</a>
	</div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
