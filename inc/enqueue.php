<?php
declare(strict_types=1);

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

function tailwindscore_enqueue_assets(): void {
	$manifest = tailwindscore_manifest();

	tailwindscore_enqueue_manifest_style( 'tailwindscore-app', 'src/css/app.css' );

	if ( tailwindscore_is_woo_page() ) {
		tailwindscore_enqueue_manifest_style( 'tailwindscore-woo', 'src/css/woo.css', array( 'tailwindscore-app' ) );
	}

	if ( ! empty( $manifest['src/ts/app.ts']['file'] ) ) {
		wp_enqueue_script(
			'tailwindscore-app',
			get_template_directory_uri() . '/assets/dist/' . $manifest['src/ts/app.ts']['file'],
			array(),
			null,
			true
		);
		wp_script_add_data( 'tailwindscore-app', 'type', 'module' );
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_enqueue_assets' );

