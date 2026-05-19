<?php
declare(strict_types=1);

function tailwindscore_cleanup(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'init', 'tailwindscore_cleanup' );

