<?php
declare(strict_types=1);

/**
 * WhatsApp 联系 helpers（号码、预填消息、跳转 URL）。
 *
 * 本轮仅供订单成功页等调用；页头顶栏 / FAB 未接入。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * WhatsApp 号码（E.164 数字，不含 +）。
 */
function tailwindscore_get_whatsapp_number(): string {
	$number = (string) tailwindscore_mod( 'contact_whatsapp_number', '' );
	$number = preg_replace( '/\D+/', '', $number );

	/** @param string $number */
	return (string) apply_filters( 'tailwindscore/contact/whatsapp_number', is_string( $number ) ? $number : '' );
}

/**
 * 展示用 WhatsApp 号码。
 */
function tailwindscore_get_whatsapp_display_number(): string {
	$display = trim( (string) tailwindscore_mod( 'contact_whatsapp_display', '' ) );

	if ( '' !== $display ) {
		return $display;
	}

	$number = tailwindscore_get_whatsapp_number();
	if ( '' === $number ) {
		return '';
	}

	return '+' . $number;
}

/**
 * tel: URI（含 +，无空格），用于拨号链接。
 */
function tailwindscore_get_whatsapp_tel_uri(): string {
	$number = tailwindscore_get_whatsapp_number();
	if ( '' === $number ) {
		return '';
	}

	return 'tel:+' . $number;
}

/**
 * 替换消息模板占位符。
 */
function tailwindscore_build_whatsapp_message( string $template, ?WC_Order $order = null ): string {
	$message = trim( $template );

	if ( $order instanceof WC_Order ) {
		$replacements = array(
			'{order_number}' => $order->get_order_number(),
			'{order_total}'  => wp_strip_all_tags( $order->get_formatted_order_total() ),
		);

		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $message );
	}

	/** @param string $message */
	return (string) apply_filters( 'tailwindscore/contact/whatsapp_message', $message, $template, $order );
}

/**
 * 构建 WhatsApp 跳转 URL。
 *
 * @param array{message?: string, context?: string, order?: WC_Order|null} $args
 */
function tailwindscore_get_whatsapp_url( array $args = array() ): string {
	$number = tailwindscore_get_whatsapp_number();
	if ( '' === $number ) {
		return '';
	}

	$context = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'default';
	$order   = isset( $args['order'] ) && $args['order'] instanceof WC_Order ? $args['order'] : null;

	if ( isset( $args['message'] ) ) {
		$message = (string) $args['message'];
	} elseif ( 'order' === $context ) {
		$template = (string) tailwindscore_mod(
			'contact_whatsapp_message_order',
			__( 'Hi, I just placed order #{order_number} and would like to confirm the details.', 'tailwindscore' )
		);
		$message  = tailwindscore_build_whatsapp_message( $template, $order );
	} else {
		$template = (string) tailwindscore_mod(
			'contact_whatsapp_message_default',
			__( 'Hi, I have a question about my order.', 'tailwindscore' )
		);
		$message  = tailwindscore_build_whatsapp_message( $template, $order );
	}

	$url = 'https://api.whatsapp.com/send?phone=' . rawurlencode( $number );

	if ( '' !== $message ) {
		$url .= '&text=' . rawurlencode( $message );
	}

	/** @param string $url */
	return (string) apply_filters( 'tailwindscore/contact/whatsapp_url', $url, $number, $message, $args );
}
