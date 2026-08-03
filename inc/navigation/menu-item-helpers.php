<?php
declare(strict_types=1);

/**
 * 导航菜单项辅助函数。
 *
 * 菜单编辑器 CSS class：
 * - mega-menu — mega panel（三级：顶栏 → 列 → 链接）
 * - hot、new、sale — 角标（样式见 `_navigation.scss`）
 */

/**
 * @param object $item 导航菜单项。
 */
function tailwindscore_nav_item_has_class( object $item, string $class_name ): bool {
	if ( empty( $item->classes ) || ! is_array( $item->classes ) ) {
		return false;
	}

	$needle = sanitize_html_class( $class_name );

	foreach ( $item->classes as $class ) {
		if ( sanitize_html_class( (string) $class ) === $needle ) {
			return true;
		}
	}

	return false;
}

/**
 * @param object $item 导航菜单项。
 * @param object $args Walker 参数。
 */
function tailwindscore_nav_item_has_children( object $item, object $args ): bool {
	$has_children = ! empty( $args->has_children )
		|| tailwindscore_nav_item_has_class( $item, 'menu-item-has-children' );

	return (bool) apply_filters( 'tailwindscore_nav_item_has_children', $has_children, $item, $args );
}

/**
 * @param object $item 导航菜单项。
 */
function tailwindscore_nav_item_is_mega( object $item ): bool {
	$is_mega = tailwindscore_nav_item_has_class( $item, 'mega-menu' )
		|| tailwindscore_nav_item_has_class( $item, 'mega-menu' );

	return (bool) apply_filters( 'tailwindscore_nav_menu_is_mega_item', $is_mega, $item );
}

/**
 * @return list<string>
 */
function tailwindscore_nav_badge_classes(): array {
	return array( 'hot', 'new', 'sale' );
}

	/**
	 * 菜单角标 HTML（hot / new / sale）。
	 *
	 * @param object $item 导航菜单项。
	 */
function tailwindscore_nav_item_badge_html( object $item, string $position = 'top' ): string {
	if ( empty( $item->classes ) || ! is_array( $item->classes ) ) {
		return '';
	}

	foreach ( tailwindscore_nav_badge_classes() as $badge ) {
		if ( ! tailwindscore_nav_item_has_class( $item, $badge ) ) {
			continue;
		}

		$label = ucfirst( $badge );
		$pos   = 'inline' === $position ? 'nav__badge--inline' : 'nav__badge--top';

		return sprintf(
			'<span class="nav__badge nav__badge--%1$s %2$s">%3$s</span>',
			esc_attr( $badge ),
			esc_attr( $pos ),
			esc_html( $label )
		);
	}

	return '';
}
