<?php
declare(strict_types=1);

/**
 * 归档属性筛选：依赖 WooCommerce layered nav 的 filter_{attr} 查询变量。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * MVP 参与侧栏筛选的全局属性 slug（不含 pa_ 前缀）。
 * Gender / Style 不参与归档筛选。
 *
 * @return array<int, string>
 */
function tailwindscore_archive_attribute_slugs(): array {
	$slugs = array( 'color' );

	/**
	 * @param array<int, string> $slugs
	 */
	return apply_filters( 'tailwindscore/archive/attribute_slugs', $slugs );
}

/**
 * 当前请求中已选的 layered-nav 属性（taxonomy => terms）。
 *
 * @return array<string, array{terms: array<int, string>, query_type: string}>
 */
function tailwindscore_archive_get_chosen_attributes(): array {
	if ( ! class_exists( 'WC_Query' ) ) {
		return array();
	}

	$chosen = WC_Query::get_layered_nav_chosen_attributes();

	return is_array( $chosen ) ? $chosen : array();
}

/**
 * 某属性当前选中的 term slug（单选 MVP：取第一个）。
 */
function tailwindscore_archive_get_chosen_attribute_slug( string $attribute_slug ): string {
	$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
	$chosen   = tailwindscore_archive_get_chosen_attributes();

	if ( empty( $chosen[ $taxonomy ]['terms'] ) || ! is_array( $chosen[ $taxonomy ]['terms'] ) ) {
		return '';
	}

	$first = reset( $chosen[ $taxonomy ]['terms'] );

	return is_string( $first ) ? sanitize_title( $first ) : '';
}

/**
 * 已激活的属性筛选维度数量（用于移动端 Filters 徽章）。
 */
function tailwindscore_archive_get_active_attribute_count(): int {
	$count = 0;

	foreach ( tailwindscore_archive_attribute_slugs() as $slug ) {
		if ( '' !== tailwindscore_archive_get_chosen_attribute_slug( $slug ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * 当前归档基础 URL（shop 或分类/标签 permalink，不含筛选 query）。
 */
function tailwindscore_archive_get_base_url(): string {
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );

			if ( ! is_wp_error( $link ) ) {
				return (string) $link;
			}
		}
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );

		if ( is_string( $shop ) && '' !== $shop ) {
			return $shop;
		}
	}

	return home_url( '/' );
}

/**
 * 在归档 URL 上合并/切换属性筛选 query。
 *
 * @param string               $base_url       归档基础 URL（可含 path）。
 * @param array<string, string> $set_filters   要写入的 filter_{attr} => slug；空字符串表示删除该维。
 * @param bool                 $preserve_others 是否保留其它 filter_* / orderby。
 */
function tailwindscore_archive_build_filtered_url( string $base_url, array $set_filters = array(), bool $preserve_others = true ): string {
	$parts = wp_parse_url( $base_url );

	if ( ! is_array( $parts ) ) {
		return $base_url;
	}

	$query = array();

	if ( $preserve_others ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( (array) $_GET as $key => $value ) {
			$key = (string) $key;

			if ( 'orderby' === $key || 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'query_type_' ) ) {
				if ( is_scalar( $value ) ) {
					$query[ $key ] = (string) $value;
				}
			}
		}
	}

	foreach ( $set_filters as $attr_slug => $term_slug ) {
		$attr_slug  = sanitize_title( (string) $attr_slug );
		$term_slug  = sanitize_title( (string) $term_slug );
		$filter_key = 'filter_' . $attr_slug;
		$type_key   = 'query_type_' . $attr_slug;

		if ( '' === $term_slug ) {
			unset( $query[ $filter_key ], $query[ $type_key ] );
			continue;
		}

		$query[ $filter_key ] = $term_slug;
		$query[ $type_key ]   = 'or';
	}

	$path   = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
	$scheme = isset( $parts['scheme'] ) ? (string) $parts['scheme'] : ( is_ssl() ? 'https' : 'http' );
	$host   = isset( $parts['host'] ) ? (string) $parts['host'] : wp_parse_url( home_url(), PHP_URL_HOST );
	$url    = $scheme . '://' . $host . $path;

	if ( array() !== $query ) {
		$url = add_query_arg( $query, $url );
	}

	return $url;
}

