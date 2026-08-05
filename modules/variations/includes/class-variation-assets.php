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
		add_filter( 'woocommerce_available_variation', array( $this, 'ensure_variation_price_html' ), 10, 3 );
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
	 * 同价变体时 Woo 会把 price_html 置空；ATC 按钮需要始终能读到价格文案。
	 *
	 * @param array<string, mixed> $data      变体 payload。
	 * @param WC_Product           $product   父商品。
	 * @param WC_Product           $variation 变体。
	 * @return array<string, mixed>
	 */
	public function ensure_variation_price_html( array $data, $product, $variation ): array {
		unset( $product );

		if ( ! empty( $data['price_html'] ) ) {
			return $data;
		}

		if ( ! $variation instanceof WC_Product ) {
			return $data;
		}

		$html = $variation->get_price_html();
		if ( '' !== $html && false !== $html ) {
			$data['price_html'] = '<span class="price">' . $html . '</span>';
		}

		return $data;
	}

	/**
	 * @param string[] $classes body 类名。
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$classes[] = 'single-product';
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
