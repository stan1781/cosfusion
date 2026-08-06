<?php
declare(strict_types=1);

function tailwindscore_is_vite_dev_flag_on(): bool {
	return defined( 'TAILWINDSCORE_VITE_DEV' ) && true === TAILWINDSCORE_VITE_DEV;
}

/**
 * Vite 开发服务器是否可达（每请求缓存）。
 */
function tailwindscore_is_vite_dev_server_live(): bool {
	static $live = null;

	if ( null !== $live ) {
		return $live;
	}

	$response = wp_remote_get(
		trailingslashit( tailwindscore_vite_dev_server_url() ) . '@vite/client',
		array(
			'timeout'   => 0.35,
			'sslverify' => false,
		)
	);

	$live = ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );

	return $live;
}

/**
 * 仅当开关开启且开发服务器运行时使用 Vite 开发资源。
 */
function tailwindscore_is_vite_dev_enabled(): bool {
	return tailwindscore_is_vite_dev_flag_on() && tailwindscore_is_vite_dev_server_live();
}

function tailwindscore_vite_dev_server_url(): string {
	return 'http://localhost:5173';
}

function tailwindscore_module_script_handles(): array {
	return array(
		'tailwindscore-vite-client',
		'tailwindscore-app',
	);
}

function tailwindscore_runtime_config_inline(): string {
	$parts = apply_filters( 'tailwindscore_runtime_config_parts', array() );

	$inline = sprintf(
		'window.tailwindscoreCart = %s;',
		wp_json_encode( tailwindscore_purchase_flow_config() )
	);

	if ( isset( $parts['accountAuth'] ) ) {
		$inline .= sprintf(
			'window.tailwindscoreAccountAuth = %s;',
			wp_json_encode( $parts['accountAuth'] )
		);
	}

	if ( isset( $parts['orderProcess'] ) ) {
		$inline .= sprintf(
			'window.wcOrderProcessFrontend = %s;',
			wp_json_encode( $parts['orderProcess'] )
		);
	}

	if ( isset( $parts['quickView'] ) ) {
		$inline .= sprintf(
			'window.tailwindscoreQuickView = %s;',
			wp_json_encode( $parts['quickView'] )
		);
	}

	if ( isset( $parts['wishlist'] ) ) {
		$inline .= sprintf(
			'window.tailwindscoreWishlist = %s;',
			wp_json_encode( $parts['wishlist'] )
		);
	}

	if ( isset( $parts['ajaxSearch'] ) ) {
		$inline .= sprintf(
			'window.tailwindscoreAjaxSearch = %s;',
			wp_json_encode( $parts['ajaxSearch'] )
		);
	}

	if ( isset( $parts['mailnest'] ) && is_array( $parts['mailnest'] ) ) {
		if ( isset( $parts['mailnest']['subscribe'] ) ) {
			$inline .= sprintf(
				'window.wcSubscribeAjax = %s;',
				wp_json_encode( $parts['mailnest']['subscribe'] )
			);
		}
		if ( isset( $parts['mailnest']['form'] ) ) {
			$inline .= sprintf(
				'window.wcMailnestFormAjax = %s;',
				wp_json_encode( $parts['mailnest']['form'] )
			);
		}
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		if ( function_exists( 'tailwindscore_product_page_config' ) ) {
			$product_id = (int) get_queried_object_id();
			$inline    .= sprintf(
				'window.tailwindscoreProduct = %s;',
				wp_json_encode( tailwindscore_product_page_config( $product_id > 0 ? $product_id : null ) )
			);
		} elseif ( function_exists( 'tailwindscore_product_gallery_config' ) ) {
			$inline .= sprintf(
				'window.tailwindscoreProduct = %s;',
				wp_json_encode( tailwindscore_product_gallery_config() )
			);
		}
	}

	return $inline;
}

function tailwindscore_enqueue_runtime_config(): void {
	wp_register_script( 'tailwindscore-runtime-config', false, array(), null, true );
	wp_enqueue_script( 'tailwindscore-runtime-config' );
	wp_add_inline_script( 'tailwindscore-runtime-config', tailwindscore_runtime_config_inline() );
}

function tailwindscore_localize_app_script(): void {
	// WordPress 对 module 脚本不会可靠输出 wp_add_inline_script 内容。
	// 运行时配置改由 tailwindscore-runtime-config 输出。
}

function tailwindscore_manifest_path(): string {
	return get_template_directory() . '/assets/dist/manifest.json';
}

function tailwindscore_manifest(): array {
	static $manifest = null;

	if ( null !== $manifest ) {
		return $manifest;
	}

	$path = tailwindscore_manifest_path();

	if ( ! file_exists( $path ) ) {
		$manifest = array();
		return $manifest;
	}

	$data = json_decode( (string) file_get_contents( $path ), true );
	$manifest = is_array( $data ) ? $data : array();

	return $manifest;
}

