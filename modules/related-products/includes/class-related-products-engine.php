<?php
declare(strict_types=1);

/**
 * Related products recommendation engine.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves related product IDs by tag, category (with parent fill), title tokens, and fallbacks.
 */
class Tailwindscore_Related_Products_Engine {

	/**
	 * @var int
	 */
	private int $max_products;

	/**
	 * @var bool
	 */
	private bool $exclude_out_of_stock;

	/**
	 * @var bool
	 */
	private bool $randomize_results;

	/**
	 * @var bool
	 */
	private bool $title_match_enabled;

	/**
	 * @var int
	 */
	private int $title_min_token_length;

	public function __construct() {
		$this->max_products           = Tailwindscore_Related_Products_Settings::get_max_products();
		$this->exclude_out_of_stock   = Tailwindscore_Related_Products_Settings::exclude_out_of_stock();
		$this->randomize_results      = Tailwindscore_Related_Products_Settings::randomize_results();
		$this->title_match_enabled    = Tailwindscore_Related_Products_Settings::title_match_enabled();
		$this->title_min_token_length = Tailwindscore_Related_Products_Settings::get_title_min_token_length();
	}

	/**
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	public function get_related_product_ids( int $product_id ): array {
		if ( ! wc_get_product( $product_id ) ) {
			return array();
		}

		$algorithm = Tailwindscore_Related_Products_Settings::get_algorithm();

		switch ( $algorithm ) {
			case 'simple':
				$ids = $this->get_simple_related_products( $product_id );
				break;
			case 'tag_based':
				$ids = $this->get_tag_based_related_products( $product_id );
				break;
			case 'category_based':
				$ids = $this->get_category_based_related_products( $product_id );
				break;
			case 'hybrid':
			default:
				$ids = $this->get_hybrid_related_products( $product_id );
				break;
		}

		return $this->finalize_ids( $ids );
	}

	/**
	 * Upsell + cross-sell IDs to exclude from related list.
	 *
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	public function get_excluded_product_ids( int $product_id ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$excluded = array_merge(
			$product->get_upsell_ids(),
			$product->get_cross_sell_ids()
		);

		return array_values( array_unique( array_map( 'absint', $excluded ) ) );
	}

	/**
	 * @param int   $product_id   Product ID.
	 * @param int[] $wc_related_ids WooCommerce default related IDs.
	 * @return int[]
	 */
	public function build_related_list( int $product_id, array $wc_related_ids ): array {
		$related = $this->get_related_product_ids( $product_id );

		if ( count( $related ) < $this->max_products && ! empty( $wc_related_ids ) ) {
			$related = array_merge( $related, array_map( 'absint', $wc_related_ids ) );
			$related = array_values( array_unique( $related ) );
			$related = array_slice( $related, 0, $this->max_products );
		}

		$excluded = $this->get_excluded_product_ids( $product_id );
		if ( ! empty( $excluded ) ) {
			$related = array_values( array_diff( $related, $excluded ) );
		}

		return $related;
	}

	/**
	 * @param int[] $ids Product IDs.
	 * @return int[]
	 */
	private function finalize_ids( array $ids ): array {
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );

		if ( $this->randomize_results ) {
			shuffle( $ids );
		}

