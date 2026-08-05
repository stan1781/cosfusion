<?php
declare(strict_types=1);

/**
 * 取消入队 WC 默认画廊；本地化商品画廊配置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 画廊资源与 Hook。
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

		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
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
	 * PhotoSwipe 布局与 WooCommerce 默认皮肤（开发与生产环境 sprite URL 正确）。
	 * 脚本保持 dequeue；UI 由 Vite 打包。
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
	 * 保留变体表单行为（price、variation_id），不加载 wc-single-product bundle。
	 *
	 * @return void
	 */
	public function enqueue_variation_scripts(): void {
		// 变体匹配由 variation-engine.ts（Vite bundle）处理。
	}

	/**
	 * 主题打包 lightbox 的 PhotoSwipe 根 markup（脚本/样式经 Vite）。
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
	 * @param string[] $classes body 类名。
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$classes[] = 'product-gallery-active';
		}
		return $classes;
	}
}

// 商品页配置（画廊 + 变体）在 inc/enqueue.php 中通过 tailwindscore_product_page_config() 本地化。
