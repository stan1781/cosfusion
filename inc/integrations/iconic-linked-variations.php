<?php
declare(strict_types=1);

/**
 * Iconic Woo Linked Variations — load frontend assets only on single product pages.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Iconic Linked Variations frontend CSS/JS should load on this request.
 */
function tailwindscore_should_load_iconic_wlv_assets(): bool {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return false;
	}

	return (bool) apply_filters( 'tailwindscore_should_load_iconic_wlv_assets', true );
}

/**
 * Dequeue Iconic Linked Variations frontend assets.
 */
function tailwindscore_dequeue_iconic_wlv_assets(): void {
	wp_dequeue_style( 'iconic-wlv' );
	wp_dequeue_script( 'iconic-wlv' );
	wp_deregister_style( 'iconic-wlv' );
	wp_deregister_script( 'iconic-wlv' );
}

/**
 * Stop the plugin from enqueueing assets (and jQuery) before it runs.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		remove_action( 'wp_enqueue_scripts', array( 'Iconic_WLV_Assets', 'enqueue_frontend_assets' ) );
		tailwindscore_dequeue_iconic_wlv_assets();
	},
	1
);

/**
 * Safety net if another callback re-enqueues later in the same request.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		tailwindscore_dequeue_iconic_wlv_assets();
	},
	999
);

add_action(
	'wp_print_styles',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		tailwindscore_dequeue_iconic_wlv_assets();
	},
	100
);

add_action(
	'wp_print_scripts',
	static function (): void {
		if ( tailwindscore_should_load_iconic_wlv_assets() ) {
			return;
		}

		tailwindscore_dequeue_iconic_wlv_assets();
	},
	100
);
