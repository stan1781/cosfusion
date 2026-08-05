<?php
declare(strict_types=1);

/**
 * 导航菜单项辅助函数。
 *
 * 菜单编辑器 CSS class：
 * - mega-menu — mega panel（三级：顶栏 → 列 → 链接）
 * - hot、new、sale — 角标（样式见 `_navigation.scss`）
 *
 * 子菜单缩略图：手动 meta `_ts_nav_item_thumbnail_id`，或 product_cat Thumbnail。
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

/**
 * 菜单项缩略图 post meta key。
 */
function tailwindscore_nav_item_thumbnail_meta_key(): string {
	return '_ts_nav_item_thumbnail_id';
}

/**
 * 读取菜单项手动指定的缩略图 attachment ID。
 *
 * @param object $item 菜单项。
 */
function tailwindscore_nav_menu_item_manual_thumbnail_id( object $item ): int {
	if ( isset( $item->ts_thumbnail_id ) ) {
		return max( 0, (int) $item->ts_thumbnail_id );
	}

	if ( empty( $item->ID ) ) {
		return 0;
	}

	return max( 0, (int) get_post_meta( (int) $item->ID, tailwindscore_nav_item_thumbnail_meta_key(), true ) );
}

/**
 * 从菜单项 URL 反查 product_cat（自定义链接回退）。
 */
function tailwindscore_nav_resolve_product_cat_from_url( string $url ): ?WP_Term {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$path = trim( $path, '/' );

	if ( '' === $path ) {
		return null;
	}

	$permalinks = get_option( 'woocommerce_permalinks', array() );
	$base       = trim( (string) ( $permalinks['category_base'] ?? 'product-category' ), '/' );

	if ( '' !== $base && ! str_starts_with( $path, $base ) ) {
		return null;
	}

	$slug_path = '' !== $base ? trim( substr( $path, strlen( $base ) ), '/' ) : $path;

	if ( '' === $slug_path ) {
		return null;
	}

	$segments = explode( '/', $slug_path );
	$slug     = (string) end( $segments );

	if ( '' === $slug ) {
		return null;
	}

	$term = get_term_by( 'slug', $slug, 'product_cat' );

	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}

	return $term;
}

/**
 * 解析菜单项对应的产品分类。
 *
 * @param object $item 菜单项。
 */
function tailwindscore_nav_resolve_product_cat_from_menu_item( object $item ): ?WP_Term {
	$term = null;

	if (
		isset( $item->type, $item->object, $item->object_id )
		&& 'taxonomy' === $item->type
		&& 'product_cat' === $item->object
		&& (int) $item->object_id > 0
	) {
		$resolved = get_term( (int) $item->object_id, 'product_cat' );

		if ( $resolved && ! is_wp_error( $resolved ) ) {
			$term = $resolved;
		}
	} elseif ( ! empty( $item->url ) ) {
		$term = tailwindscore_nav_resolve_product_cat_from_url( (string) $item->url );
	}

	/**
	 * @param WP_Term|null $term 解析到的分类；null 表示无法解析。
	 * @param object       $item 菜单项。
	 */
	return apply_filters( 'tailwindscore_nav_menu_item_product_cat', $term, $item );
}

/**
 * 读取产品分类缩略图 attachment ID（WooCommerce thumbnail_id）。
 */
function tailwindscore_nav_product_cat_thumbnail_id( WP_Term $term ): int {
	return max( 0, (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) );
}

/**
 * 子菜单缩略图 HTML（仅 depth > 0）。
 *
 * 优先级：手动 meta → 产品分类 Thumbnail → 不显示。
 *
 * @param object $item  菜单项。
 * @param int    $depth Walker 深度。
 */
function tailwindscore_nav_menu_item_thumbnail_html( object $item, int $depth ): string {
	if ( $depth <= 0 ) {
		return '';
	}

	$term        = null;
	$manual_id   = tailwindscore_nav_menu_item_manual_thumbnail_id( $item );
	$title       = apply_filters( 'the_title', $item->title, $item->ID );
	$alt         = is_string( $title ) ? wp_strip_all_tags( $title ) : '';
	$image_attrs = array(
		'class'   => 'nav__thumb',
		'alt'     => $alt,
		'loading' => 'lazy',
	);

	if ( $manual_id > 0 ) {
		$html = wp_get_attachment_image( $manual_id, 'woocommerce_thumbnail', false, $image_attrs );
	} else {
		$term = tailwindscore_nav_resolve_product_cat_from_menu_item( $item );

		if ( ! $term ) {
			return '';
		}

		$attachment_id = tailwindscore_nav_product_cat_thumbnail_id( $term );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		$html = wp_get_attachment_image( $attachment_id, 'woocommerce_thumbnail', false, $image_attrs );
	}

	if ( ! is_string( $html ) || '' === $html ) {
		return '';
	}

	/**
	 * @param string       $html  缩略图 HTML。
	 * @param object       $item  菜单项。
	 * @param int          $depth Walker 深度。
	 * @param WP_Term|null $term  解析到的产品分类（手动图时为 null）。
	 */
	return (string) apply_filters( 'tailwindscore_nav_menu_item_thumbnail_html', $html, $item, $depth, $term );
}
