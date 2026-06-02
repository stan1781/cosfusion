<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Whether the mobile bottom tab bar should render.
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
	 * @param bool $show Whether to render the mobile tab bar.
	 */
	return (bool) apply_filters( 'tailwindscore_should_render_mobile_tab_bar', $show );
}

/**
 * Whether the product sticky purchase bar should render.
 */
function tailwindscore_should_render_product_sticky_bar(): bool {
	if ( is_admin() ) {
		return false;
	}

	return function_exists( 'is_product' ) && is_product();
}

/**
 * URL for the "New" tab (new arrivals page or latest products).
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
 * Parse theme_mod textarea lines into a trimmed string list.
 *
 * @param string $key     Theme mod key.
 * @param string $default Default multiline value.
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
 * Hot search terms for the search modal chips.
 *
 * @return string[]
 */
function tailwindscore_search_modal_hot_terms(): array {
	$default = "Prom Dresses\nEvening Dresses\nBridesmaid Dresses\nHomecoming Dresses";

	return tailwindscore_mod_lines( 'search_modal_hot_terms', $default );
}

/**
 * Hot product categories for the search modal chips.
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
 * Search URL for a hot term (WooCommerce product search when available).
 */
function tailwindscore_search_modal_term_url( string $term ): string {
	$args = array( 's' => $term );

	if ( function_exists( 'WC' ) ) {
		$args['post_type'] = 'product';
	}

	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function tailwindscore_mobile_shell_body_class( array $classes ): array {
	if ( tailwindscore_should_render_mobile_tab_bar() ) {
		$classes[] = 'ts-has-mobile-tab-bar';
	}

	if ( tailwindscore_should_render_product_sticky_bar() ) {
		$classes[] = 'ts-has-product-sticky-bar';
	}

	return $classes;
}
add_filter( 'body_class', 'tailwindscore_mobile_shell_body_class' );

/**
 * Render mobile shell template parts before wp_footer scripts.
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
 * @param string $cfg   Kirki config id.
 * @param string $panel Kirki panel id.
 */
function tailwindscore_mobile_shell_kirki_register( string $cfg, string $panel ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_search_modal',
		array(
			'title'       => esc_html__( 'Search Modal', 'tailwindscore' ),
			'description' => esc_html__( 'Hot searches and categories shown in the search dialog.', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 45,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'textarea',
			'settings' => 'search_modal_hot_terms',
			'label'    => esc_html__( 'Hot searches', 'tailwindscore' ),
			'description' => esc_html__( 'One search term per line.', 'tailwindscore' ),
			'section'  => 'tailwindscore_search_modal',
			'default'  => "Prom Dresses\nEvening Dresses\nBridesmaid Dresses\nHomecoming Dresses",
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'textarea',
			'settings'    => 'search_modal_hot_categories',
			'label'       => esc_html__( 'Hot categories', 'tailwindscore' ),
			'description' => esc_html__( 'One category name or slug per line.', 'tailwindscore' ),
			'section'     => 'tailwindscore_search_modal',
			'default'     => "Prom\nEvening\nBridesmaid\nPlus Size",
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_mobile_shell_kirki_register', 10, 2 );
