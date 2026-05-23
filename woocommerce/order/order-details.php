<?php
declare(strict_types=1);

/**
 * Order details
 *
 * @package TailwindScore
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
	return;
}

$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$downloads          = $order->get_downloadable_items();
$actions            = array_filter(
	wc_get_account_orders_actions( $order ),
	static function ( $key ) {
		return 'view' !== $key;
	},
	ARRAY_FILTER_USE_KEY
);
$show_customer_details = $order->get_user_id() === get_current_user_id();
$process_data          = class_exists( 'WC_Order_Process_Manager' ) ? WC_Order_Process_Manager::get_order_process( $order->get_id() ) : null;
$order_status          = $order->get_status();
$is_shipping           = ( 'shipping' === $order_status ) || ( $process_data && 'shipping' === $process_data['process_step'] );

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		array(
			'downloads'  => $downloads,
			'show_title' => true,
		)
	);
}
?>
<section class="woocommerce-order-details ts-stack">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

	<?php if ( class_exists( 'WC_Order_Process_Customer_View' ) ) : ?>
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

	<h2 class="woocommerce-order-details__title ts-heading"><?php esc_html_e( 'Order details', 'woocommerce' ); ?></h2>

	<div class="ts-wc-account-order-card">
		<div class="ts-wc-account-order-details__items">
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );

			foreach ( $order_items as $item_id => $item ) {
				if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					continue;
				}

				$product = $item->get_product();
				if ( $product ) {
					$image_id  = $product->get_image_id();
					$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
				} else {
					$image_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
				}

				$is_visible        = $product && $product->is_visible();
				$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );
				$product_name      = apply_filters( 'woocommerce_order_item_name', $product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $item->get_name() ) : $item->get_name(), $item, $is_visible );
				$quantity          = $item->get_quantity();
				$refunded_qty      = $order->get_qty_refunded_for_item( $item_id );
				if ( $refunded_qty ) {
					$qty_display = '<del>' . esc_html( (string) $quantity ) . '</del> <ins>' . esc_html( (string) ( $quantity - ( $refunded_qty * -1 ) ) ) . '</ins>';
				} else {
					$qty_display = esc_html( (string) $quantity );
				}
				$quantity_html = apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $qty_display ) . '</strong>', $item );
				$line_total    = $order->get_formatted_line_subtotal( $item );
				$purchase_note = $product ? $product->get_purchase_note() : '';
				$item_class    = apply_filters( 'woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order );
				?>
				<article class="ts-wc-account-order-details__item <?php echo esc_attr( $item_class ); ?>">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $item->get_name() ); ?>" width="80" height="80" loading="lazy" />
					<div class="ts-wc-account-order-details__item-body">
						<div class="product-name">
							<?php echo wp_kses_post( $product_name ); ?>
							<?php echo wp_kses_post( $quantity_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<?php
						do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
						ob_start();
						wc_display_item_meta(
							$item,
							array(
								'before'    => '<ul class="wc-item-meta"><li>',
								'after'     => '</li></ul>',
								'separator' => '</li><li>',
								'echo'      => true,
								'autop'     => false,
							)
						);
						$variation_string = ob_get_clean();
						if ( ! empty( $variation_string ) && '' !== trim( wp_strip_all_tags( $variation_string ) ) ) {
							echo wp_kses_post( $variation_string );
						}
						do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
						if ( $show_purchase_note && $purchase_note ) :
							?>
							<div class="purchase-note">
								<strong><?php esc_html_e( 'Purchase note:', 'woocommerce' ); ?></strong>
								<?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<?php
						endif;
						?>
					</div>
					<div class="product-price"><?php echo wp_kses_post( $line_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</article>
				<?php
			}

			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</div>

		<div class="ts-wc-account-order-details__totals">
			<?php foreach ( $order->get_order_item_totals() as $total ) : ?>
				<div class="ts-wc-account-order-details__total-row">
					<span><?php echo esc_html( $total['label'] ); ?></span>
					<span><?php echo wp_kses_post( $total['value'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $order->get_customer_note() ) : ?>
			<div class="ts-wc-account-order-details__note">
				<strong><?php esc_html_e( 'Note:', 'woocommerce' ); ?></strong>
				<?php
				$customer_note = wc_wptexturize_order_note( $order->get_customer_note() );
				echo wp_kses( nl2br( $customer_note ), array( 'br' => array() ) );
				?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $actions ) || $is_shipping ) : ?>
			<footer class="ts-wc-account-order-card__footer">
				<?php foreach ( $actions as $key => $action ) : ?>
					<a class="ts-button ts-button--sm ts-button--outline <?php echo esc_attr( sanitize_html_class( $key ) ); ?>" href="<?php echo esc_url( $action['url'] ); ?>">
						<?php echo esc_html( $action['name'] ); ?>
					</a>
				<?php endforeach; ?>
				<?php if ( $is_shipping ) : ?>
					<button
						type="button"
						class="ts-button ts-button--sm wc-confirm-receipt-btn"
						data-order-id="<?php echo esc_attr( (string) $order->get_id() ); ?>"
						data-ts-button-loading
					>
						<?php esc_html_e( 'Confirm Receipt', 'tailwindscore' ); ?>
					</button>
				<?php endif; ?>
			</footer>
		<?php endif; ?>
	</div>

	<?php if ( $process_data && class_exists( 'WC_Order_Process_Tracking_Widget_Manager' ) && ! empty( $process_data['international_tracking'] ) ) : ?>
		<section class="ts-wc-account-process-info">
			<h3 class="ts-heading"><?php esc_html_e( 'Shipping information', 'tailwindscore' ); ?></h3>
			<?php if ( ! empty( $process_data['international_carrier'] ) ) : ?>
				<?php
				$carriers     = WC_Order_Process_Tracking_Widget_Manager::get_supported_carriers();
				$carrier_name = $carriers[ $process_data['international_carrier'] ] ?? $process_data['international_carrier'];
				?>
				<p><strong><?php esc_html_e( 'Carrier:', 'tailwindscore' ); ?></strong> <?php echo esc_html( $carrier_name ); ?></p>
			<?php endif; ?>
			<p>
				<strong><?php esc_html_e( 'Tracking number:', 'tailwindscore' ); ?></strong>
				<?php
				echo WC_Order_Process_Tracking_Widget_Manager::render_tracking_widget( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$process_data['international_tracking'],
					$process_data['international_carrier'],
					null,
					array( 'context' => 'frontend' )
				);
				?>
			</p>
		</section>
	<?php endif; ?>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<?php
do_action( 'woocommerce_after_order_details', $order );

if ( $show_customer_details ) {
	wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
}
