<?php
declare(strict_types=1);

/**
 * wc-mailnest theme integration (Tier B).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether MailNest frontend integration should load on this request.
 */
function tailwindscore_should_load_mailnest(): bool {
	if ( ! function_exists( 'wc_mailnest_frontend_config' ) ) {
		return false;
	}

	return function_exists( 'wc_mailnest_is_frontend_needed' ) && wc_mailnest_is_frontend_needed();
}

/**
 * Dequeue plugin frontend assets (legacy handle guard).
 */
function tailwindscore_dequeue_mailnest_assets(): void {
	wp_dequeue_style( 'wc-mailnest-frontend' );
	wp_dequeue_script( 'wc-mailnest-frontend' );
	wp_deregister_style( 'wc-mailnest-frontend' );
	wp_deregister_script( 'wc-mailnest-frontend' );
	wp_dequeue_style( 'mailnest-frontend' );
	wp_dequeue_script( 'mailnest-frontend' );
	wp_deregister_style( 'mailnest-frontend' );
	wp_deregister_script( 'mailnest-frontend' );
}

add_filter( 'wc_mailnest_enqueue_frontend_assets', '__return_false' );

add_filter(
	'tailwindscore_runtime_config_parts',
	static function ( array $parts ): array {
		if ( ! tailwindscore_should_load_mailnest() ) {
			return $parts;
		}

		$parts['mailnest'] = wc_mailnest_frontend_config();

		return $parts;
	}
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( tailwindscore_should_load_mailnest() ) {
			$classes[] = 'ts-mailnest';
		}

		return $classes;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! tailwindscore_should_load_mailnest() ) {
			return;
		}

		tailwindscore_dequeue_mailnest_assets();
	},
	1000
);
