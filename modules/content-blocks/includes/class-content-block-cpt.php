<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 内容区块 — CPT 注册、修订限制与 CSS meta 修订同步。
 */
final class Tailwindscore_Content_Block_Cpt {

	public const POST_TYPE = 'ts_block';

	public const CSS_META_KEY = '_ts_block_css';

	public static function init(): void {
		add_action( 'init', array( self::class, 'register_post_type' ) );
		add_filter( 'use_block_editor_for_post_type', array( self::class, 'disable_block_editor' ), 10, 2 );
		add_filter( 'wp_revisions_to_keep', array( self::class, 'limit_revisions' ), 10, 2 );
		add_action( 'wp_save_post_revision', array( self::class, 'copy_css_to_revision' ), 10, 2 );
		add_action( 'wp_restore_post_revision', array( self::class, 'restore_css_from_revision' ), 10, 2 );
	}

	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( '内容区块', 'tailwindscore' ),
					'singular_name'      => __( '内容区块', 'tailwindscore' ),
					'add_new'            => __( '新增区块', 'tailwindscore' ),
					'add_new_item'       => __( '新增内容区块', 'tailwindscore' ),
					'edit_item'          => __( '编辑内容区块', 'tailwindscore' ),
					'new_item'           => __( '新内容区块', 'tailwindscore' ),
					'view_item'          => __( '查看内容区块', 'tailwindscore' ),
					'search_items'       => __( '搜索内容区块', 'tailwindscore' ),
					'not_found'          => __( '找不到内容区块', 'tailwindscore' ),
					'not_found_in_trash' => __( '回收站内没有内容区块', 'tailwindscore' ),
					'all_items'          => __( '内容区块', 'tailwindscore' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'themes.php',
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'capability_type'     => 'page',
				'hierarchical'        => false,
				'menu_icon'           => 'dashicons-editor-code',
				'supports'            => array( 'title', 'revisions' ),
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * @param bool   $use_block_editor Whether the block editor is enabled.
	 * @param string $post_type        Post type slug.
	 */
	public static function disable_block_editor( bool $use_block_editor, string $post_type ): bool {
		if ( self::POST_TYPE === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * @param int|string $num  Default revision count.
	 * @param WP_Post    $post Post object.
	 * @return int|string
	 */
	public static function limit_revisions( $num, WP_Post $post ) {
		if ( self::POST_TYPE === $post->post_type ) {
			return defined( 'TAILWINDSCORE_BLOCK_REVISIONS_TO_KEEP' ) ? (int) TAILWINDSCORE_BLOCK_REVISIONS_TO_KEEP : 3;
		}

		return $num;
	}

	public static function copy_css_to_revision( int $revision_id, int $parent_id ): void {
		$parent = get_post( $parent_id );
		if ( ! $parent instanceof WP_Post || self::POST_TYPE !== $parent->post_type ) {
			return;
		}

		$css = get_post_meta( $parent_id, self::CSS_META_KEY, true );
		if ( is_string( $css ) && '' !== $css ) {
			update_metadata( 'post', $revision_id, self::CSS_META_KEY, $css );
			return;
		}

		delete_metadata( 'post', $revision_id, self::CSS_META_KEY );
	}

	public static function restore_css_from_revision( int $post_id, int $revision_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		$css = get_post_meta( $revision_id, self::CSS_META_KEY, true );
		if ( is_string( $css ) && '' !== $css ) {
			update_post_meta( $post_id, self::CSS_META_KEY, $css );
			return;
		}

		delete_post_meta( $post_id, self::CSS_META_KEY );
	}
}
