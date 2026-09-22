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

		if ( ! Tailwindscore_Related_Products_Settings::is_carousel_enabled() ) {
			woocommerce_output_related_products();
			return;
		}

		$this->output_related_products_carousel();
	}

	/**
	 * 以共享产品轮播壳层输出相关产品。
	 *
	 * @return void
	 */
	private function output_related_products_carousel(): void {
		if ( ! function_exists( 'wc_get_related_products' ) || ! function_exists( 'tailwindscore_render_products_carousel_shell' ) ) {
			woocommerce_output_related_products();
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = (int) $product->get_id();
		$limit      = Tailwindscore_Related_Products_Settings::get_max_products();
		$exclude    = $this->engine()->get_excluded_product_ids( $product_id );
		$ids        = wc_get_related_products( $product_id, $limit, $exclude );
		$ids        = array_values( array_filter( array_map( 'absint', is_array( $ids ) ? $ids : array() ) ) );

		if ( array() === $ids ) {
			return;
		}

			$markup = tailwindscore_render_products_carousel_shell(
				array(
					'product_ids'        => $ids,
					'title'              => __( 'Related products', 'woocommerce' ),
					'columns_mobile'     => function_exists( 'tailwindscore_get_products_carousel_columns_mobile' )
						? tailwindscore_get_products_carousel_columns_mobile()
						: 1.5,
					'columns_desktop'    => function_exists( 'tailwindscore_get_products_carousel_columns_desktop' )
						? tailwindscore_get_products_carousel_columns_desktop()
						: 3.0,
					'columns_wide'       => function_exists( 'tailwindscore_get_products_carousel_columns_wide' )
						? tailwindscore_get_products_carousel_columns_wide()
						: 4.0,
					'section_class'      => 'related products product-carousel--related',
					'section_id'         => 'ts-related-products-title',
					'section_data_attrs' => array(
						'data-ts-related-products' => '',
					),
					'title_class'        => 'section-title--inline',
				)
			);

		if ( '' === trim( $markup ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Carousel shell markup from theme template.
		echo $markup;
	}
}
