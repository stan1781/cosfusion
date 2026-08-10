<?php
declare(strict_types=1);

/**
 * 博客文章查询辅助（相关 / 热门）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 同主分类相关文章（排除当前）。
 *
 * @param int $post_id 当前文章 ID。
 * @param int $limit   数量上限。
 * @return list<WP_Post>
 */
function tailwindscore_get_related_posts( int $post_id = 0, int $limit = 3 ): array {
	$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
	$limit   = max( 1, min( 12, $limit ) );

	if ( $post_id <= 0 ) {
		return array();
	}

	$categories = get_the_category( $post_id );
	if ( empty( $categories ) || ! $categories[0] instanceof WP_Term ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'post__not_in'        => array( $post_id ),
			'category__in'        => array( (int) $categories[0]->term_id ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);

	$posts = array();
	foreach ( $query->posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$posts[] = $post;
		}
	}

	return $posts;
}

/**
 * 热门文章（按评论数）。
 *
 * @param int $limit 数量上限。
 * @return list<WP_Post>
 */
function tailwindscore_get_popular_posts( int $limit = 5 ): array {
	$limit = max( 1, min( 12, $limit ) );

	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'comment_count',
			'order'               => 'DESC',
		)
	);

	$posts = array();
	foreach ( $query->posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$posts[] = $post;
		}
	}

	return $posts;
}