/**
 * 分类链接：始终使用干净 permalink，不携带属性筛选（避免空结果与筛选参数组合爆炸）。
 */
function tailwindscore_archive_category_url( string $term_url ): string {
	$parts = wp_parse_url( $term_url );

	if ( ! is_array( $parts ) ) {
		return $term_url;
	}

	$scheme = isset( $parts['scheme'] ) ? (string) $parts['scheme'] : ( is_ssl() ? 'https' : 'http' );
	$host   = isset( $parts['host'] ) ? (string) $parts['host'] : (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$path   = isset( $parts['path'] ) ? (string) $parts['path'] : '/';

	return $scheme . '://' . $host . $path;
}

/**
 * 属性 chip URL：All 传空 slug 清除该维；具体 term 始终写入该 slug（激活态不反转为清除）。
 * 点已选项清除由前端导航到同组 All 链接完成。
 */
function tailwindscore_archive_attribute_chip_url( string $attribute_slug, string $term_slug ): string {
	return tailwindscore_archive_build_filtered_url(
		tailwindscore_archive_get_base_url(),
		array( $attribute_slug => sanitize_title( $term_slug ) ),
		true
	);
}

/**
 * 当前请求是否带有归档属性筛选（用于 SEO noindex / canonical）。
 */
function tailwindscore_archive_request_has_attribute_filters(): bool {
	if ( ! tailwindscore_is_product_archive() ) {
		return false;
	}

	return tailwindscore_archive_get_active_attribute_count() > 0;
}

/**
 * 带属性筛选的归档页：noindex，避免蜘蛛收录组合垃圾 URL。
 *
 * @param array<string, bool|string> $robots
 * @return array<string, bool|string>
 */
function tailwindscore_archive_filter_wp_robots( array $robots ): array {
	if ( ! tailwindscore_archive_request_has_attribute_filters() ) {
		return $robots;
	}

	$robots['noindex'] = true;
	$robots['follow']  = true;
	unset( $robots['index'] );

	return $robots;
}
add_filter( 'wp_robots', 'tailwindscore_archive_filter_wp_robots', 20 );

/**
 * 属性筛选页若仍输出 canonical，则指向干净归档 URL（不含 filter_*）。
 * 注：Rank Math 在 noindex 时会移除 canonical（其默认行为），此时本 filter 不生效亦正确。
 *
 * @param string $canonical
 */
function tailwindscore_archive_filter_get_canonical_url( string $canonical ): string {
	if ( ! tailwindscore_archive_request_has_attribute_filters() ) {
		return $canonical;
	}

	$clean = tailwindscore_archive_get_base_url();

	return '' !== $clean ? $clean : $canonical;
}
add_filter( 'get_canonical_url', 'tailwindscore_archive_filter_get_canonical_url', 20 );
add_filter( 'wpseo_canonical', 'tailwindscore_archive_filter_get_canonical_url', 20 );
add_filter( 'rank_math/frontend/canonical', 'tailwindscore_archive_filter_get_canonical_url', 20 );

/**
 * Yoast：属性筛选页强制 noindex。
 *
 * @param string $robots
 */
function tailwindscore_archive_filter_wpseo_robots( string $robots ): string {
	if ( ! tailwindscore_archive_request_has_attribute_filters() ) {
		return $robots;
	}

	return 'noindex, follow';
}
add_filter( 'wpseo_robots', 'tailwindscore_archive_filter_wpseo_robots', 20 );

/**
 * Rank Math：属性筛选页强制 noindex。
 *
 * @param array<string, string> $robots
 * @return array<string, string>
 */
function tailwindscore_archive_filter_rank_math_robots( $robots ) {
	if ( ! tailwindscore_archive_request_has_attribute_filters() ) {
		return $robots;
	}

	if ( ! is_array( $robots ) ) {
		return array(
			'index'  => 'noindex',
			'follow' => 'follow',
		);
	}

	$robots['index']  = 'noindex';
	$robots['follow'] = 'follow';

	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'tailwindscore_archive_filter_rank_math_robots', 20 );
