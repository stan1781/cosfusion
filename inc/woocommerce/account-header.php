<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 是否渲染头部账户 UI（登录触发器或账户菜单）。
 */
function tailwindscore_should_render_header_account_ui(): bool {
	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	if ( function_exists( 'tailwindscore_is_account_auth_surface' ) && tailwindscore_is_account_auth_surface() ) {
		return false;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return false;
	}

	return true;
}

/**
 * 当前请求下头部登录弹窗的重定向 URL。
 */
function tailwindscore_account_header_login_redirect_url(): string {
	global $wp;

	if ( is_singular() ) {
		$url = get_permalink();

		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	if ( ! empty( $wp->request ) ) {
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	return home_url( '/' );
}

/**
 * 统计活跃（未完成）客户订单数以显示头部徽章。
 */
function tailwindscore_account_active_order_count(): int {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_get_orders' ) ) {
		return 0;
	}

	$user_id = get_current_user_id();

	if ( class_exists( 'WC_Order_Process_Customer_View' ) ) {
		$data   = WC_Order_Process_Customer_View::get_dashboard_data( $user_id );
		$counts = isset( $data['counts'] ) && is_array( $data['counts'] ) ? $data['counts'] : array();

		return (int) ( $counts['confirmed'] ?? 0 )
			+ (int) ( $counts['packing'] ?? 0 )
			+ (int) ( $counts['shipping'] ?? 0 );
	}

	$orders = wc_get_orders(
		array(
			'customer_id' => $user_id,
			'status'      => array( 'processing', 'preparing', 'packing', 'shipping' ),
			'limit'       => -1,
			'return'      => 'ids',
		)
	);

	return is_array( $orders ) ? count( $orders ) : 0;
}

/**
 * 在账户菜单中渲染内联订单数徽章（为零时隐藏）。
 */
function tailwindscore_render_account_order_badge(): void {
	$count = tailwindscore_account_active_order_count();

	if ( $count <= 0 ) {
		return;
	}
	?>
	<span class="ts-account-menu__badge" aria-hidden="true"><?php echo esc_html( (string) $count ); ?></span>
	<?php
}

/**
 * 在移动端 Tab 栏账户图标上渲染订单徽章（仅已登录且有活跃订单）。
 */
function tailwindscore_render_mobile_tab_account_badge(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$count = tailwindscore_account_active_order_count();

	if ( $count <= 0 ) {
		return;
	}
	?>
	<span class="ts-mobile-tab-bar__badge" aria-hidden="true"><?php echo esc_html( (string) $count ); ?></span>
	<?php
}

/**
 * 移动端 Tab 栏账户控件的无障碍标签。
 */
function tailwindscore_mobile_tab_account_aria_label(): string {
	if ( ! is_user_logged_in() ) {
		return __( 'My account', 'tailwindscore' );
	}

	$count = tailwindscore_account_active_order_count();

	if ( $count <= 0 ) {
		return __( 'My account', 'tailwindscore' );
	}

	return sprintf(
		/* translators: %d: active order count */
		__( 'My account, %d active orders', 'tailwindscore' ),
		$count
	);
}

/**
 * 为访客输出全站登录对话框（结账页单独输出一份）。
 */
function tailwindscore_render_account_login_dialog(): void {
	if ( is_user_logged_in() || ! tailwindscore_should_render_header_account_ui() ) {
		return;
	}

	get_template_part( 'template-parts/header/account-login-dialog' );
}
add_action( 'wp_footer', 'tailwindscore_render_account_login_dialog', 50 );

/**
 * 在含登录弹窗的访客页避免过期登录 nonce。
 */
function tailwindscore_nocache_guest_account_login_pages(): void {
	if ( is_user_logged_in() || ! tailwindscore_should_render_header_account_ui() ) {
		return;
	}

	nocache_headers();
}
add_action( 'template_redirect', 'tailwindscore_nocache_guest_account_login_pages', 0 );

/**
 * WooCommerce 账户菜单 endpoint 图标映射。
 *
 * @return array<string, string>
 */
function tailwindscore_account_nav_icons(): array {
	return array(
		'dashboard'       => 'package',
		'orders'          => 'package',
		'edit-address'    => 'map-pin',
		'edit-account'    => 'user',
		'customer-logout' => 'log-out',
	);
}

/**
 * 账户菜单 endpoint 的短标签。
 *
 * @return array<string, string>
 */
function tailwindscore_account_nav_short_labels(): array {
	return array(
		'dashboard'       => __( 'Orders', 'tailwindscore' ),
		'orders'          => __( 'Orders', 'tailwindscore' ),
		'edit-address'    => __( 'Address', 'tailwindscore' ),
		'edit-account'    => __( 'Profile', 'tailwindscore' ),
		'customer-logout' => __( 'Log out', 'tailwindscore' ),
	);
}
