<?php
declare(strict_types=1);

function tailwindscore_is_vite_dev_enabled(): bool {
	return defined( 'TAILWINDSCORE_VITE_DEV' ) && true === TAILWINDSCORE_VITE_DEV;
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

	if ( function_exists( 'is_product' ) && is_product() ) {
		if ( function_exists( 'tailwindscore_product_page_config' ) ) {
			$inline .= sprintf(
				'window.tailwindscoreProduct = %s;',
				wp_json_encode( tailwindscore_product_page_config() )
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
	// Module scripts do not reliably print wp_add_inline_script output in WordPress.
	// Runtime config is emitted via tailwindscore-runtime-config instead.
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
		// Product gallery config is included in tailwindscore_localize_app_script() on is_product().
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
