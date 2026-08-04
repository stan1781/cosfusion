<?php
declare(strict_types=1);

/**
 * 商店 / 分类归档：筛选组注册表（分类 + 属性 facet）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<int, array<string, mixed>>
 */
function tailwindscore_archive_get_filter_groups( ?WP_Term $current_term = null ): array {
	$repository = new Tailwindscore_Archive_Category_Repository();
	$groups     = $repository->get_sidebar_groups( $current_term );
	$groups     = array_merge( $groups, tailwindscore_archive_get_attribute_filter_groups() );

	/**
	 * @param array<int, array<string, mixed>> $groups
	 * @param WP_Term|null                     $current_term
	 */
	return apply_filters( 'tailwindscore/archive/filter_groups', $groups, $current_term );
}

/**
 * Color 等属性 chip 组（由 attribute_slugs 决定；默认仅 color）。
 * 仅展示当前主查询结果集中有货的属性值（空值不出现）。
 *
 * @return array<int, array{id: string, label: string, type: string, items: array<int, array<string, mixed>>}>
 */
function tailwindscore_archive_get_attribute_filter_groups(): array {
	$groups = array();

	foreach ( tailwindscore_archive_attribute_slugs() as $attribute_slug ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$label = wc_attribute_label( $taxonomy );
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( ! is_array( $terms ) || array() === $terms ) {
			continue;
		}

		$term_ids    = array_map(
			static function ( $term ): int {
				return $term instanceof WP_Term ? (int) $term->term_id : 0;
			},
			$terms
		);
		$term_ids    = array_values( array_filter( $term_ids ) );
		$term_counts = tailwindscore_archive_get_attribute_term_counts( $taxonomy, $term_ids );
		$chosen_slug = tailwindscore_archive_get_chosen_attribute_slug( $attribute_slug );

		$items = array(
			array(
				'name'      => __( 'All', 'tailwindscore' ),
				'url'       => tailwindscore_archive_attribute_chip_url( $attribute_slug, '' ),
				'is_active' => '' === $chosen_slug,
			),
		);

		$visible = 0;

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$count     = isset( $term_counts[ $term->term_id ] ) ? (int) $term_counts[ $term->term_id ] : 0;
			$is_active = $chosen_slug === $term->slug;

			// 当前列表无此属性值则隐藏；已选中的仍保留，便于取消筛选。
			if ( $count < 1 && ! $is_active ) {
				continue;
			}

			++$visible;

			$items[] = array(
				'term_id'   => (int) $term->term_id,
				'name'      => $term->name,
				'url'       => tailwindscore_archive_attribute_chip_url( $attribute_slug, $term->slug ),
				'is_active' => $is_active,
				'count'     => $count,
			);
		}

		if ( 0 === $visible ) {
			continue;
		}

		$groups[] = array(
			'id'    => 'attr-' . $attribute_slug,
			'label' => $label,
			'type'  => 'attribute',
			'items' => $items,
		);
	}

	return $groups;
}

/**
 * 按当前主查询（分类等）统计各属性 term 下的商品数。
 *
 * @param array<int, int> $term_ids
 * @return array<int, int> term_id => count
 */
function tailwindscore_archive_get_attribute_term_counts( string $taxonomy, array $term_ids ): array {
	if ( array() === $term_ids || ! function_exists( 'wc_get_container' ) || ! class_exists( 'WC_Query' ) ) {
		return array();
	}

	try {
		$filterer = wc_get_container()->get(
			\Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer::class
		);

		if ( ! is_object( $filterer ) || ! method_exists( $filterer, 'get_filtered_term_product_counts' ) ) {
			return array();
		}

		$counts = $filterer->get_filtered_term_product_counts( $term_ids, $taxonomy, 'or' );

		return is_array( $counts ) ? array_map( 'absint', $counts ) : array();
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		return array();
	}
}
