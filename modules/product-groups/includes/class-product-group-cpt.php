<?php
declare(strict_types=1);

/**
 * 产品组 CPT 注册。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * CPT `ts_product_group`。
 */
final class Tailwindscore_Product_Group_Cpt {

	public const POST_TYPE = 'ts_product_group';

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_post_type' ) );
		add_filter( 'use_block_editor_for_post_type', array( self::class, 'disable_block_editor' ), 10, 2 );
	}

	/**
	 * @return void
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( '产品组', 'tailwindscore' ),
					'singular_name'      => __( '产品组', 'tailwindscore' ),
					'add_new'            => __( '新增产品组', 'tailwindscore' ),
					'add_new_item'       => __( '新增产品组', 'tailwindscore' ),
					'edit_item'          => __( '编辑产品组', 'tailwindscore' ),
					'new_item'           => __( '新产品组', 'tailwindscore' ),
					'view_item'          => __( '查看产品组', 'tailwindscore' ),
					'search_items'       => __( 'Search groups, SKU, or product name', 'tailwindscore' ),
					'not_found'          => __( '找不到产品组', 'tailwindscore' ),
					'not_found_in_trash' => __( '回收站内没有产品组', 'tailwindscore' ),
					'all_items'          => __( 'Product Groups', 'tailwindscore' ),
					'menu_name'          => __( 'Product Groups', 'tailwindscore' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=product',
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'capability_type'     => 'product',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * @param bool   $use_block_editor 是否使用块编辑器。
	 * @param string $post_type        文章类型。
	 */
	public static function disable_block_editor( bool $use_block_editor, string $post_type ): bool {
		if ( self::POST_TYPE === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}
}
