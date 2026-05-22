<?php
declare(strict_types=1);

/**
 * Enqueue WooCommerce variation scripts for the custom selector.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variation selector frontend dependencies.
 */
class Tailwindscore_Variation_Assets {

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 99 );
		add_filter( 'wc_get_template', array( $this, 'prefer_theme_variable_template' ), 20, 5 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Use theme variable.php even if wc-enhanced-tools is still active (its filter runs at 10).
	 *
	 * @param string $located       Path.
	 * @param string $template_name Template.
	 * @return string
	 */
	public function prefer_theme_variable_template( string $located, string $template_name ): string {
		if ( 'single-product/add-to-cart/variable.php' !== $template_name ) {
			return $located;
		}

		if ( ! tailwindscore_variations_enabled() ) {
			return $located;
		}

		$theme_file = get_template_directory() . '/woocommerce/single-product/add-to-cart/variable.php';
		if ( is_readable( $theme_file ) ) {
			return $theme_file;
		}

		return $located;
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$classes[] = 'ts-single-product';
		}
		return $classes;
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		if ( ! tailwindscore_variations_enabled() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'wc-add-to-cart-variation' );
	}
}
