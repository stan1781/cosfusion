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
		'productSuggestionsHtml' => function_exists( 'tailwindscore_get_cart_product_suggestions_html' )
			? tailwindscore_get_cart_product_suggestions_html()
			: '',
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
 * 是否在购物车抽屉中显示优惠券表单。
 *
 * 购物车页与结账页保留各自优惠券 UI，不受此设置影响。
 */
function tailwindscore_cart_drawer_coupon_is_enabled(): bool {
	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return false;
	}

	$enabled = (bool) tailwindscore_mod( 'cart_drawer_coupon_enable', true );

	/**
	 * 过滤迷你购物车抽屉是否显示优惠券输入框。
	 *
	 * @param bool $enabled 主题设置值。
	 */
	return (bool) apply_filters( 'tailwindscore_cart_drawer_show_coupon', $enabled );
}

/**
 * 当前请求是否应初始化购买流程优惠券处理器。
 *
 * 用于在不渲染优惠券表单的页面上跳过优惠券事件绑定。
 * 注意：除非单独拆包，优惠券逻辑仍随主 app bundle 下发。
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
	 * 过滤当前请求是否应初始化优惠券 JS。
	 *
	 * @param bool $needs 未渲染优惠券 UI 时默认为 false。
	 */
	return (bool) apply_filters( 'tailwindscore_purchase_flow_needs_coupon_js', false );
}

/**
 * @param string               $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_cart_drawer_coupon_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_cart_settings',
		array(
			'title'       => esc_html__( '购物车', 'tailwindscore' ),
			'description' => esc_html__( '迷你购物车抽屉与购物车页选项。', 'tailwindscore' ),
			'panel'       => $targets['woocommerce'],
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'cart_drawer_coupon_enable',
			'label'       => esc_html__( '在迷你购物车中显示优惠码字段', 'tailwindscore' ),
			'description' => esc_html__( '关闭后隐藏购物车抽屉中的折扣码输入框。购物车页与结账优惠码字段不受影响。', 'tailwindscore' ),
			'section'     => 'tailwindscore_cart_settings',
			'default'     => true,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_cart_drawer_coupon_kirki_register', 10, 2 );
