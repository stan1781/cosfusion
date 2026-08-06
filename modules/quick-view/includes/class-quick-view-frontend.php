<?php
declare(strict_types=1);

/**
 * Quick View 前台 Hook：资源、弹层壳、循环触发器。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quick View 前台集成。
 */
class Tailwindscore_Quick_View_Frontend {

	/**
	 * @return void
	 */
	public function __construct() {
		if ( ! Tailwindscore_Quick_View_Settings::is_enabled() ) {
			return;
		}

		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_footer', array( $this, 'render_modal_shell' ), 50 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_loop_add_to_cart_link' ), 20, 3 );
		add_filter( 'tailwindscore_runtime_config_parts', array( $this, 'filter_runtime_config' ) );
	}

	/**
	 * @param string[] $classes body 类名。
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( Tailwindscore_Quick_View_Settings::should_load_frontend() ) {
			$classes[] = 'quick-view-enabled';
		}

		return $classes;
	}

	/**
	 * @return void
	 */
	public function render_modal_shell(): void {
		if ( ! Tailwindscore_Quick_View_Settings::should_load_frontend() ) {
			return;
		}

		get_template_part( 'template-parts/woocommerce/quick-view-modal' );
	}

	/**
	 * @param string      $html    加购链接 HTML。
	 * @param WC_Product  $product 商品。
	 * @param array<mixed> $args   参数。
	 * @return string
	 */
	public function filter_loop_add_to_cart_link( string $html, $product, array $args ): string {
		if ( ! Tailwindscore_Quick_View_Settings::should_load_frontend() ) {
			return $html;
		}

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return $html;
		}

		$product_id = $product->get_id();
		$label      = __( 'Quick view', 'tailwindscore' );
		$icon       = function_exists( 'tailwindscore_icon' ) ? tailwindscore_icon( 'eye' ) : '';

		// Icon SVG 来自主题 map，信任输出。
		return sprintf(
			'<a href="#" class="btn-icon product-card-action product-card-action--quick-view quick-view-loop-trigger button" data-ts-quick-view data-product-id="%1$s" aria-label="%2$s"><span class="icon" aria-hidden="true">%3$s</span></a>',
			esc_attr( (string) $product_id ),
			esc_attr( $label ),
			$icon
		);
	}

	/**
	 * @param array<string, mixed> $parts 运行时配置片段。
	 * @return array<string, mixed>
	 */
	public function filter_runtime_config( array $parts ): array {
		if ( ! Tailwindscore_Quick_View_Settings::should_load_frontend() ) {
			return $parts;
		}

		$parts['quickView'] = array(
			'ajaxUrl' => add_query_arg( 'wc-ajax', 'tailwindscore_get_product_quick_view', home_url( '/' ) ),
			'i18n'    => array(
				'title'         => __( 'Quick view', 'tailwindscore' ),
				'loading'       => __( 'Loading…', 'tailwindscore' ),
				'error'         => __( 'Unable to load this product. Please try again.', 'tailwindscore' ),
				'networkError'  => __( 'Network error. Please try again.', 'tailwindscore' ),
				'chooseOptions' => __( 'Please choose product options.', 'woocommerce' ),
				'addedToCart'   => __( 'Added to your cart.', 'tailwindscore' ),
				'close'         => __( 'Close quick view', 'tailwindscore' ),
			),
		);

		return $parts;
	}
}
