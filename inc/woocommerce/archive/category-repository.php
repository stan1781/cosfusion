<?php
declare(strict_types=1);

/**
 * 归档 product_cat 侧栏分组：顶层频道 + 当前频道下 IP 子分类。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 商店 / 分类归档：读取 product_cat 侧栏分组。
 */
final class Tailwindscore_Archive_Category_Repository {

	/**
	 * 主导航顶层频道 slug。
	 *
	 * @return array<int, string>
	 */
	public static function get_top_level_slugs(): array {
		$slugs = array( 'movie', 'anime', 'game', 'tv', 'halloween-costumes' );

		/**
		 * @param array<int, string> $slugs
		 */
		return apply_filters( 'tailwindscore/archive/top_level_category_slugs', $slugs );
	}

	/**
	 * 是否跳过侧栏中的 term。
	 */
	public static function should_skip_term( WP_Term $term ): bool {
		$slug = sanitize_title( $term->slug );

		if ( in_array( $slug, array( 'uncategorized', 'all' ), true ) ) {
			return true;
		}

		/**
		 * @param bool    $skip
		 * @param WP_Term $term
		 */
		return (bool) apply_filters( 'tailwindscore/archive/skip_sidebar_term', false, $term );
	}

	/**
	 * @return array<int, array{id: string, label: string, type: string, items: array<int, array<string, mixed>>}>
	 */
	public function get_sidebar_groups( ?WP_Term $current_term = null ): array {
		$groups = array();

		$channel_items = $this->get_channel_items( $current_term );

		if ( array() !== $channel_items ) {
			$groups[] = array(
				'id'    => 'categories',
				'label' => __( 'Categories', 'tailwindscore' ),
				'type'  => 'taxonomy',
				'items' => $channel_items,
			);
		}

		$series_items = $this->get_series_items( $current_term );

		if ( array() !== $series_items ) {
			$groups[] = array(
				'id'    => 'series',
				'label' => __( 'Series', 'tailwindscore' ),
				'type'  => 'taxonomy',
				'items' => $series_items,
			);
		}

		/**
		 * @param array<int, array<string, mixed>> $groups
		 * @param WP_Term|null                     $current_term
		 */
		return apply_filters( 'tailwindscore/archive/sidebar_groups', $groups, $current_term );
	}

	/**
	 * 顶层频道 chip。
	 *
	 * @return array<int, array{term_id: int, name: string, url: string, is_active: bool}>
	 */
	private function get_channel_items( ?WP_Term $current_term ): array {
		$current_id     = $current_term instanceof WP_Term ? (int) $current_term->term_id : 0;
		$active_root_id = $this->resolve_channel_root_id( $current_term );
		$items          = array();

		foreach ( self::get_top_level_slugs() as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );

			if ( ! $term instanceof WP_Term || self::should_skip_term( $term ) ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$term_id = (int) $term->term_id;

			$items[] = array(
				'term_id'   => $term_id,
				'name'      => $term->name,
				'url'       => tailwindscore_archive_category_url( (string) $link ),
				'is_active' => $term_id === $current_id || $term_id === $active_root_id,
			);
		}

		return $items;
	}

	/**
	 * 当前频道下的 IP 子分类。
	 *
	 * @return array<int, array{term_id: int, name: string, url: string, is_active: bool}>
	 */
	private function get_series_items( ?WP_Term $current_term ): array {
		$root_id = $this->resolve_channel_root_id( $current_term );

		if ( $root_id < 1 ) {
			return array();
		}

		$current_id = $current_term instanceof WP_Term ? (int) $current_term->term_id : 0;
		$children   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $root_id,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( ! is_array( $children ) ) {
			return array();
		}

		$items = array();

		foreach ( $children as $term ) {
			if ( ! $term instanceof WP_Term || self::should_skip_term( $term ) ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$term_id = (int) $term->term_id;

			$items[] = array(
				'term_id'   => $term_id,
				'name'      => $term->name,
				'url'       => tailwindscore_archive_category_url( (string) $link ),
				'is_active' => $term_id === $current_id,
			);
		}

		return $items;
	}

	/**
	 * 解析当前 term 所属的顶层频道 term_id；shop 时为 0。
	 */
	private function resolve_channel_root_id( ?WP_Term $current_term ): int {
		if ( ! $current_term instanceof WP_Term || 'product_cat' !== $current_term->taxonomy ) {
			return 0;
		}

		$top_slugs = self::get_top_level_slugs();
		$term      = $current_term;

		for ( $i = 0; $i < 8; $i++ ) {
			if ( in_array( $term->slug, $top_slugs, true ) ) {
				return (int) $term->term_id;
			}

			if ( (int) $term->parent < 1 ) {
				break;
			}

			$parent = get_term( (int) $term->parent, 'product_cat' );

			if ( ! $parent instanceof WP_Term ) {
				break;
			}

			$term = $parent;
		}

		return 0;
	}
}
