<?php
declare(strict_types=1);

/**
 * 相关产品模块的 WooCommerce Hook。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 相关产品的过滤与单品输出。
 */
class Tailwindscore_Related_Products_Hooks {

	/**
	 * @var Tailwindscore_Related_Products_Engine|null
	 */
	private ?Tailwindscore_Related_Products_Engine $engine = null;

	public function __construct() {
		add_filter( 'woocommerce_related_products', array( $this, 'filter_related_products' ), 10, 2 );
		add_filter( 'woocommerce_output_related_products_args', array( $this, 'filter_related_args' ), 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'prevent_duplicate_related_products' ), 5 );
	}

	/**
	 * @return Tailwindscore_Related_Products_Engine
	 */
	private function engine(): Tailwindscore_Related_Products_Engine {
		if ( null === $this->engine ) {
			$this->engine = new Tailwindscore_Related_Products_Engine();
		}
		return $this->engine;
	}

	/**
	 * @param int[] $related_posts 相关产品 ID。
	 * @param int   $product_id    当前商品 ID。
	 * @return int[]
	 */
	public function filter_related_products( array $related_posts, int $product_id ): array {
		if ( ! Tailwindscore_Related_Products_Settings::is_enabled() ) {
			return $related_posts;
		}

		if ( ! is_product() || is_admin() ) {
			return $related_posts;
		}

		$cache_key = 'ts_rp_' . $product_id . '_' . Tailwindscore_Related_Products_Settings::get_cache_fingerprint();
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			$excluded = $this->engine()->get_excluded_product_ids( $product_id );
			if ( ! empty( $excluded ) ) {
				$cached = array_values( array_diff( array_map( 'absint', $cached ), $excluded ) );
			}
			return $cached;
		}

		$result = $this->engine()->build_related_list( $product_id, $related_posts );

		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * @param array<string, mixed> $args 相关商品循环参数。
	 * @return array<string, mixed>
	 */
	public function filter_related_args( array $args ): array {
		if ( ! Tailwindscore_Related_Products_Settings::is_enabled() ) {
			return $args;
		}

		if ( ! is_product() || is_admin() ) {
			return $args;
		}

		$args['posts_per_page'] = Tailwindscore_Related_Products_Settings::get_max_products();

		if ( Tailwindscore_Related_Products_Settings::randomize_results() ) {
			$args['orderby'] = 'rand';
		}

		return $args;
	}

	/**
	 * @return void
	 */
	public function prevent_duplicate_related_products(): void {
		if ( ! Tailwindscore_Related_Products_Settings::is_enabled() ) {
			return;
		}

		if ( ! is_product() || is_admin() ) {
			return;
		}

		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'output_related_products' ), 20 );
	}

	/**
	 * @return void
	 */
	public function output_related_products(): void {
		if ( ! Tailwindscore_Related_Products_Settings::is_enabled() ) {
			return;
		}

		if ( ! is_product() || is_admin() ) {
			return;
		}

		woocommerce_output_related_products();
	}
}
