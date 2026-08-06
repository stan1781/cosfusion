<?php
declare(strict_types=1);

/**
 * My Account 心愿单 endpoint。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist My Account integration.
 */
class Tailwindscore_Wishlist_Account {

	public const ENDPOINT       = 'wishlist';
	public const OPTION_FLUSHED = 'tailwindscore_wishlist_rewrite_flushed';

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'register_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'menu_items' ), 40 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_action( 'after_switch_theme', array( $this, 'flush_rewrites' ) );
		add_action( 'after_setup_theme', array( $this, 'maybe_flush_rewrites' ), 99 );
		add_filter( 'tailwindscore_quick_view_is_target_page', array( $this, 'enable_quick_view_on_endpoint' ) );
	}

	/**
	 * 注册到 WC query vars，供 is_wc_endpoint_url / 账户 URL 使用。
	 *
	 * @param array<string, string> $vars Query vars.
	 * @return array<string, string>
	 */
	public function register_query_var( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Account Wishlist 页加载 Quick View，供可变品选规格加购。
	 *
	 * 注意：仅 add_rewrite_endpoint 时 is_wc_endpoint_url() 可能为 false，
	 * 需同时看 $wp->query_vars（woocommerce_account_content 也是这么匹配的）。
	 *
	 * @param bool $matched 是否已是 QV 目标页。
	 */
	public function enable_quick_view_on_endpoint( bool $matched ): bool {
		if ( $matched ) {
			return true;
		}

		global $wp;

		if ( isset( $wp->query_vars[ self::ENDPOINT ] ) ) {
			return true;
		}

		return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( self::ENDPOINT );
	}

	/**
	 * @return void
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * @return void
	 */
	public function flush_rewrites(): void {
		$this->add_endpoint();
		flush_rewrite_rules( false );
		update_option( self::OPTION_FLUSHED, TAILWINDSCORE_MODULE_WISHLIST_VERSION );
	}

	/**
	 * @return void
	 */
	public function maybe_flush_rewrites(): void {
		$flushed = (string) get_option( self::OPTION_FLUSHED, '' );

		if ( $flushed === TAILWINDSCORE_MODULE_WISHLIST_VERSION ) {
			return;
		}

		$this->flush_rewrites();
	}

	/**
	 * @param array<string, string> $items 菜单项。
	 * @return array<string, string>
	 */
	public function menu_items( array $items ): array {
		$logout = null;

		if ( isset( $items['customer-logout'] ) ) {
			$logout = $items['customer-logout'];
			unset( $items['customer-logout'] );
		}

		$items[ self::ENDPOINT ] = __( 'Wishlist', 'tailwindscore' );

		if ( null !== $logout ) {
			$items['customer-logout'] = $logout;
		}

		return $items;
	}

	/**
	 * @return void
	 */
	public function render_endpoint(): void {
		$template = locate_template( 'woocommerce/myaccount/wishlist.php' );

		if ( $template ) {
			include $template;
			return;
		}

		echo '<p>' . esc_html__( 'Your wishlist is empty.', 'tailwindscore' ) . '</p>';
	}
}
