<?php
declare(strict_types=1);

/**
 * 后台订单：根据 customer_user_agent 显示装置 OS 标识。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 从 User-Agent 粗分 OS。
 *
 * 匹配顺序：iOS → Android → macOS → Windows → Linux；
 * iOS 必须先于 macOS，避免 `like Mac OS X` 误判。
 *
 * @return string ios|android|macos|windows|linux|''（未识别）。
 */
function tailwindscore_detect_os_from_ua( string $ua ): string {
	$ua = strtolower( trim( $ua ) );
	if ( '' === $ua ) {
		return '';
	}

	if ( str_contains( $ua, 'iphone' ) || str_contains( $ua, 'ipad' ) || str_contains( $ua, 'ipod' ) ) {
		return 'ios';
	}

	if ( str_contains( $ua, 'android' ) ) {
		return 'android';
	}

	if ( str_contains( $ua, 'macintosh' ) || str_contains( $ua, 'mac os' ) ) {
		return 'macos';
	}

	if ( str_contains( $ua, 'windows' ) ) {
		return 'windows';
	}

	if ( str_contains( $ua, 'linux' ) ) {
		return 'linux';
	}

	return '';
}

/**
 * OS slug → 后台展示文案（品牌名英文）。
 */
function tailwindscore_os_label( string $os ): string {
	$labels = array(
		'ios'     => 'iOS',
		'android' => 'Android',
		'macos'   => 'macOS',
		'windows' => 'Windows',
		'linux'   => 'Linux',
	);

	return $labels[ $os ] ?? '';
}

/**
 * 订单客户装置 OS（展示时解析 UA，不写 meta）。
 */
function tailwindscore_order_customer_os( WC_Order $order ): string {
	$ua = (string) $order->get_customer_user_agent();

	return tailwindscore_detect_os_from_ua( $ua );
}

/**
 * 后台订单详情：装置 OS。
 */
function tailwindscore_order_os_admin_notice( WC_Order $order ): void {
	$os    = tailwindscore_order_customer_os( $order );
	$label = tailwindscore_os_label( $os );

	if ( '' === $label ) {
		return;
	}
	?>
	<div class="address" style="margin-top:12px">
		<p><strong><?php esc_html_e( 'Device', 'tailwindscore' ); ?>:</strong>
			<?php echo esc_html( $label ); ?>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'tailwindscore_order_os_admin_notice', 25 );

/**
 * 后台订单列表：在 Origin 列追加 OS（不另开列）。
 *
 * @param string       $column Column key.
 * @param int|WC_Order $order  Order ID (CPT) or object (HPOS).
 */
function tailwindscore_order_os_admin_origin_column_append( string $column, $order ): void {
	if ( 'origin' !== $column ) {
		return;
	}

	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( absint( $order ) );
	}

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$label = tailwindscore_os_label( tailwindscore_order_customer_os( $order ) );
	if ( '' === $label ) {
		return;
	}

	echo '<br><span class="order-os-tag" style="display:inline-block;margin-top:2px;color:#646970;">'
		. esc_html( $label )
		. '</span>';
}
add_action( 'manage_shop_order_posts_custom_column', 'tailwindscore_order_os_admin_origin_column_append', 25, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'tailwindscore_order_os_admin_origin_column_append', 25, 2 );
