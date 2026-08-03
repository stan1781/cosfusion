<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 一次性清除旧「首页展示」Kirki theme_mod（不做数据迁移）。
 */
function tailwindscore_purge_homepage_theme_mods(): void {
	if ( get_option( 'tailwindscore_purged_homepage_mods_v1' ) ) {
		return;
	}

	$keys = array(
		'homepage_hero_slides',
		'homepage_hero_autoplay',
		'homepage_occasion_title',
		'homepage_occasion_items',
		'homepage_trending_title',
		'homepage_trending_items',
		'homepage_products_enable',
		'homepage_products_title',
		'homepage_products_shortcode',
		'homepage_products_slides',
		'homepage_products_slides_mobile',
		'homepage_products_slides_desktop',
		'homepage_products_slides_wide',
		'homepage_products_slide_gap',
		'homepage_shop_by_color_enable',
		'homepage_shop_by_color_title',
		'homepage_trust_enable',
		'homepage_trust_items',
	);

	foreach ( $keys as $key ) {
		remove_theme_mod( $key );
	}

	update_option( 'tailwindscore_purged_homepage_mods_v1', 1, false );
}
add_action( 'after_setup_theme', 'tailwindscore_purge_homepage_theme_mods', 5 );
