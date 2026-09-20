<?php
declare(strict_types=1);

/**
 * 账户 View Order：状态头部卡片 + 订单更新 + 详情。
 *
 * @package TailwindScore
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Order $order */
/** @var int $order_id */

$notes = $order->get_customer_order_notes();

$is_draft = 'checkout-draft' === $order->get_status();
$bucket   = class_exists( 'WC_Order_Process_Customer_View' )
	? WC_Order_Process_Customer_View::get_bucket_for_order( $order )
	: '';

$status_label = class_exists( 'WC_Order_Process_Customer_View' )
	? WC_Order_Process_Customer_View::get_order_status_label( $order )
	: ( $is_draft ? __( 'Draft', 'tailwindscore' ) : wc_get_order_status_name( $order->get_status() ) );

if ( $is_draft || 'awaiting_payment' === $bucket ) {
	$badge_mod = 'warning';
} elseif ( 'completed' === $bucket ) {
	$badge_mod = 'success';
} elseif ( 'shipping' === $bucket ) {
	$badge_mod = 'primary';
} elseif ( 'packing' === $bucket ) {
	$badge_mod = 'info';
} else {
	$badge_mod = 'neutral';
}

$show_stepper = class_exists( 'WC_Order_Process_Customer_View' )
	&& WC_Order_Process_Customer_View::is_fulfillment_bucket( $bucket );

$date_created = $order->get_date_created();
$orders_url   = wc_get_account_endpoint_url( 'orders' );
?>
<section class="wc-account-order-header" aria-labelledby="wc-account-order-header-title">
	<header class="wc-account-order-header__top">
		<div class="wc-account-order-header__title-row">
			<h1 id="wc-account-order-header-title" class="wc-account-order-header__title">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: order number */
						__( 'Order #%s', 'tailwindscore' ),
						$order->get_order_number()
					)
				);
				?>
			</h1>
			<span class="badge badge-<?php echo esc_attr( $badge_mod ); ?>"><?php echo esc_html( $status_label ); ?></span>
		</div>
		<a class="wc-account-order-header__back" href="<?php echo esc_url( $orders_url ); ?>">
			<?php esc_html_e( '< Back to Orders', 'tailwindscore' ); ?>
		</a>
	</header>

	<p class="wc-account-order-header__meta">
		<?php if ( $date_created ) : ?>
			<span class="wc-account-order-header__placed">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: order date */
						__( 'Placed on %s', 'tailwindscore' ),
						wc_format_datetime( $date_created )
					)
				);
				?>
			</span>
			<span class="wc-account-order-header__sep" aria-hidden="true">|</span>
		<?php endif; ?>
		<span class="wc-account-order-header__total">
			<?php esc_html_e( 'Total:', 'tailwindscore' ); ?>
			<span class="wc-account-order-header__total-amount"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
		</span>
	</p>

	<?php if ( $show_stepper ) : ?>
		<?php
		wc_get_template(
			'myaccount/partials/order-stepper.php',
			array(
				'order'   => $order,
				'variant' => 'full',
			)
		);
		?>
	<?php endif; ?>
</section>

<?php if ( $notes ) : ?>
	<h2><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
	<ol class="woocommerce-OrderUpdates commentlist notes">
		<?php foreach ( $notes as $note ) : ?>
			<li class="woocommerce-OrderUpdate comment note">
				<div class="woocommerce-OrderUpdate-inner comment_container">
					<div class="woocommerce-OrderUpdate-text comment-text">
						<p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<div class="woocommerce-OrderUpdate-description description">
							<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
						</div>
						<div class="clear"></div>
					</div>
					<div class="clear"></div>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
