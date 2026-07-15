<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 是否渲染移动端底部 Tab 栏。
 */
function tailwindscore_should_render_mobile_tab_bar(): bool {
	if ( is_admin() ) {
		return false;
	}

	$show = true;

	if ( function_exists( 'is_product' ) && is_product() ) {
		$show = false;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		$show = false;
	}

	if ( function_exists( 'tailwindscore_is_checkout_page' ) && tailwindscore_is_checkout_page() ) {
		$show = false;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$show = false;
	}

	if ( is_page( 'register' ) ) {
		$show = false;
	}

	if ( function_exists( 'tailwindscore_is_account_auth_surface' ) && tailwindscore_is_account_auth_surface() ) {
		$show = false;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		$show = false;
	}

	/**
	 * @param bool $show 是否渲染移动端 Tab 栏。
	 */
	return (bool) apply_filters( 'tailwindscore_should_render_mobile_tab_bar', $show );
}

/**
 * 是否渲染单品 sticky 购买栏。
 */
function tailwindscore_should_render_product_sticky_bar(): bool {
	if ( is_admin() ) {
		return false;
	}

	return function_exists( 'is_product' ) && is_product();
}

/**
 * "New" Tab 的 URL（新品页或最新商品）。
 */
function tailwindscore_new_arrivals_url(): string {
	$page = get_page_by_path( 'new-arrivals' );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return add_query_arg( 'orderby', 'date', wc_get_page_permalink( 'shop' ) );
	}

	return home_url( '/' );
}

/**
 * 将 theme_mod textarea 行解析为去空白字符串列表。
 *
 * @param string $key     theme_mod 键名。
 * @param string $default 默认多行值。
 * @return string[]
 */
function tailwindscore_mod_lines( string $key, string $default = '' ): array {
	$raw = (string) tailwindscore_mod( $key, $default );
	$lines = preg_split( '/\r\n|\r|\n/', $raw );

	if ( ! is_array( $lines ) ) {
		return array();
	}

	$out = array();

	foreach ( $lines as $line ) {
		$line = trim( (string) $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}

	return $out;
}

/**
 * 搜索弹窗 chip 的热门搜索词。
 *
 * @return string[]
 */
function tailwindscore_search_modal_hot_terms(): array {
	$default = "Prom Dresses\nEvening Dresses\nBridesmaid Dresses\nHomecoming Dresses";

	return tailwindscore_mod_lines( 'search_modal_hot_terms', $default );
}

/**
 * 搜索弹窗 chip 的热门商品分类。
 *
 * @return WP_Term[]
 */
function tailwindscore_search_modal_hot_categories(): array {
	$default = "Prom\nEvening\nBridesmaid\nPlus Size";
	$lines   = tailwindscore_mod_lines( 'search_modal_hot_categories', $default );
	$terms   = array();

	foreach ( $lines as $line ) {
		$term = get_term_by( 'slug', sanitize_title( $line ), 'product_cat' );

		if ( ! $term instanceof WP_Term ) {
			$term = get_term_by( 'name', $line, 'product_cat' );
		}

		if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
			$terms[] = $term;
		}
	}

	return $terms;
}

/**
 * 热门词的搜索 URL（可用时为 WooCommerce 商品搜索）。
 */
function tailwindscore_search_modal_term_url( string $term ): string {
	$args = array( 's' => $term );

	if ( function_exists( 'WC' ) ) {
		$args['post_type'] = 'product';
	}

	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * @param string[] $classes body class 列表。
 * @return string[]
 */
function tailwindscore_mobile_shell_body_class( array $classes ): array {
	if ( tailwindscore_should_render_mobile_tab_bar() ) {
		$classes[] = 'has-mobile-tab-bar';
	}

	if ( tailwindscore_should_render_product_sticky_bar() ) {
		$classes[] = 'has-product-sticky-bar';
	}

	return $classes;
}
add_filter( 'body_class', 'tailwindscore_mobile_shell_body_class' );

/**
 * 在 wp_footer 脚本前渲染 mobile shell 模板片段。
 */
function tailwindscore_render_mobile_shell(): void {
	if ( tailwindscore_should_render_mobile_tab_bar() ) {
		get_template_part( 'template-parts/navigation/mobile-tab-bar' );
	}

	if ( tailwindscore_should_render_product_sticky_bar() ) {
		get_template_part( 'template-parts/woocommerce/product-sticky-bar' );
	}
}
add_action( 'wp_footer', 'tailwindscore_render_mobile_shell', 5 );

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_mobile_shell_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_search_modal',
		array(
			'title'       => esc_html__( '搜索弹层', 'tailwindscore' ),
			'description' => esc_html__( '搜索对话框中显示的热搜与分类。', 'tailwindscore' ),
			'panel'       => $targets['header'],
			'priority'    => 45,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'search_modal_hot_terms',
			'label'    => esc_html__( '热门搜索', 'tailwindscore' ),
			'description' => esc_html__( '每行一个搜索词。', 'tailwindscore' ),
			'section'  => 'tailwindscore_search_modal',
			'default'  => "Prom Dresses\nEvening Dresses\nBridesmaid Dresses\nHomecoming Dresses",
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'textarea',
			'settings'    => 'search_modal_hot_categories',
			'label'       => esc_html__( '热门分类', 'tailwindscore' ),
			'description' => esc_html__( '每行一个分类名称或 slug。', 'tailwindscore' ),
			'section'     => 'tailwindscore_search_modal',
			'default'     => "Prom\nEvening\nBridesmaid\nPlus Size",
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_mobile_shell_kirki_register', 10, 2 );
