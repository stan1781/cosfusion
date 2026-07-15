<?php
declare(strict_types=1);

/**
 * 为自定义选择器入队 WooCommerce 变体脚本。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 变体选择器前台依赖。
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
	 * 即使 wc-enhanced-tools 仍启用也使用主题 variable.php（其 filter 在优先级 10 运行）。
	 *
	 * @param string $located       路径。
	 * @param string $template_name 模板。
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
	 * @param string[] $classes body 类名。
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
		// 变体 UI 由 variation-engine.ts（Vite bundle）处理。
	}
}
