<?php
declare(strict_types=1);

/**
 * 心愿单前台：按钮 markup、运行时配置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist frontend hooks.
 */
class Tailwindscore_Wishlist_Frontend {

	/**
	 * @return void
	 */
	public function __construct() {
		add_filter( 'tailwindscore_runtime_config_parts', array( $this, 'filter_runtime_config' ) );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_single_button' ), 15 );
	}

	/**
	 * @param array<string, mixed> $parts 运行时配置片段。
	 * @return array<string, mixed>
	 */
	public function filter_runtime_config( array $parts ): array {
		$parts['wishlist'] = array(
			'restUrl'    => esc_url_raw( rest_url( Tailwindscore_Wishlist_Rest::NAMESPACE . '/wishlist' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn' => is_user_logged_in(),
			'maxItems'   => Tailwindscore_Wishlist_Repository::MAX_ITEMS,
			'i18n'       => array(
				'add'         => __( 'Add to wishlist', 'tailwindscore' ),
				'remove'      => __( 'Remove from wishlist', 'tailwindscore' ),
				'added'       => __( 'Added to wishlist.', 'tailwindscore' ),
				'removed'     => __( 'Removed from wishlist.', 'tailwindscore' ),
				'limit'       => __( 'Your wishlist is full. Remove an item to add another.', 'tailwindscore' ),
				'error'       => __( 'Unable to update wishlist. Please try again.', 'tailwindscore' ),
				'addedToCart'  => __( 'Added to your cart.', 'tailwindscore' ),
				'itemCount'    => __( '%d items', 'tailwindscore' ),
				'itemCountOne' => __( '1 item', 'tailwindscore' ),
				'empty'        => __( 'Your wishlist is empty.', 'tailwindscore' ),
			),
		);

		return $parts;
	}

	/**
	 * @return void
	 */
	public function render_single_button(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// 可变品也只收藏父商品。
		$product_id = $product->is_type( 'variation' )
			? (int) $product->get_parent_id()
			: (int) $product->get_id();

		tailwindscore_render_wishlist_button(
			$product_id,
			array(
				'class' => 'wishlist-btn wishlist-btn--single',
			)
		);
	}
}

/**
 * 渲染心形按钮（仅父商品 ID）。
 *
 * @param int                  $product_id 父商品 ID。
 * @param array<string, mixed> $args       可选：class。
 * @return void
 */
function tailwindscore_render_wishlist_button( int $product_id, array $args = array() ): void {
	if ( $product_id < 1 ) {
		return;
	}

	$class   = isset( $args['class'] ) && is_string( $args['class'] ) ? $args['class'] : 'wishlist-btn';
	$classes = trim( 'btn-icon ' . $class );
	$label   = __( 'Add to wishlist', 'tailwindscore' );
	?>
	<button
		type="button"
		class="<?php echo esc_attr( $classes ); ?>"
		data-ts-wishlist
		data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
		aria-pressed="false"
		aria-label="<?php echo esc_attr( $label ); ?>"
	>
		<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG 来自主题图标 map。 ?></span>
	</button>
	<?php
}

/**
 * 头部 / 账户菜单中的心愿单数量徽章（零条时隐藏；供 JS 实时更新）。
 *
 * @return void
 */
function tailwindscore_render_account_wishlist_badge(): void {
	if ( ! is_user_logged_in() || ! class_exists( 'Tailwindscore_Wishlist_Repository' ) ) {
		return;
	}

	$count = count( Tailwindscore_Wishlist_Repository::get_items( get_current_user_id() ) );
	?>
	<span
		class="account-menu__badge"
		data-ts-wishlist-menu-badge
		aria-hidden="true"
		<?php echo $count > 0 ? '' : 'hidden'; ?>
	><?php echo esc_html( (string) max( 0, $count ) ); ?></span>
	<?php
}
