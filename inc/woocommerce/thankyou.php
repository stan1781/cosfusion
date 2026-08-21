<?php
declare(strict_types=1);

/**
 * 订单成功页（order-received）领域 helpers。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 是否处于订单已收到 endpoint。
 */
function tailwindscore_is_order_received_context(): bool {
	return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
}

/**
 * 渲染 order-success 片段。
 *
 * @param array<string, mixed> $args
 */
function tailwindscore_render_order_success_partial( string $slug, array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'order-success/' . $slug, $args );
}

/**
 * 渲染订单行项。
 */
function tailwindscore_render_order_line_item( WC_Order_Item_Product $item ): void {
	tailwindscore_load_woocommerce_template_part(
		'order-line-item',
		array(
			'item' => $item,
		)
	);
}

/**
 * 行项隐藏 meta 键。
 *
 * @return string[]
 */
function tailwindscore_get_order_line_item_hidden_meta_keys(): array {
	$keys = array();

	/** @param string[] $keys */
	return array_values( apply_filters( 'tailwindscore/order/hidden_meta_keys', $keys ) );
}

/**
 * @return array<int, array{type: string, label: string, value: string}>
 */
function tailwindscore_get_order_line_item_meta( WC_Order_Item_Product $item ): array {
	$meta        = array();
	$hidden_keys = tailwindscore_get_order_line_item_hidden_meta_keys();
	$formatted   = $item->get_formatted_meta_data( '_', true );

	foreach ( $formatted as $formatted_meta ) {
		if ( ! is_object( $formatted_meta ) ) {
			continue;
		}

		$key = isset( $formatted_meta->key ) ? (string) $formatted_meta->key : '';
		if ( '' === $key || in_array( $key, $hidden_keys, true ) ) {
			continue;
		}

		$label = isset( $formatted_meta->display_key ) ? wp_strip_all_tags( (string) $formatted_meta->display_key ) : $key;
		$value = isset( $formatted_meta->display_value ) ? (string) $formatted_meta->display_value : '';

		if ( '' === $label || '' === $value ) {
			continue;
		}

		$meta[] = array(
			'type'  => 'custom',
			'label' => $label,
			'value' => $value,
		);
	}

	/** @param array<int, array{type: string, label: string, value: string}> $meta */
	return (array) apply_filters( 'tailwindscore/order/line_item_meta', $meta, $item );
}

/**
 * Hero 标题。
 */
function tailwindscore_get_thankyou_hero_title(): string {
	return trim( (string) tailwindscore_mod( 'thankyou_hero_title', __( 'Order received!', 'tailwindscore' ) ) );
}

/**
 * Hero 副标题默认文案。
 */
function tailwindscore_thankyou_hero_subtitle_default(): string {
	return __( "Thank you for your order!\nWe will contact you if anything needs confirmation.", 'tailwindscore' );
}

/**
 * Hero 副标题。
 */
function tailwindscore_get_thankyou_hero_subtitle(): string {
	$stored = trim( (string) tailwindscore_mod( 'thankyou_hero_subtitle', '' ) );

	return '' !== $stored ? $stored : tailwindscore_thankyou_hero_subtitle_default();
}

/**
 * Next steps 默认三格。
 *
 * @return array<int, array{body: string}>
 */
function tailwindscore_thankyou_next_steps_defaults(): array {
	return array(
		array(
			'body' => __( 'You will receive an <strong>email confirmation</strong><br>with your order details', 'tailwindscore' ),
		),
		array(
			'body' => __( 'Track your order status<br>anytime from your account', 'tailwindscore' ),
		),
		array(
			'body' => __( 'Need changes? Contact us<br>as soon as possible', 'tailwindscore' ),
		),
	);
}

/**
 * @return array<int, array{body: string}>
 */
function tailwindscore_get_thankyou_next_steps(): array {
	$stored = tailwindscore_mod( 'thankyou_next_steps', tailwindscore_thankyou_next_steps_defaults() );

	if ( ! is_array( $stored ) ) {
		return tailwindscore_thankyou_next_steps_defaults();
	}

	$steps = array();
	foreach ( $stored as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$body = trim( (string) ( $row['body'] ?? '' ) );
		if ( '' === $body ) {
			continue;
		}

		$steps[] = array( 'body' => $body );
	}

	return ! empty( $steps ) ? $steps : tailwindscore_thankyou_next_steps_defaults();
}

/**
 * 商店 URL（成功页继续购物）。
 */
function tailwindscore_get_thankyou_shop_url(): string {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	if ( ! is_string( $shop_url ) || '' === $shop_url ) {
		return home_url( '/' );
	}

	return $shop_url;
}

/**
 * 移除 Woo 默认订单详情 / 地址输出（由主题模板接管）。
 */
function tailwindscore_thankyou_remove_default_wc_output(): void {
	if ( ! tailwindscore_is_order_received_context() ) {
		return;
	}

	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
}
add_action( 'wp', 'tailwindscore_thankyou_remove_default_wc_output', 20 );

/**
 * 付款网关说明输出容器。
 */
function tailwindscore_thankyou_payment_instructions( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$payment_method = $order->get_payment_method();
	if ( '' === $payment_method ) {
		return;
	}

	ob_start();
	do_action( 'woocommerce_thankyou_' . $payment_method, $order_id );
	$html = trim( (string) ob_get_clean() );

	if ( '' === $html ) {
		return;
	}

	printf(
		'<div class="wc-thankyou__payment-instructions">%s</div>',
		wp_kses_post( wpautop( $html ) )
	);
}

/**
 * Kirki：结账 → 订单成功页文案。
 *
 * @param string                $cfg
 * @param array<string, string> $targets
 */
function tailwindscore_thankyou_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$section = $targets['checkout'] ?? 'tailwindscore_checkout_settings';

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'thankyou_hero_title',
			'label'    => esc_html__( '订单成功页标题', 'tailwindscore' ),
			'section'  => $section,
			'default'  => __( 'Order received!', 'tailwindscore' ),
			'priority' => 85,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'textarea',
			'settings'    => 'thankyou_hero_subtitle',
			'label'       => esc_html__( '订单成功页副标题', 'tailwindscore' ),
			'description' => esc_html__( '留空则使用主题默认英文文案；可换行分段。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => '',
			'priority'    => 86,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'      => 'repeater',
			'settings'  => 'thankyou_next_steps',
			'label'     => esc_html__( '订单成功页下一步指引', 'tailwindscore' ),
			'section'   => $section,
			'priority'  => 87,
			'row_label' => array(
				'type'  => 'field',
				'value' => esc_html__( '指引', 'tailwindscore' ),
				'field' => 'body',
			),
			'default'   => tailwindscore_thankyou_next_steps_defaults(),
			'fields'    => array(
				'body' => array(
					'type'        => 'textarea',
					'label'       => esc_html__( '文案', 'tailwindscore' ),
					'description' => esc_html__( '可使用 <br> 换行、<strong> 强调。前台默认英文。', 'tailwindscore' ),
					'default'     => '',
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_thankyou_kirki_register', 10, 2 );
