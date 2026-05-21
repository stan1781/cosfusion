<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Unique collapse target per review-order instance (sidebar + payment duplicate).
 */
static $tailwindscore_checkout_order_review_index = 0;
++$tailwindscore_checkout_order_review_index;
$tailwindscore_order_summary_id = ( 1 === $tailwindscore_checkout_order_review_index )
	? 'checkout-order-summary'
	: 'checkout-order-summary-' . (int) $tailwindscore_checkout_order_review_index;

$tailwindscore_show_coupon_form = (bool) apply_filters( 'bootscore_checkout_show_coupon_in_order_review', true );
$tailwindscore_can_show_coupon_form = (bool) (
	$tailwindscore_show_coupon_form
	&& ( is_checkout() || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) )
	&& wc_coupons_enabled()
);
$tailwindscore_is_primary_summary = ( 1 === $tailwindscore_checkout_order_review_index );
?>
<div class="shop_table woocommerce-checkout-review-order-table woocommerce-checkout-review-order ts-checkout-review-order">
	<details
		class="ts-checkout-order-summary"
		data-ts-checkout-order-summary
		<?php echo $tailwindscore_is_primary_summary ? ' data-ts-checkout-order-summary-primary' : ''; ?>
	>
	<summary class="order-summary-toggle ts-checkout-order-summary__toggle">
		<span class="ts-checkout-order-summary__toggle-inner">
			<span class="ts-checkout-order-summary__lead">
				<span class="order-summary-thumbs ts-checkout-order-summary__thumbs" aria-hidden="true">
					<?php
					if ( WC()->cart ) {
						$product_ids = array();
						foreach ( WC()->cart->get_cart() as $cart_item_for_count ) {
							$_p = isset( $cart_item_for_count['data'] ) ? $cart_item_for_count['data'] : null;
							if ( ! $_p || ! is_a( $_p, 'WC_Product' ) ) {
								continue;
							}
							$product_ids[] = (int) $_p->get_id();
						}
						$has_multiple_products = count( array_unique( $product_ids ) ) > 1;

						foreach ( WC()->cart->get_cart() as $cart_item ) {
							$_product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
							if ( ! $_product || ! is_a( $_product, 'WC_Product' ) ) {
								continue;
							}

							$thumb_html = $_product->get_image(
								array( 36, 36 ),
								array(
									'class' => 'order-summary-thumb-img',
									'alt'   => esc_attr( $_product->get_name() ),
								)
							);

							echo '<span class="order-summary-thumb' . ( $has_multiple_products ? ' has-stack' : '' ) . '"><span class="order-summary-thumb-media">' . wp_kses_post( $thumb_html ) . '</span></span>';
							break;
						}
					}
					?>
				</span>
				<span class="ts-checkout-order-summary__toggle-copy">
				<?php if ( $tailwindscore_can_show_coupon_form ) : ?>
					<span class="order-summary-add-coupon ts-button ts-button--outline ts-button--sm"><?php esc_html_e( 'Add Coupon', 'woocommerce' ); ?></span>
					<span class="order-summary-title-expanded ts-checkout-order-summary__title-expanded"><?php esc_html_e( 'Order summary', 'woocommerce' ); ?></span>
				<?php else : ?>
					<span class="ts-checkout-order-summary__title"><?php esc_html_e( 'Order summary', 'woocommerce' ); ?></span>
				<?php endif; ?>
				<span class="ts-checkout-order-summary__item-count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: item count */
							_n( '%d item', '%d items', WC()->cart ? WC()->cart->get_cart_contents_count() : 0, 'woocommerce' ),
							WC()->cart ? WC()->cart->get_cart_contents_count() : 0
						)
					);
					?>
				</span>
			</span>
			</span>
			<span class="ts-checkout-order-summary__toggle-meta">
				<span class="order-summary-total"><?php wc_cart_totals_order_total_html(); ?></span>
				<span class="order-summary-chevron ts-checkout-order-summary__chevron" aria-hidden="true"></span>
			</span>
		</span>
	</summary>

	<div
		id="<?php echo esc_attr( $tailwindscore_order_summary_id ); ?>"
		class="ts-checkout-order-summary__panel"
	>
		<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

		<div class="cart-items ts-checkout-review-order__items">
			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					?>
					<div class="cart-item ts-checkout-line-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
						<div class="product-image ts-checkout-line-item__media">
							<?php
							if ( $_product->get_image_id() ) {
								echo wp_kses_post( $_product->get_image( array( 60, 60 ), array( 'class' => 'ts-checkout-line-item__img' ) ) );
							} else {
								echo '<div class="ts-checkout-line-item__placeholder" aria-hidden="true"></div>';
							}
							?>
							<span class="ts-checkout-line-item__qty"><?php echo esc_html( (string) $cart_item['quantity'] ); ?></span>
						</div>
						<div class="product-info ts-checkout-line-item__info">
							<div class="product-name ts-checkout-line-item__name">
								<?php echo esc_html( $_product->get_name() ); ?>
							</div>
							<?php
							$variation_data = wc_get_formatted_cart_item_data( $cart_item );
							if ( $variation_data ) {
								echo '<div class="product-variation ts-checkout-line-item__variation">' . $variation_data . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
						<div class="product-price ts-checkout-line-item__price">
							<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
					<?php
				}
			}
			?>
		</div>

		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

		<?php if ( $tailwindscore_show_coupon_form && ( is_checkout() || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) ) && wc_coupons_enabled() ) : ?>
			<div class="coupon-form ts-checkout-review-order__coupon">
				<?php wc_get_template( 'checkout/form-coupon.php', array( 'checkout' => WC()->checkout() ) ); ?>
			</div>
		<?php endif; ?>

		<div class="order-totals ts-checkout-review-order__totals">
			<div class="order-total-row ts-checkout-total-row">
				<span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
				<span><?php wc_cart_totals_subtotal_html(); ?></span>
			</div>

			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
				<div class="order-total-row ts-checkout-total-row cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
					<span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
				<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
				<div class="order-total-row ts-checkout-total-row ts-checkout-total-row--shipping">
					<div class="shipping-method-title ts-checkout-total-row__shipping-label">
						<?php esc_html_e( 'Shipping', 'woocommerce' ); ?>
						<span
							class="ts-wc-checkout__hint"
							title="<?php echo esc_attr__( 'Shipping costs will be calculated after you enter your address', 'woocommerce' ); ?>"
							aria-label="<?php echo esc_attr__( 'Shipping costs will be calculated after you enter your address', 'woocommerce' ); ?>"
						>?</span>
					</div>
					<div class="shipping-method-list ts-checkout-total-row__shipping-value">
						<?php
						$packages       = WC()->cart ? WC()->cart->get_shipping_packages() : array();
						$chosen_methods = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
						$has_any_rate   = false;

						if ( WC()->shipping() ) {
							WC()->shipping()->calculate_shipping( $packages );
							$packages = WC()->shipping()->get_packages();
						}

						foreach ( $packages as $i => $package ) {
							if ( empty( $package['rates'] ) || ! is_array( $package['rates'] ) ) {
								continue;
							}

							$chosen_rate_id = isset( $chosen_methods[ $i ] ) ? (string) $chosen_methods[ $i ] : '';
							$rate           = ( $chosen_rate_id && isset( $package['rates'][ $chosen_rate_id ] ) )
								? $package['rates'][ $chosen_rate_id ]
								: reset( $package['rates'] );

							if ( ! $rate ) {
								continue;
							}

							$has_any_rate = true;
							$label        = is_callable( array( $rate, 'get_label' ) ) ? $rate->get_label() : '';
							$cost         = is_callable( array( $rate, 'get_cost' ) ) ? (float) $rate->get_cost() : 0.0;
							$taxes        = is_callable( array( $rate, 'get_taxes' ) ) ? (array) $rate->get_taxes() : array();
							$display_cost = $cost;

							if ( WC()->cart && WC()->cart->display_prices_including_tax() ) {
								$display_cost += array_sum( $taxes );
							}

							echo '<div class="shipping-method-selected">';
							echo esc_html( $label );
							echo '<span class="woocommerce-Price-amount amount">' . wp_kses_post( wc_price( $display_cost ) ) . '</span>';
							echo '</div>';
						}

						if ( ! $has_any_rate ) {
							$fallback_total = WC()->cart ? WC()->cart->get_cart_shipping_total() : '';
							if ( $fallback_total ) {
								echo '<div class="shipping-method-selected">' . wp_kses_post( $fallback_total ) . '</div>';
							} else {
								echo '<div class="shipping-method-selected">' . esc_html__( 'Shipping will be calculated during checkout.', 'woocommerce' ) . '</div>';
							}
						}
						?>
					</div>
				</div>
				<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
			<?php endif; ?>

			<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<div class="order-total-row ts-checkout-total-row fee">
					<span><?php echo esc_html( $fee->name ); ?></span>
					<span><?php wc_cart_totals_fee_html( $fee ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
				<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
					<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
						<div class="order-total-row ts-checkout-total-row tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
							<span><?php echo esc_html( $tax->label ); ?></span>
							<span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="order-total-row ts-checkout-total-row tax-total">
						<span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
						<span><?php wc_cart_totals_taxes_total_html(); ?></span>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

			<div class="order-total-row ts-checkout-total-row ts-checkout-total-row--grand">
				<span><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
				<span><?php wc_cart_totals_order_total_html(); ?></span>
			</div>

			<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

			<?php if ( wc_tax_enabled() && WC()->cart->display_prices_including_tax() ) : ?>
				<div class="tax-info ts-checkout-review-order__tax-note">
					<?php
					$tax_string_array = array();
					$cart_tax_totals  = WC()->cart->get_tax_totals();
					if ( get_option( 'woocommerce_tax_total_display' ) === 'itemized' ) {
						foreach ( $cart_tax_totals as $code => $tax ) {
							$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
						}
					} elseif ( ! empty( $cart_tax_totals ) ) {
						$tax_string_array[] = sprintf(
							'%s %s',
							wc_price( WC()->cart->get_taxes_total( true, true ) ),
							WC()->countries->tax_or_vat()
						);
					}
					if ( ! empty( $tax_string_array ) ) {
						echo '<span>' . sprintf(
							/* translators: %s: tax amounts */
							esc_html__( 'Including %s in taxes', 'woocommerce' ),
							esc_html( implode( ', ', $tax_string_array ) )
						) . '</span>';
					} else {
						echo '<span>' . sprintf(
							/* translators: %s: zero tax amount */
							esc_html__( 'Including %s in taxes', 'woocommerce' ),
							wp_kses_post( wc_price( 0 ) )
						) . '</span>';
					}
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	</details>
</div>
