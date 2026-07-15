<?php
declare(strict_types=1);

function tailwindscore_purchase_flow_config(): array {
	return array(
		'showDrawerCoupon' => tailwindscore_cart_drawer_coupon_is_enabled(),
		'couponJs'           => tailwindscore_purchase_flow_needs_coupon_js(),
		'storeApiUrl' => rest_url( 'wc/store/v1/' ),
		'nonce'       => wp_create_nonce( 'wc_store_api' ),
		'cartUrl'       => tailwindscore_cart_url(),
		'checkoutUrl'   => tailwindscore_checkout_url(),
		'shopUrl'       => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
		'notifications' => array(
			'selectors' => array(
				'toast'  => '[data-ts-purchase-flow-toast-root]',
				'drawer' => '[data-ts-cart-feedback][data-ts-cart-feedback-surface="drawer"]',
				'inline' => '[data-ts-cart-feedback][data-ts-cart-feedback-surface="inline"]',
				'page'   => '[data-ts-cart-feedback][data-ts-cart-feedback-surface="page"]',
			),
		),
		'i18n'          => array(
			'cartScreenReaderSingular' => __( 'Cart, %d item', 'tailwindscore' ),
			'cartScreenReaderPlural'   => __( 'Cart, %d items', 'tailwindscore' ),
			'cartSummarySingular'      => __( '%d item ready for checkout', 'tailwindscore' ),
			'cartSummaryPlural'        => __( '%d items ready for checkout', 'tailwindscore' ),
			'emptyCartMessage'         => __( 'Your cart is empty.', 'tailwindscore' ),
			'browseProducts'           => __( 'Browse products', 'tailwindscore' ),
			'subtotal'                 => __( 'Subtotal', 'tailwindscore' ),
			'couponDiscount'           => __( 'Coupon discount', 'tailwindscore' ),
			'checkout'                 => __( 'Checkout', 'tailwindscore' ),
			'viewCart'                 => __( 'View cart', 'tailwindscore' ),
			'quantityLabel'            => __( 'Quantity', 'tailwindscore' ),
			'decreaseQuantity'         => __( 'Decrease quantity', 'tailwindscore' ),
			'increaseQuantity'         => __( 'Increase quantity', 'tailwindscore' ),
			'removeItem'               => __( 'Remove item from your cart', 'tailwindscore' ),
			'couponLabel'              => __( 'Coupon code', 'tailwindscore' ),
			'couponPlaceholder'        => __( 'Discount code or gift card', 'tailwindscore' ),
			'applyCoupon'              => __( 'Apply', 'tailwindscore' ),
			'couponApplied'            => __( 'Coupon applied.', 'tailwindscore' ),
			'couponRemoved'            => __( 'Coupon removed.', 'tailwindscore' ),
			'couponInvalid'            => __( 'That coupon code is not valid.', 'tailwindscore' ),
			'couponRequired'           => __( 'Please enter a coupon code.', 'tailwindscore' ),
			'itemRemoved'              => __( 'Item removed from your cart.', 'tailwindscore' ),
			'addedToCart'              => __( 'Added to your cart.', 'tailwindscore' ),
			'shippingAtCheckout'       => __( 'Shipping and taxes calculated at checkout.', 'tailwindscore' ),
		),
		'icons'         => array(
			'close'  => tailwindscore_icon( 'close' ),
			'plus'   => tailwindscore_icon( 'plus' ),
			'minus'  => tailwindscore_icon( 'minus' ),
			'trash'  => tailwindscore_icon( 'trash' ),
			'pencil' => tailwindscore_icon( 'pencil' ),
		),
		'cartItemEdit'  => array(
			'getFormUrl' => add_query_arg( 'wc-ajax', 'tailwindscore_get_cart_item_edit_form', home_url( '/' ) ),
			'saveUrl'    => add_query_arg( 'wc-ajax', 'tailwindscore_update_cart_item_edit', home_url( '/' ) ),
			'nonce'      => wp_create_nonce( 'tailwindscore_cart_item_edit' ),
			'i18n'       => array(
				'title'               => __( 'Edit options', 'tailwindscore' ),
				'edit'                => __( 'Edit', 'tailwindscore' ),
				'save'                => __( 'Save', 'tailwindscore' ),
				'cancel'              => __( 'Cancel', 'tailwindscore' ),
				'loading'             => __( 'Loading…', 'tailwindscore' ),
				'loadFailed'          => __( 'Failed to load item options.', 'tailwindscore' ),
				'saveFailed'          => __( 'Failed to save item options.', 'tailwindscore' ),
				'saved'               => __( 'Item updated.', 'tailwindscore' ),
				'noMatchingVariation' => __( 'No matching variation found.', 'tailwindscore' ),
				'chooseOptions'       => __( 'Please choose product options.', 'woocommerce' ),
				'close'               => __( 'Close edit options', 'tailwindscore' ),
			),
		),
	);
}

/**
 * Whether the coupon form should appear in the cart drawer.
 *
 * Cart page and checkout keep their own coupon UIs regardless of this setting.
 */
function tailwindscore_cart_drawer_coupon_is_enabled(): bool {
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return false;
	}

	$enabled = (bool) tailwindscore_mod( 'cart_drawer_coupon_enable', true );

	/**
	 * Filter whether the mini cart drawer shows a coupon input.
	 *
	 * @param bool $enabled Theme setting value.
	 */
	return (bool) apply_filters( 'tailwindscore_cart_drawer_show_coupon', $enabled );
}

/**
 * Whether purchase-flow coupon handlers should initialize on this request.
 *
 * Used to skip coupon event bindings on pages that never render a coupon form.
 * Note: coupon logic still ships in the main app bundle unless code-split separately.
 */
function tailwindscore_purchase_flow_needs_coupon_js(): bool {
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return false;
	}

	if ( tailwindscore_cart_drawer_coupon_is_enabled() ) {
		return true;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	/**
	 * Filter whether coupon JS should initialize on this request.
	 *
	 * @param bool $needs Default false when no coupon UI is rendered.
	 */
	return (bool) apply_filters( 'tailwindscore_purchase_flow_needs_coupon_js', false );
}

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_cart_drawer_coupon_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_cart_settings',
		array(
			'title'       => esc_html__( 'Cart', 'tailwindscore' ),
			'description' => esc_html__( 'Mini cart drawer and cart page options.', 'tailwindscore' ),
			'panel'       => $targets['woocommerce'],
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'cart_drawer_coupon_enable',
			'label'       => esc_html__( 'Show coupon field in mini cart', 'tailwindscore' ),
			'description' => esc_html__( 'Hides the discount code input in the cart drawer. Cart page and checkout coupon fields are not affected.', 'tailwindscore' ),
			'section'     => 'tailwindscore_cart_settings',
			'default'     => true,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_cart_drawer_coupon_kirki_register', 10, 2 );
