<?php
declare(strict_types=1);

/**
 * Quick View WC-Ajax handler and HTML cache.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders Quick View product HTML via wc-ajax.
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
	 * Strip heavy variation fields when rendering Quick View (smaller JSON in HTML).
	 *
	 * @param array<string, mixed> $data      Variation data.
	 * @param WC_Product           $product   Parent product.
	 * @param WC_Product           $variation Variation.
	 * @return array<string, mixed>
	 */
	public function slim_variation_for_quick_view( $data, $product, $variation ): array {
		if ( ! is_array( $data ) || empty( $GLOBALS['tailwindscore_in_quick_view'] ) ) {
			return is_array( $data ) ? $data : array();
		}

		unset( $data['image'], $data['variation_description'], $data['weight_html'], $data['dimensions_html'] );

		// WC onFoundVariation reads dimensions_html when dimensions is truthy — avoid undefined.data errors.
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
	 * @param int $product_id Product ID.
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
				// Another request is rendering; wait briefly and retry cache.
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
	 * @param string $cache_key Full transient key.
	 * @return void
	 */
	private function release_render_lock( string $cache_key ): void {
		if ( '' === $cache_key ) {
			return;
		}
		delete_transient( 'ts_qv_lock_' . $cache_key );
	}

	/**
	 * Cache key: product + per-product version + locale/currency/module context.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function cache_key( int $product_id ): string {
		$product_id = absint( $product_id );
		$ver        = (int) get_post_meta( $product_id, self::CACHE_META_KEY, true );

		return 'ts_qv_v2_' . $product_id . '_' . $ver . '_' . self::cache_context_suffix();
	}

	/**
	 * @return string Short hash for locale, currency, and module template version.
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
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function should_bypass_cache( int $product_id ): bool {
		$components = get_post_meta( absint( $product_id ), 'wooco_components', true );
		return ! empty( $components );
	}

	/**
	 * Invalidate cached HTML for a product (all locale/currency keys via version bump).
	 *
	 * @param int $post_id Product post ID.
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
	 * @param int $post_id Product post ID.
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
	 * @param int $product_id WooCommerce product ID.
	 * @return void
	 */
	public function clear_cache_for_product( int $product_id ): void {
		self::invalidate_product_cache( $product_id );
	}
}
