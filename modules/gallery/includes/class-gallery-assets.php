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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_photoswipe_skin_styles' ), 101 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_variation_scripts' ), 102 );
		add_action( 'wp_footer', array( $this, 'render_photoswipe_markup' ), 10 );
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
	 * PhotoSwipe layout + default skin from WooCommerce (correct sprite URLs in dev and prod).
	 * Scripts stay dequeued; UI is bundled via Vite.
	 *
	 * @return void
	 */
	public function enqueue_photoswipe_skin_styles(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'WC' ) ) {
			return;
		}

		$base = WC()->plugin_url() . '/assets/css/photoswipe/';
		$ver  = defined( 'WC_VERSION' ) ? WC_VERSION : null;

		wp_enqueue_style( 'tailwindscore-photoswipe', $base . 'photoswipe.css', array(), $ver );
		wp_enqueue_style(
			'tailwindscore-photoswipe-skin',
			$base . 'default-skin/default-skin.css',
			array( 'tailwindscore-photoswipe' ),
			$ver
		);

		$skin_svg = $base . 'default-skin/default-skin.svg';
		wp_add_inline_style(
			'tailwindscore-photoswipe-skin',
			sprintf(
				'.pswp__button,.pswp__button--arrow--left:before,.pswp__button--arrow--right:before{background-image:url(%1$s);}',
				esc_url( $skin_svg )
			)
		);
	}

	/**
	 * Keep variation form behavior (price, variation_id) without wc-single-product bundle.
	 *
	 * @return void
	 */
	public function enqueue_variation_scripts(): void {
		// Variation matching is handled by variation-engine.ts (Vite bundle).
	}

	/**
	 * PhotoSwipe root markup for theme-bundled lightbox (scripts/styles via Vite).
	 *
	 * @return void
	 */
	public function render_photoswipe_markup(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		wc_get_template( 'single-product/photoswipe.php' );
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

// Product page config (gallery + variations) is localized in inc/enqueue.php via tailwindscore_product_page_config().
