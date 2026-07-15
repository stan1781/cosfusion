<?php
declare(strict_types=1);

/**
 * Quick View WC-Ajax 处理器与 HTML 缓存。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 通过 wc-ajax 渲染 Quick View 商品 HTML。
 */
class Tailwindscore_Quick_View_Ajax {

	private const CACHE_META_KEY = '_ts_qv_cache_ver';

	/**
	 * @return void
	 */
	public function __construct() {
		if ( class_exists( 'WC_AJAX' ) ) {
			add_action( 'wc_ajax_tailwindscore_get_product_quick_view', array( $this, 'handle_request' ) );
		}

		add_action( 'save_post_product', array( $this, 'clear_cache' ), 10, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'clear_cache_for_product' ), 10, 1 );
		add_action( 'woocommerce_product_set_stock', array( $this, 'clear_cache_for_product' ), 10, 1 );
		add_filter( 'woocommerce_available_variation', array( $this, 'slim_variation_for_quick_view' ), 10, 3 );
	}

	/**
	 * 渲染 Quick View 时剥离重型变体字段（HTML 中 JSON 更小）。
	 *
	 * @param array<string, mixed> $data      变体数据。
	 * @param WC_Product           $product   父商品。
	 * @param WC_Product           $variation 变体。
	 * @return array<string, mixed>
	 */
	public function slim_variation_for_quick_view( $data, $product, $variation ): array {
		if ( ! is_array( $data ) || empty( $GLOBALS['tailwindscore_in_quick_view'] ) ) {
			return is_array( $data ) ? $data : array();
		}

		unset( $data['image'], $data['variation_description'], $data['weight_html'], $data['dimensions_html'] );

		// WC onFoundVariation 在 dimensions 为真时读取 dimensions_html — 避免 undefined.data 错误。
		$data['dimensions'] = false;
		$data['weight']       = false;

		return $data;
	}

	/**
	 * @return void
	 */
	public function handle_request(): void {
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( (string) $_POST['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->send_response( $product_id );
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return void
	 */
	private function send_response( int $product_id ): void {
		if ( ! defined( 'WC_DOING_AJAX' ) ) {
			define( 'WC_DOING_AJAX', true );
		}

		$bypass_cache = $product_id > 0 && $this->should_bypass_cache( $product_id );
		$cache_key    = $product_id > 0 ? self::cache_key( $product_id ) : '';

		if ( $product_id > 0 && ! $bypass_cache ) {
			$cached_html = get_transient( $cache_key );
			if ( is_string( $cached_html ) && '' !== $cached_html ) {
				wp_send_json_success( array( 'html' => $cached_html ) );
			}

			$lock_key = 'ts_qv_lock_' . $cache_key;
			if ( get_transient( $lock_key ) ) {
				// 另一请求正在渲染；短暂等待后重试缓存。
				usleep( 150000 );
				$cached_html = get_transient( $cache_key );
				if ( is_string( $cached_html ) && '' !== $cached_html ) {
					wp_send_json_success( array( 'html' => $cached_html ) );
				}
			}

			set_transient( $lock_key, '1', 30 );
		}

		$product = $product_id > 0 ? wc_get_product( $product_id ) : false;
		if ( ! $product instanceof WC_Product ) {
			$this->release_render_lock( $cache_key );
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Product not found.', 'tailwindscore' ),
				)
			);
		}

		if ( ! $product->is_purchasable() ) {
			$this->release_render_lock( $cache_key );
			wp_send_json_error(
				array(
					'message' => esc_html__( 'This product cannot be purchased.', 'tailwindscore' ),
				)
			);
		}

		$post = get_post( $product_id );
		if ( ! $post ) {
			$this->release_render_lock( $cache_key );
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Product data is unavailable.', 'tailwindscore' ),
				)
			);
		}

		setup_postdata( $post );

		$GLOBALS['tailwindscore_in_quick_view'] = true;

		ob_start();
		wc_get_template(
			'content-product-quick-view.php',
			array( 'product' => $product ),
			'',
			get_template_directory() . '/woocommerce/'
		);
		$html = (string) ob_get_clean();

		unset( $GLOBALS['tailwindscore_in_quick_view'] );

		wp_reset_postdata();

		$this->release_render_lock( $cache_key );

		if ( '' === trim( $html ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to render Quick View.', 'tailwindscore' ),
				)
			);
		}

		if ( $product_id > 0 && ! $bypass_cache ) {
			set_transient( $cache_key, $html, HOUR_IN_SECONDS );
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * @param string $cache_key 完整 transient 键。
	 * @return void
	 */
	private function release_render_lock( string $cache_key ): void {
		if ( '' === $cache_key ) {
			return;
		}
		delete_transient( 'ts_qv_lock_' . $cache_key );
	}

	/**
	 * 缓存键：商品 + 每商品版本 + 语言/货币/模块上下文。
	 *
	 * @param int $product_id 商品 ID。
	 * @return string
	 */
	public static function cache_key( int $product_id ): string {
		$product_id = absint( $product_id );
		$ver        = (int) get_post_meta( $product_id, self::CACHE_META_KEY, true );

		return 'ts_qv_v2_' . $product_id . '_' . $ver . '_' . self::cache_context_suffix();
	}

	/**
	 * @return string 语言、货币与模块模板版本的短哈希。
	 */
	private static function cache_context_suffix(): string {
		$currency = function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : '';

		return substr(
			md5(
				get_locale() . '|' . $currency . '|' . ( defined( 'TAILWINDSCORE_MODULE_QUICK_VIEW_VERSION' ) ? TAILWINDSCORE_MODULE_QUICK_VIEW_VERSION : '0' )
			),
			0,
			8
		);
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return bool
	 */
	private function should_bypass_cache( int $product_id ): bool {
		$components = get_post_meta( absint( $product_id ), 'wooco_components', true );
		return ! empty( $components );
	}

	/**
	 * 使商品缓存 HTML 失效（通过版本号递增覆盖所有语言/货币键）。
	 *
	 * @param int $post_id 商品文章 ID。
	 * @return void
	 */
	public static function invalidate_product_cache( int $post_id ): void {
		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return;
		}

		$ver     = (int) get_post_meta( $post_id, self::CACHE_META_KEY, true );
		$suffix  = self::cache_context_suffix();
		$old_key = 'ts_qv_v2_' . $post_id . '_' . $ver . '_' . $suffix;

		delete_transient( $old_key );
		delete_transient( 'ts_qv_lock_' . $old_key );
		delete_transient( 'ts_qv_v1_' . $post_id );
		delete_transient( 'child_qv_' . $post_id );

		update_post_meta( $post_id, self::CACHE_META_KEY, $ver + 1 );
	}

	/**
	 * @param int $post_id 商品文章 ID。
	 * @return void
	 */
	public function clear_cache( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		self::invalidate_product_cache( $post_id );
	}

	/**
	 * @param int|\WC_Product $product 商品 ID 或商品对象（库存/更新 Hook 传入 WC_Product）。
	 * @return void
	 */
	public function clear_cache_for_product( $product ): void {
		$product_id = self::resolve_product_cache_id( $product );

		if ( $product_id < 1 ) {
			return;
		}

		self::invalidate_product_cache( $product_id );
	}

	/**
	 * @param int|\WC_Product $product 商品 ID 或商品对象。
	 * @return int 变体返回父商品 ID，否则返回商品 ID。
	 */
	private static function resolve_product_cache_id( $product ): int {
		if ( $product instanceof WC_Product ) {
			if ( $product->is_type( 'variation' ) ) {
				return (int) $product->get_parent_id();
			}

			return (int) $product->get_id();
		}

		return (int) $product;
	}
}
