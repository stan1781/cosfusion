<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * TailwindScore 桥接：主题拥有 Ajax 搜索 UI；插件保留 Ajax 处理器与设置。
 */
function tailwindscore_ajax_search_is_active(): bool {
	return class_exists( 'Ajax_Search' ) && Ajax_Search::is_enabled();
}

/**
 * @return void
 */
function tailwindscore_ajax_search_init(): void {
	if ( ! tailwindscore_ajax_search_is_active() ) {
		return;
	}

	add_filter( 'wc_enhanced_tools_ajax_search_enqueue_assets', '__return_false' );
	add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_ajax_search_runtime_config' );
	add_filter( 'body_class', 'tailwindscore_ajax_search_body_class' );
	add_action( 'wp_enqueue_scripts', 'tailwindscore_ajax_search_dequeue_plugin_assets', 999 );
}
add_action( 'after_setup_theme', 'tailwindscore_ajax_search_init', 30 );

/**
 * @param array<string, mixed> $parts 运行时配置片段。
 * @return array<string, mixed>
 */
function tailwindscore_ajax_search_runtime_config( array $parts ): array {
	if ( ! tailwindscore_ajax_search_is_active() || tailwindscore_is_checkout_page() ) {
		return $parts;
	}

	$settings = Ajax_Search::get_ajax_search_settings();
	$min_len  = isset( $settings['min_search_length'] ) ? (int) $settings['min_search_length'] : 3;

	$parts['ajaxSearch'] = array(
		'restUrl'      => rest_url( 'wc-enhanced-tools/v1/product-search' ),
		'transport'    => 'rest',
		'minLength'    => $min_len,
		'delay'        => isset( $settings['search_delay'] ) ? (int) $settings['search_delay'] : 1000,
		'maxResults'   => isset( $settings['max_results'] ) ? (int) $settings['max_results'] : 5,
		'showLoading'  => ! empty( $settings['show_loading_animation'] ),
		'i18n'         => array(
			'searching'  => __( 'Searching…', 'tailwindscore' ),
			'minChars'   => sprintf(
				/* translators: %d: minimum character count */
				__( 'Please enter at least %d characters', 'tailwindscore' ),
				$min_len
			),
			'noResults'  => __( 'No products found', 'tailwindscore' ),
			'error'      => __( 'Search error. Please try again.', 'tailwindscore' ),
			'viewAll'    => __( 'View all results', 'tailwindscore' ),
		),
	);

	return $parts;
}

/**
 * @param string[] $classes body class 列表。
 * @return string[]
 */
function tailwindscore_ajax_search_body_class( array $classes ): array {
	if ( tailwindscore_ajax_search_is_active() && ! tailwindscore_is_checkout_page() ) {
		$classes[] = 'ts-ajax-search-enabled';
	}

	return $classes;
}

/**
 * @return void
 */
function tailwindscore_ajax_search_dequeue_plugin_assets(): void {
	wp_dequeue_style( 'wc-enhanced-ajax-search' );
	wp_dequeue_script( 'wc-enhanced-ajax-search' );
	wp_deregister_style( 'wc-enhanced-ajax-search' );
	wp_deregister_script( 'wc-enhanced-ajax-search' );
}

/**
 * Ajax 搜索启用时头部搜索框占位符。
 */
function tailwindscore_ajax_search_placeholder(): string {
	if ( tailwindscore_ajax_search_is_active() ) {
		$settings = Ajax_Search::get_ajax_search_settings();
		if ( ! empty( $settings['search_placeholder'] ) ) {
			return (string) $settings['search_placeholder'];
		}
	}

	return tailwindscore_search_placeholder_text();
}
