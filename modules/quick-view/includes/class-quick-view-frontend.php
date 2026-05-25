<?php
declare(strict_types=1);

/**
 * Quick View frontend hooks: assets, modal shell, loop triggers.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quick View frontend integration.
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'wp_footer', array( $this, 'render_modal_shell' ), 50 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_loop_add_to_cart_link' ), 20, 3 );
		add_filter( 'tailwindscore_runtime_config_parts', array( $this, 'filter_runtime_config' ) );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( Tailwindscore_Quick_View_Settings::should_load_frontend() ) {
			$classes[] = 'ts-quick-view-enabled';
		}

		return $classes;
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( ! Tailwindscore_Quick_View_Settings::should_load_frontend() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'wc-add-to-cart-variation' );
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
	 * @param string      $html    Add to cart link HTML.
	 * @param WC_Product  $product Product.
	 * @param array<mixed> $args   Args.
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
		$label      = esc_html__( 'Quick view', 'tailwindscore' );

		if ( preg_match( '/class="([^"]*)"/', $html, $class_match ) ) {
			$classes = $class_match[1] . ' ts-quick-view-loop-trigger';
			$html    = preg_replace( '/class="[^"]*"/', 'class="' . esc_attr( $classes ) . '"', $html, 1 );
		}

		$html = preg_replace( '/href="[^"]*"/', 'href="#"', $html, 1 );
		$html = preg_replace( '/>.*?<\/a>/s', '>' . $label . '</a>', $html, 1 );

		if ( false === strpos( $html, 'data-ts-quick-view' ) ) {
			$html = str_replace( '<a ', '<a data-ts-quick-view data-product-id="' . esc_attr( (string) $product_id ) . '" ', $html );
		}

		return $html;
	}

	/**
	 * @param array<string, mixed> $parts Runtime config fragments.
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
