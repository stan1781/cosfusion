<?php
declare(strict_types=1);

// 购买流程渲染见 inc/woocommerce/cart-view.php。
// 通用站点辅助函数保留在 inc/template-tags.php。

function tailwindscore_site_name(): string {
	return get_bloginfo( 'name' );
}

/**
 * 媒体库中图片 URL 的固有尺寸。
 *
 * 用于 width/height 属性，使浏览器在图片加载前预留布局空间（PageSpeed / CLS）。
 *
 * @return array{width: int, height: int}
 */
function tailwindscore_image_url_dimensions( string $url, int $fallback_width, int $fallback_height ): array {
	$url = trim( $url );

	if ( '' === $url ) {
		return array(
			'width'  => $fallback_width,
			'height' => $fallback_height,
		);
	}

	$attachment_id = attachment_url_to_postid( $url );

	if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
		$meta = wp_get_attachment_metadata( $attachment_id );

		if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			return array(
				'width'  => (int) $meta['width'],
				'height' => (int) $meta['height'],
			);
		}
	}

	return array(
		'width'  => $fallback_width,
		'height' => $fallback_height,
	);
}

/**
 * 当前条目的面包屑路径（首页 + 祖先 / 分类 + 标题）。
 *
 * @return list<array{label: string, url: string}>
 */
function tailwindscore_entry_breadcrumb_items(): array {
	$items = array(
		array(
			'label' => __( 'Home', 'tailwindscore' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_page() && ! is_front_page() ) {
		$ancestors = array_reverse( array_map( 'absint', get_post_ancestors( get_the_ID() ) ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			);
		}
	} elseif ( is_single() && 'post' === get_post_type() ) {
		$categories = get_the_category();

		if ( ! empty( $categories ) ) {
			$category = $categories[0];
			$items[]    = array(
				'label' => $category->name,
				'url'   => get_category_link( $category->term_id ),
			);
		}
	}

	$items[] = array(
		'label' => get_the_title(),
		'url'   => '',
	);

	return $items;
}

/**
 * 是否渲染装饰性条目头部（标题带）。
 */
function tailwindscore_show_entry_header(): bool {
	if ( function_exists( 'tailwindscore_show_page_title' ) && ! tailwindscore_show_page_title() ) {
		return false;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return false;
	}

	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return false;
	}

	return true;
}

function tailwindscore_has_sidebar(): bool {
	if ( is_404() ) {
		return false;
	}

	if ( is_front_page() ) {
		return false;
	}

	if ( function_exists( 'tailwindscore_is_full_width_page' ) && tailwindscore_is_full_width_page() ) {
		return false;
	}

	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return false;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return false;
	}

	if ( is_page( 'register' ) ) {
		return false;
	}

	return is_active_sidebar( 'sidebar-1' );
}

/**
 * 文章页是否显示专用侧栏（Post Sidebar 有小工具时）。
 */
function tailwindscore_has_post_sidebar(): bool {
	return is_singular( 'post' ) && is_active_sidebar( 'sidebar-post' );
}

function tailwindscore_account_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return wp_login_url( home_url( '/' ) );
}

function tailwindscore_search_mode(): string {
	$default_mode = function_exists( 'WC' ) ? 'product' : 'site';
	$mode         = apply_filters( 'tailwindscore_search_mode', $default_mode );

	if ( ! is_string( $mode ) ) {
		return $default_mode;
	}

	$mode = sanitize_key( $mode );

	if ( ! in_array( $mode, array( 'product', 'site', 'mixed' ), true ) ) {
		return $default_mode;
	}

	if ( 'product' === $mode && ! function_exists( 'WC' ) ) {
		return 'site';
	}

	if ( 'mixed' === $mode && ! function_exists( 'WC' ) ) {
		return 'site';
	}

	return $mode;
}

function tailwindscore_search_post_types(): array {
	$mode = tailwindscore_search_mode();

	if ( 'product' === $mode ) {
		return array( 'product' );
	}

	if ( 'mixed' === $mode ) {
		return array( 'product', 'post', 'page' );
	}

	return array( 'post', 'page' );
}

function tailwindscore_search_placeholder_text(): string {
	if ( 'product' === tailwindscore_search_mode() ) {
		return __( 'Search products', 'tailwindscore' );
	}

	return __( 'Search the site', 'tailwindscore' );
}

function tailwindscore_search_submit_text(): string {
	if ( 'product' === tailwindscore_search_mode() ) {
		return __( 'Search products', 'tailwindscore' );
	}

	return __( 'Search', 'tailwindscore' );
}

function tailwindscore_render_search_hidden_fields(): void {
	$post_types = tailwindscore_search_post_types();

	if ( 1 !== count( $post_types ) ) {
		return;
	}
	?>
	<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_types[0] ); ?>">
	<?php
}

function tailwindscore_render_cart_badge(): void {
	$count = tailwindscore_cart_count();

	if ( $count <= 0 ) {
		return;
	}
	?>
	<span class="site-header__badge" aria-hidden="true"><?php echo esc_html( (string) $count ); ?></span>
	<?php
}

function tailwindscore_render_cart_screen_reader_text(): void {
	$count = tailwindscore_cart_count();

	?>
	<span class="screen-reader-text">
		<?php
		printf(
			/* translators: %d: cart item count */
			esc_html__( 'Cart, %d items', 'tailwindscore' ),
			$count
		);
		?>
	</span>
	<?php
}
