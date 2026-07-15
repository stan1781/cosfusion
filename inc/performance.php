<?php
declare(strict_types=1);

/**
 * 前台资源性能：在主题已替代 WooCommerce / 插件行为（Store API 购物车、经典通知）时，减少阻塞渲染的 CSS/JS。
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
 * 购物车 / 结账 / 账户页仍依赖 WooCommerce 旧版脚本包。
 */
function tailwindscore_needs_wc_core_script(): bool {
	return function_exists( 'is_cart' )
		&& ( is_cart() || is_checkout() || is_account_page() );
}

/**
 * 前台移除 jquery-migrate（WooCommerce + 主题不需要）。
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
 * 将 jQuery 加载到页脚而非 head（避免阻塞首屏绘制）。
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
 * 经典主题通知样式在 woo.scss 中 — 区块通知 CSS 未使用。
 */
function tailwindscore_performance_dequeue_wc_block_styles(): void {
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'wc-blocks-vendors-style' );
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_performance_dequeue_wc_block_styles', 100 );
add_action( 'wp_head', 'tailwindscore_performance_dequeue_wc_block_styles', 100 );

/**
 * 主题购物车 / 列表加购使用 Store API；WC 全局 add-to-cart 脚本冗余。
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
 * 当前请求是否仍有前台功能依赖 jQuery。
 */
function tailwindscore_theme_needs_jquery(): bool {
	if ( tailwindscore_needs_wc_core_script() ) {
		return true;
	}

	// Iconic Linked Variations 在单品页仍会加载小型 jQuery 脚本。
	if ( function_exists( 'tailwindscore_should_load_iconic_wlv_assets' ) && tailwindscore_should_load_iconic_wlv_assets() ) {
		return true;
	}

	return (bool) apply_filters( 'tailwindscore_theme_needs_jquery', false );
}

/**
 * 主题不再使用时移除 jQuery 与 wc-add-to-cart-variation。
 */
function tailwindscore_performance_dequeue_unused_jquery(): void {
	if ( is_admin() || tailwindscore_theme_needs_jquery() ) {
		return;
	}

	$handles = array(
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'wc-add-to-cart-variation',
		'wp-util',
		'underscore',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_performance_dequeue_unused_jquery', 1001 );

/**
 * 以 print media swap 方式加载非关键样式表，不阻塞渲染。
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
 * 输出 preload/modulepreload 提示，使关键资源与 HTML 并行获取。
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
 * 延迟加载 WooCommerce 订单归因脚本（首屏不需要）。
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

/**
 * 清理默认 WP 头部噪音，并禁用附件固定链接页。
 */
function tailwindscore_cleanup(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'init', 'tailwindscore_cleanup' );

/**
 * 附件固定链接页返回 404（媒体文件仍可通过 URL 访问）。
 */
function tailwindscore_disable_attachment_pages(): void {
	if ( ! is_attachment() ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'tailwindscore_disable_attachment_pages' );
