<?php
declare(strict_types=1);

function tailwindscore_cleanup(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'init', 'tailwindscore_cleanup' );

/**
 * Return 404 for attachment permalink pages (media files remain accessible by URL).
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

