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

function tailwindscore_localize_app_script(): void {
	wp_add_inline_script(
		'tailwindscore-app',
		sprintf(
			'window.tailwindscoreCart = %s;',
			wp_json_encode(
				array(
					'storeApiUrl' => rest_url( 'wc/store/v1/' ),
					'nonce'       => wp_create_nonce( 'wc_store_api' ),
					'cartUrl'     => tailwindscore_cart_url(),
					'checkoutUrl' => tailwindscore_checkout_url(),
					'shopUrl'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
					'i18n'        => array(
						'cartScreenReaderSingular' => __( 'Cart, %d item', 'tailwindscore' ),
						'cartScreenReaderPlural'   => __( 'Cart, %d items', 'tailwindscore' ),
						'cartSummarySingular'      => __( '%d item ready for checkout', 'tailwindscore' ),
						'cartSummaryPlural'        => __( '%d items ready for checkout', 'tailwindscore' ),
						'emptyCartMessage'         => __( 'Your cart is empty.', 'tailwindscore' ),
						'browseProducts'           => __( 'Browse products', 'tailwindscore' ),
						'subtotal'                 => __( 'Subtotal', 'tailwindscore' ),
						'checkout'                 => __( 'Checkout', 'tailwindscore' ),
						'viewCart'                 => __( 'View cart', 'tailwindscore' ),
						'quantityLabel'            => __( 'Quantity', 'tailwindscore' ),
						'decreaseQuantity'         => __( 'Decrease quantity', 'tailwindscore' ),
						'increaseQuantity'         => __( 'Increase quantity', 'tailwindscore' ),
						'removeItem'               => __( 'Remove item from your cart', 'tailwindscore' ),
					),
					'icons'       => array(
						'plus'  => tailwindscore_icon( 'plus' ),
						'minus' => tailwindscore_icon( 'minus' ),
						'trash' => tailwindscore_icon( 'trash' ),
					),
				)
			)
		),
		'before'
	);
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
		tailwindscore_enqueue_vite_dev_style( 'tailwindscore-app', 'src/css/app.css' );

		if ( tailwindscore_is_woo_page() ) {
			tailwindscore_enqueue_vite_dev_style( 'tailwindscore-woo', 'src/css/woo.css', array( 'tailwindscore-app' ) );
		}

		tailwindscore_enqueue_vite_dev_script( 'tailwindscore-vite-client', '@vite/client', array(), false );
		tailwindscore_enqueue_vite_dev_script( 'tailwindscore-app', 'src/ts/app.ts' );
		tailwindscore_localize_app_script();
		return;
	}

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
		tailwindscore_localize_app_script();
	}
}
add_action( 'wp_enqueue_scripts', 'tailwindscore_enqueue_assets' );
add_filter( 'script_loader_tag', 'tailwindscore_script_loader_tag', 10, 3 );
