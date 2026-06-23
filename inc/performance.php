<?php
declare(strict_types=1);

/**
 * Frontend asset performance: reduce render-blocking CSS/JS where the theme
 * already replaces WooCommerce / plugin behaviour (Store API cart, classic notices).
 */

/**
 * @return string[]
 */
function tailwindscore_async_style_handles(): array {
	$handles = array( 'fashistyles-style' );

	if ( is_front_page() ) {
		$handles[] = 'tailwindscore-woo';
	}

	return apply_filters( 'tailwindscore_async_style_handles', $handles );
}

/**
 * Cart/checkout/account still rely on WooCommerce's legacy script bundle.
 */
function tailwindscore_needs_wc_core_script(): bool {
	return function_exists( 'is_cart' )
		&& ( is_cart() || is_checkout() || is_account_page() );
}

/**
 * Drop jquery-migrate on the frontend (WooCommerce + theme do not require it).
 */
function tailwindscore_performance_remove_jquery_migrate( WP_Scripts $scripts ): void {
	if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
		return;
	}

	$deps = $scripts->registered['jquery']->deps;
	$scripts->registered['jquery']->deps = array_values(
		array_diff( $deps, array( 'jquery-migrate' ) )
	);
}
add_action( 'wp_default_scripts', 'tailwindscore_performance_remove_jquery_migrate' );

/**
 * Load jQuery in the footer instead of the head (unblocks first paint).
 */
