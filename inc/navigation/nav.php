<?php
declare(strict_types=1);

require_once get_template_directory() . '/inc/navigation/menu-item-helpers.php';
require_once get_template_directory() . '/inc/navigation/menu-item-admin.php';
require_once get_template_directory() . '/inc/navigation/class-nav-menu-walker.php';

/**
 * 构建 primary 位置的 wp_nav_menu() 参数。
 *
 * @param array<string, mixed> $part_args 来自 get_template_part( ..., null, $args )。
 * @return array<string, mixed>
 */
function tailwindscore_primary_nav_menu_args( array $part_args = array() ): array {
	$part_args = wp_parse_args(
		$part_args,
		array(
			'nav_id'    => 'primary-nav',
			'nav_class' => 'nav',
		)
	);

	$variant = str_contains( (string) $part_args['nav_class'], 'mobile' ) ? 'mobile' : 'desktop';

	$menu_args = array(
		'theme_location' => 'primary',
		'container'      => 'nav',
		'container_id'   => $part_args['nav_id'],
		'container_class'=> $part_args['nav_class'],
		'menu_class'     => 'nav__list',
		'fallback_cb'    => false,
		'depth'          => 3,
		'sub_menu_class' => 'nav__submenu sub-menu',
		'walker'         => new Tailwindscore_Nav_Menu_Walker(
			array(
				'variant' => $variant,
			)
		),
	);

	if ( 'mobile' === $variant ) {
		$menu_args['items_wrap'] = '%3$s';
		$menu_args['menu_class'] = '';
	}

	/**
	 * @param array<string, mixed> $menu_args wp_nav_menu 参数。
	 * @param string               $variant desktop|mobile 变体。
	 */
	return apply_filters( 'tailwindscore_nav_menu_args', $menu_args, $variant );
}

/**
 * 移除 wp-enhanced-tools Mega Menu 后的一次性清理。
 */
function tailwindscore_maybe_delete_legacy_mega_menu_option(): void {
	if ( get_option( 'tailwindscore_legacy_mega_option_removed' ) ) {
		return;
	}

	delete_option( 'wc_enhanced_tools_mega_menu' );
	update_option( 'tailwindscore_legacy_mega_option_removed', '1', true );
}
add_action( 'after_setup_theme', 'tailwindscore_maybe_delete_legacy_mega_menu_option', 20 );
