<?php
declare(strict_types=1);

/**
 * Nav menu item helpers.
 *
 * Menu editor CSS classes:
 * - mega-menu or ts-mega-menu — mega panel (3-level: top → column → links)
 * - hot, new, sale — badge labels (styled in a later phase)
 */

/**
 * @param object $item Nav menu item.
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
 * @param object $item Nav menu item.
 * @param object $args Walker args.
 */
function tailwindscore_nav_item_has_children( object $item, object $args ): bool {
	$has_children = ! empty( $args->has_children )
		|| tailwindscore_nav_item_has_class( $item, 'menu-item-has-children' );

	return (bool) apply_filters( 'tailwindscore_nav_item_has_children', $has_children, $item, $args );
}

/**
 * @param object $item Nav menu item.
 */
function tailwindscore_nav_item_is_mega( object $item ): bool {
	$is_mega = tailwindscore_nav_item_has_class( $item, 'mega-menu' )
		|| tailwindscore_nav_item_has_class( $item, 'ts-mega-menu' );

	return (bool) apply_filters( 'tailwindscore_nav_menu_is_mega_item', $is_mega, $item );
}

/**
 * @return list<string>
 */
function tailwindscore_nav_badge_classes(): array {
	return array( 'hot', 'new', 'sale' );
}

/**
 * Badge HTML for menu labels (structure only; styles in phase 2).
 *
 * @param object $item Nav menu item.
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
		$pos   = 'inline' === $position ? 'ts-nav__badge--inline' : 'ts-nav__badge--top';

		return sprintf(
			'<span class="ts-nav__badge ts-nav__badge--%1$s %2$s">%3$s</span>',
			esc_attr( $badge ),
			esc_attr( $pos ),
			esc_html( $label )
		);
	}

	return '';
}