function tailwindscore_performance_move_jquery_to_footer(): void {
	if ( is_admin() ) {
		return;
	}

	foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $handle ) {
		wp_scripts()->add_data( $handle, 'group', 1 );
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_performance_move_jquery_to_footer', 1 );

/**
 * Classic theme notice styles live in woo.scss — block notice CSS is unused.
 */
function tailwindscore_performance_dequeue_wc_block_styles(): void {
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'wc-blocks-vendors-style' );
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_performance_dequeue_wc_block_styles', 100 );
add_action( 'wp_head', 'tailwindscore_performance_dequeue_wc_block_styles', 100 );

/**
 * Theme cart / loop add-to-cart uses the Store API; WC's global add-to-cart script is redundant.
 */
function tailwindscore_performance_dequeue_legacy_wc_scripts(): void {
	if ( ! function_exists( 'WC' ) || tailwindscore_needs_wc_core_script() ) {
		return;
	}

	wp_dequeue_script( 'woocommerce' );
	wp_dequeue_script( 'wc-add-to-cart' );
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_performance_dequeue_legacy_wc_scripts', 99 );

/**
 * Dequeue third-party plugin assets outside the views that need them.
 */
function tailwindscore_performance_dequeue_third_party_assets(): void {
	if ( function_exists( 'is_product' ) && ! is_product() ) {
		wp_dequeue_style( 'iconic-wlv' );
		wp_dequeue_script( 'iconic-wlv' );
		wp_deregister_style( 'iconic-wlv' );
		wp_deregister_script( 'iconic-wlv' );
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_performance_dequeue_third_party_assets', 999 );

/**
 * Load non-critical stylesheets without blocking render (print media swap).
 */
function tailwindscore_performance_async_style_loader_tag( string $html, string $handle, string $href, string $media ): string {
	if ( ! in_array( $handle, tailwindscore_async_style_handles(), true ) ) {
		return $html;
	}

	if ( false !== strpos( $html, "media='all'" ) ) {
		return str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html );
	}

	if ( false !== strpos( $html, 'media="all"' ) ) {
		return str_replace( 'media="all"', 'media="print" onload="this.media=\'all\'"', $html );
	}

	return $html;
}
add_filter( 'style_loader_tag', 'tailwindscore_performance_async_style_loader_tag', 10, 4 );

/**
 * @return string[]
 */
function tailwindscore_performance_font_preload_files(): array {
	if ( ! class_exists( 'Tailwindscore_Typography_Settings' ) || ! class_exists( 'Tailwindscore_Typography_Fonts' ) ) {
		return array();
	}

	$slugs = array(
		Tailwindscore_Typography_Settings::get_body_font_slug(),
		Tailwindscore_Typography_Settings::get_heading_font_slug(),
	);
	$files = array();

	foreach ( array_unique( $slugs ) as $slug ) {
		if ( 'system' === $slug ) {
			continue;
		}

		$font = Tailwindscore_Typography_Fonts::get_font( $slug );
		if ( null === $font || empty( $font['faces'] ) ) {
			continue;
		}

		foreach ( $font['faces'] as $face ) {
			$file = isset( $face['file'] ) ? (string) $face['file'] : '';
			if ( '' === $file ) {
				continue;
			}

			$path = get_template_directory() . '/assets/fonts/' . ltrim( $file, '/' );
			if ( is_readable( $path ) ) {
				$files[] = $file;
			}
		}
	}

	return array_values( array_unique( $files ) );
}

/**
 * @return string[]
 */
function tailwindscore_performance_resource_hint_entries(): array {
	if ( tailwindscore_is_vite_dev_enabled() ) {
		return array();
	}

	$entries = array( 'src/scss/app.scss', 'src/ts/app.ts' );

	if ( tailwindscore_is_woo_page() ) {
		$entries[] = 'src/scss/woo.scss';
	}

	if ( is_front_page() ) {
		$entries[] = 'src/ts/home/index.ts';
	}

	return apply_filters( 'tailwindscore_resource_hint_entries', $entries );
}

/**
 * Emit preload/modulepreload hints so critical assets fetch in parallel with HTML.
 */
function tailwindscore_performance_resource_hints(): void {
	if ( is_admin() || tailwindscore_is_vite_dev_enabled() ) {
		return;
	}

	$preloaded = array();

	$preload = static function ( string $url, string $as ) use ( &$preloaded ): void {
		if ( '' === $url || isset( $preloaded[ $url ] ) ) {
			return;
		}

		$preloaded[ $url ] = true;

		if ( 'script-module' === $as ) {
			printf( '<link rel="modulepreload" href="%s" />' . "\n", esc_url( $url ) );
			return;
		}

		$attrs = array(
			'rel'  => 'preload',
			'href' => esc_url( $url ),
			'as'   => esc_attr( $as ),
		);

		if ( 'font' === $as ) {
			$attrs['type'] = 'font/woff2';
			$attrs['crossorigin'] = '';
		}

		echo '<link';
		foreach ( $attrs as $name => $value ) {
			if ( '' === $value ) {
				printf( ' %s', esc_attr( $name ) );
				continue;
			}
			printf( ' %s="%s"', esc_attr( $name ), $value );
		}
		echo ' />' . "\n";
	};

	foreach ( tailwindscore_performance_resource_hint_entries() as $entry_key ) {
		foreach ( tailwindscore_manifest_bundle_files( $entry_key ) as $file ) {
			$url = tailwindscore_dist_url( $file );
			$as  = str_ends_with( $file, '.css' ) ? 'style' : 'script-module';
			$preload( $url, $as );
		}
	}

	foreach ( tailwindscore_performance_font_preload_files() as $file ) {
		$preload(
			get_template_directory_uri() . '/assets/fonts/' . ltrim( $file, '/' ),
			'font'
		);
	}
}
add_action( 'wp_head', 'tailwindscore_performance_resource_hints', 1 );

/**
 * Defer WooCommerce order-attribution scripts (not needed for first paint).
 */
function tailwindscore_performance_defer_script_loader_tag( string $tag, string $handle, string $src ): string {
	$defer_handles = apply_filters(
		'tailwindscore_defer_script_handles',
		array( 'sourcebuster-js', 'wc-order-attribution' )
	);

	if ( ! in_array( $handle, $defer_handles, true ) || false !== strpos( $tag, ' defer' ) ) {
		return $tag;
	}

	return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'tailwindscore_performance_defer_script_loader_tag', 10, 3 );