		return array_slice( $ids, 0, $this->max_products );
	}

	/**
	 * @param int[] $ids       Accumulated IDs.
	 * @param int   $needed    How many more IDs needed.
	 * @return int[]
	 */
	private function merge_up_to( array $ids, int $needed, array $candidates ): array {
		if ( $needed <= 0 || empty( $candidates ) ) {
			return $ids;
		}

		$candidates = array_diff( array_map( 'absint', $candidates ), $ids );
		if ( empty( $candidates ) ) {
			return $ids;
		}

		$candidates = array_values( $candidates );
		if ( $this->randomize_results ) {
			shuffle( $candidates );
		}

		return array_merge( $ids, array_slice( $candidates, 0, $needed ) );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	private function get_simple_related_products( int $product_id ): array {
		$related = array();

		$tags = wp_get_object_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			$related = $this->get_products_by_tags( $tags, $product_id );
			if ( count( $related ) >= $this->max_products ) {
				return $this->finalize_ids( $related );
			}
		}

		$cats = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
			$related = array_values(
				array_unique(
					array_merge( $related, $this->get_products_by_categories( $cats, $product_id ) )
				)
			);
		}

		return $this->pipeline_fill( $product_id, $related );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	private function get_tag_based_related_products( int $product_id ): array {
		$tags = wp_get_object_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );

		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			return $this->get_simple_related_products( $product_id );
		}

		$related = $this->get_products_by_tags( $tags, $product_id );

		if ( count( $related ) < $this->max_products ) {
			$cats = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
				$needed = $this->max_products - count( $related );
				$related = $this->merge_up_to( $related, $needed, $this->get_products_by_categories( $cats, $product_id ) );
			}
		}

		return $this->pipeline_fill( $product_id, $related );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	private function get_category_based_related_products( int $product_id ): array {
		$cats = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( empty( $cats ) || is_wp_error( $cats ) ) {
			return $this->get_simple_related_products( $product_id );
		}

		$related = $this->get_products_by_categories( $cats, $product_id );

		if ( count( $related ) < $this->max_products ) {
			$tags = wp_get_object_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );
			if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
				$needed = $this->max_products - count( $related );
				$related = $this->merge_up_to( $related, $needed, $this->get_products_by_tags( $tags, $product_id ) );
			}
		}

		return $this->pipeline_fill( $product_id, $related );
	}

	/**
	 * Hybrid: tags → categories (parent fill) → title tokens → random fallback.
	 *
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	private function get_hybrid_related_products( int $product_id ): array {
		$related = array();

		$tags = wp_get_object_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			$related = array_merge( $related, $this->get_products_by_tags( $tags, $product_id ) );
		}

		$cats = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
			$related = array_merge( $related, $this->get_products_by_categories( $cats, $product_id ) );
		}

		$related = array_values( array_unique( array_map( 'absint', $related ) ) );

		return $this->pipeline_fill( $product_id, $related );
	}

	/**
	 * Title match then random fallback when still below max.
	 *
	 * @param int   $product_id Product ID.
	 * @param int[] $related    Current candidate IDs.
	 * @return int[]
	 */
	private function pipeline_fill( int $product_id, array $related ): array {
		$related = array_values( array_unique( array_map( 'absint', $related ) ) );

		if ( count( $related ) < $this->max_products && $this->title_match_enabled ) {
			$needed = $this->max_products - count( $related );
			$related = $this->merge_up_to(
				$related,
				$needed,
				$this->get_products_by_title_tokens( $product_id, $related, $needed )
			);
		}

		if ( count( $related ) < $this->max_products ) {
			$needed = $this->max_products - count( $related );
			$related = $this->merge_up_to( $related, $needed, $this->get_fallback_products( $product_id, $needed ) );
		}

		if ( $this->randomize_results ) {
			shuffle( $related );
		}

		return array_slice( $related, 0, $this->max_products );
	}

	/**
	 * @param int   $exclude_id Exclude product.
	 * @param int   $limit      Max IDs.
	 * @return int[]
	 */
	private function get_fallback_products( int $exclude_id, int $limit ): array {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $exclude_id ),
			'fields'         => 'ids',
			'orderby'        => 'rand',
		);

		if ( $this->exclude_out_of_stock ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			);
		}

		$posts = get_posts( $args );
		return is_array( $posts ) ? array_map( 'absint', $posts ) : array();
	}

	/**
	 * @param int[] $tag_ids    Tag term IDs.
	 * @param int   $exclude_id Product to exclude.
	 * @return int[]
	 */
	private function get_products_by_tags( array $tag_ids, int $exclude_id ): array {
		global $wpdb;

		$tag_ids = array_filter( array_map( 'absint', $tag_ids ) );
		if ( empty( $tag_ids ) ) {
			return array();
		}

		$in = implode( ',', $tag_ids );
		$sql = $wpdb->prepare(
			"SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			WHERE tt.taxonomy = 'product_tag'
			AND tt.term_id IN ({$in})
			AND tr.object_id != %d
			AND p.post_status = 'publish'
			AND p.post_type = 'product'",
			$exclude_id
		);

		return $this->run_product_query( $sql );
	}

	/**
	 * @param int $cat_id     Category term ID.
	 * @param int $exclude_id Product to exclude.
	 * @return int[]
	 */
	private function get_products_by_single_category( int $cat_id, int $exclude_id ): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			WHERE tt.taxonomy = 'product_cat'
			AND tt.term_id = %d
			AND tr.object_id != %d
			AND p.post_status = 'publish'
			AND p.post_type = 'product'",
			$cat_id,
			$exclude_id
		);

		return $this->run_product_query( $sql );
	}

	/**
	 * Child categories first; fill from parent when below max.
	 *
	 * @param int[] $cat_ids    Category term IDs.
	 * @param int   $exclude_id Product to exclude.
	 * @return int[]
	 */
	private function get_products_by_categories( array $cat_ids, int $exclude_id ): array {
		$all_products      = array();
		$processed_parents = array();

		foreach ( $cat_ids as $cat_id ) {
			$cat_id    = (int) $cat_id;
			$parent_id = $this->get_category_parent( $cat_id );

			if ( $parent_id > 0 ) {
				$child_products = $this->get_products_by_single_category( $cat_id, $exclude_id );
				$all_products   = array_values( array_unique( array_merge( $all_products, $child_products ) ) );

				if ( count( $all_products ) < $this->max_products && ! in_array( $parent_id, $processed_parents, true ) ) {
					$parent_products = $this->get_products_by_single_category( $parent_id, $exclude_id );
					$parent_products = array_diff( $parent_products, $all_products );
					$needed          = $this->max_products - count( $all_products );

					if ( $needed > 0 && ! empty( $parent_products ) ) {
						$parent_products = array_slice( array_values( $parent_products ), 0, $needed );
						$all_products    = array_merge( $all_products, $parent_products );
					}
					$processed_parents[] = $parent_id;
				}
			} else {
				$top_products = $this->get_products_by_single_category( $cat_id, $exclude_id );
				$all_products = array_values( array_unique( array_merge( $all_products, $top_products ) ) );
			}

			if ( count( $all_products ) >= $this->max_products ) {
				break;
			}
		}

		return array_values( array_unique( array_map( 'absint', $all_products ) ) );
	}

	/**
	 * @param int $cat_id Category term ID.
	 * @return int
	 */
	private function get_category_parent( int $cat_id ): int {
		$term = get_term( $cat_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return 0;
		}
		return (int) $term->parent;
	}

	/**
	 * @param string $sql Prepared SQL without stock clause.
	 * @return int[]
	 */
	private function run_product_query( string $sql ): array {
		global $wpdb;

		$stock_clause = " AND tr.object_id IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE meta_key = '_stock_status' AND meta_value = 'instock'
		)";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql built with prepare above.
		$results = $this->exclude_out_of_stock
			? $wpdb->get_col( $sql . $stock_clause )
			: $wpdb->get_col( $sql );

		if ( empty( $results ) && $this->exclude_out_of_stock ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_col( $sql );
		}

		return is_array( $results ) ? array_map( 'absint', $results ) : array();
	}

	/**
	 * @param int   $product_id  Source product.
	 * @param int[] $existing    Already selected IDs.
	 * @param int   $limit       Max new IDs.
	 * @return int[]
	 */
	private function get_products_by_title_tokens( int $product_id, array $existing, int $limit ): array {
		if ( $limit <= 0 ) {
			return array();
		}

		$title  = get_the_title( $product_id );
		$tokens = $this->extract_title_tokens( $title );

		if ( empty( $tokens ) ) {
			return array();
		}

		global $wpdb;

		$exclude   = array_merge( array( $product_id ), $existing );
		$scores    = array();
		$not_in    = ! empty( $exclude ) ? ' AND p.ID NOT IN (' . implode( ',', array_map( 'absint', $exclude ) ) . ')' : '';
		$stock_sql = '';

		if ( $this->exclude_out_of_stock ) {
			$stock_sql = " AND p.ID IN (
				SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_stock_status' AND meta_value = 'instock'
			)";
		}

		foreach ( $tokens as $token ) {
			$like = '%' . $wpdb->esc_like( $token ) . '%';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- LIKE pattern escaped.
			$sql = $wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				WHERE p.post_type = 'product'
				AND p.post_status = 'publish'
				AND p.post_title LIKE %s
				{$not_in}
				{$stock_sql}
				LIMIT %d",
				$like,
				max( $limit * 3, 20 )
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ids = $wpdb->get_col( $sql );
			if ( ! is_array( $ids ) ) {
				continue;
			}

			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( $id <= 0 ) {
					continue;
				}
				if ( ! isset( $scores[ $id ] ) ) {
					$scores[ $id ] = 0;
				}
				++$scores[ $id ];
			}
		}

		if ( empty( $scores ) ) {
			return array();
		}

		arsort( $scores, SORT_NUMERIC );

		return array_slice( array_map( 'intval', array_keys( $scores ) ), 0, $limit );
	}

	/**
	 * @param string $title Product title.
	 * @return string[]
	 */
	private function extract_title_tokens( string $title ): array {
		$title = strtolower( wp_strip_all_tags( $title ) );
		if ( '' === $title ) {
			return array();
		}

		$tokens = array();

		if ( preg_match_all( '/[a-z0-9][a-z0-9\-_]{2,}/i', $title, $model_matches ) ) {
			$tokens = array_merge( $tokens, $model_matches[0] );
		}

		$parts = preg_split( '/[\s\-\_\|\/\,\.\(\)\[\]：:]+/u', $title, -1, PREG_SPLIT_NO_EMPTY );
		if ( is_array( $parts ) ) {
			$tokens = array_merge( $tokens, $parts );
		}

		$stopwords = array(
			'the', 'and', 'for', 'with', 'from', 'new', 'sale', 'hot',
			'的', '和', '与', '及', '款', '色', '码', '号', '件', '套',
		);

		$min   = $this->title_min_token_length;
		$out   = array();
		$seen  = array();

		foreach ( $tokens as $token ) {
			$token = strtolower( trim( (string) $token ) );
			if ( strlen( $token ) < $min ) {
				continue;
			}
			if ( in_array( $token, $stopwords, true ) ) {
				continue;
			}
			if ( isset( $seen[ $token ] ) ) {
				continue;
			}
			$seen[ $token ] = true;
			$out[]          = $token;
			if ( count( $out ) >= 8 ) {
				break;
			}
		}

		return $out;
	}
}
