<?php
declare(strict_types=1);

/**
 * Dequeue WC default gallery; localize product gallery config.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gallery assets and hooks.
 */
class Tailwindscore_Gallery_Assets {

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'remove_wc_gallery_support' ), 100 );
		add_action( 'wp', array( $this, 'remove_default_gallery_render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_wc_gallery_scripts' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_variation_scripts' ), 101 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * @return void
	 */
	public function remove_wc_gallery_support(): void {
		remove_theme_support( 'wc-product-gallery-zoom' );
		remove_theme_support( 'wc-product-gallery-lightbox' );
		remove_theme_support( 'wc-product-gallery-slider' );
	}

	/**
	 * @return void
	 */
	public function remove_default_gallery_render(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	}

	/**
	 * @return void
	 */
	public function dequeue_wc_gallery_scripts(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		wp_dequeue_script( 'wc-single-product' );
		wp_deregister_script( 'wc-single-product' );
		wp_dequeue_script( 'flexslider' );
		wp_deregister_script( 'flexslider' );
		wp_dequeue_script( 'zoom' );
		wp_deregister_script( 'zoom' );
		wp_dequeue_style( 'photoswipe-default-skin' );
		wp_dequeue_style( 'photoswipe' );
		wp_dequeue_script( 'photoswipe-ui-default' );
		wp_dequeue_script( 'photoswipe' );
	}

	/**
	 * Keep variation form behavior (price, variation_id) without wc-single-product bundle.
	 *
	 * @return void
	 */
	public function enqueue_variation_scripts(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		wp_enqueue_script( 'wc-add-to-cart-variation' );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$classes[] = 'ts-product-gallery-active';
		}
		return $classes;
	}
}

/**
 * Localize window.tailwindscoreProduct on single product pages.
 */
function tailwindscore_localize_product_script(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	wp_add_inline_script(
		'tailwindscore-app',
		sprintf(
			'window.tailwindscoreProduct = %s;',
			wp_json_encode( tailwindscore_product_gallery_config() )
		),
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_localize_product_script', 102 );