function tailwindscore_dist_url( string $relative_file ): string {
	return get_template_directory_uri() . '/assets/dist/' . ltrim( $relative_file, '/' );
}

/**
 * 收集 manifest 条目的构建资源路径（file、css、嵌套 imports）。
 *
 * @return string[]
 */
function tailwindscore_manifest_bundle_files( string $entry_key ): array {
	$manifest = tailwindscore_manifest();

	if ( empty( $manifest[ $entry_key ] ) ) {
		return array();
	}

	$files = array();
	$seen  = array();

	$walk = static function ( string $key ) use ( &$walk, &$files, &$seen, $manifest ): void {
		if ( isset( $seen[ $key ] ) || empty( $manifest[ $key ] ) ) {
			return;
		}

		$seen[ $key ] = true;
		$entry        = $manifest[ $key ];

		if ( ! empty( $entry['file'] ) && is_string( $entry['file'] ) ) {
			$files[] = $entry['file'];
		}

		if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
			foreach ( $entry['css'] as $css_file ) {
				if ( is_string( $css_file ) && '' !== $css_file ) {
					$files[] = $css_file;
				}
			}
		}

		if ( ! empty( $entry['imports'] ) && is_array( $entry['imports'] ) ) {
			foreach ( $entry['imports'] as $import_key ) {
				if ( is_string( $import_key ) ) {
					$walk( $import_key );
				}
			}
		}
	};

	$walk( $entry_key );

	return array_values( array_unique( $files ) );
}

function tailwindscore_enqueue_manifest_style( string $handle, string $entry, array $deps = array() ): void {
	$manifest = tailwindscore_manifest();

	if ( empty( $manifest[ $entry ]['file'] ) ) {
		return;
	}

	wp_enqueue_style(
		$handle,
		get_template_directory_uri() . '/assets/dist/' . $manifest[ $entry ]['file'],
		$deps,
		null
	);
}

function tailwindscore_enqueue_vite_dev_style( string $handle, string $entry, array $deps = array() ): void {
	wp_enqueue_style(
		$handle,
		tailwindscore_vite_dev_server_url() . '/' . ltrim( $entry, '/' ),
		$deps,
		null
	);
}

function tailwindscore_enqueue_vite_dev_script( string $handle, string $entry, array $deps = array(), bool $in_footer = true ): void {
	wp_enqueue_script(
		$handle,
		tailwindscore_vite_dev_server_url() . '/' . ltrim( $entry, '/' ),
		$deps,
		null,
		$in_footer
	);
	wp_script_add_data( $handle, 'type', 'module' );
}

function tailwindscore_script_loader_tag( string $tag, string $handle, string $src ): string {
	if ( ! in_array( $handle, tailwindscore_module_script_handles(), true ) ) {
		return $tag;
	}

	return sprintf(
		'<script type="module" src="%s"></script>',
		esc_url( $src )
	);
}

function tailwindscore_enqueue_assets(): void {
	if ( tailwindscore_is_vite_dev_enabled() ) {
		tailwindscore_enqueue_vite_dev_style( 'tailwindscore-app', 'src/scss/app.scss' );

		if ( tailwindscore_is_woo_page() ) {
			tailwindscore_enqueue_vite_dev_style( 'tailwindscore-woo', 'src/scss/woo.scss', array( 'tailwindscore-app' ) );
		}

		tailwindscore_enqueue_vite_dev_script( 'tailwindscore-vite-client', '@vite/client', array(), false );
		tailwindscore_enqueue_runtime_config();
		tailwindscore_enqueue_vite_dev_script( 'tailwindscore-app', 'src/ts/app.ts', array( 'tailwindscore-runtime-config' ) );
		tailwindscore_localize_app_script();
		// 单品页图库配置已包含在 tailwindscore_localize_app_script() 的 is_product() 分支中。
		return;
	}

	$manifest = tailwindscore_manifest();

	tailwindscore_enqueue_manifest_style( 'tailwindscore-app', 'src/scss/app.scss' );

	if ( tailwindscore_is_woo_page() ) {
		tailwindscore_enqueue_manifest_style( 'tailwindscore-woo', 'src/scss/woo.scss', array( 'tailwindscore-app' ) );
	}

	if ( ! empty( $manifest['src/ts/app.ts']['file'] ) ) {
		tailwindscore_enqueue_runtime_config();
		wp_enqueue_script(
			'tailwindscore-app',
			get_template_directory_uri() . '/assets/dist/' . $manifest['src/ts/app.ts']['file'],
			array( 'tailwindscore-runtime-config' ),
			null,
			true
		);
		wp_script_add_data( 'tailwindscore-app', 'type', 'module' );
		tailwindscore_localize_app_script();
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_enqueue_assets' );
add_filter( 'script_loader_tag', 'tailwindscore_script_loader_tag', 10, 3 );
