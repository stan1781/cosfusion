<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 按非空 product_cat 树同步到指定导航菜单。
 *
 * 保留非分类项；已有分类项保留标题 / CSS 类 / 缩略图 meta。
 */

/**
 * 是否启用自动同步。
 */
function tailwindscore_nav_product_cat_sync_auto_enabled(): bool {
	return (bool) tailwindscore_mod( 'nav_product_cat_sync_auto', false );
}

/**
 * 解析自动同步目标菜单 ID（0 = primary 位置）。
 */
function tailwindscore_nav_product_cat_sync_resolve_menu_id( int $explicit_menu_id = 0 ): int {
	if ( $explicit_menu_id > 0 ) {
		$menu = wp_get_nav_menu_object( $explicit_menu_id );

		return $menu ? (int) $menu->term_id : 0;
	}

	$configured = (int) tailwindscore_mod( 'nav_product_cat_sync_menu_id', 0 );
	if ( $configured > 0 ) {
		$menu = wp_get_nav_menu_object( $configured );

		return $menu ? (int) $menu->term_id : 0;
	}

	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		return (int) $locations['primary'];
	}

	return 0;
}

/**
 * 拉取全部 product_cat（含空），按 name 排序。
 *
 * @return array<int, WP_Term> term_id => term
 */
function tailwindscore_nav_product_cat_sync_load_terms(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	$by_id = array();
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			$by_id[ (int) $term->term_id ] = $term;
		}
	}

	return $by_id;
}

/**
 * 顶级 product_cat（parent=0），按 name 排序。
 *
 * @param array<int, WP_Term> $terms
 * @return list<WP_Term>
 */
function tailwindscore_nav_product_cat_sync_top_level_terms( array $terms ): array {
	$roots = array();
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			$roots[] = $term;
		}
	}

	usort(
		$roots,
		static function ( WP_Term $a, WP_Term $b ): int {
			return strcasecmp( $a->name, $b->name );
		}
	);

	return $roots;
}

/**
 * 选中一级分类及其全部后代（含自身）。
 *
 * @param list<int>           $root_ids
 * @param array<int, WP_Term> $terms
 * @return array<int, true>
 */
function tailwindscore_nav_product_cat_sync_expand_root_scope( array $root_ids, array $terms ): array {
	$root_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'absint', $root_ids ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);

	if ( array() === $root_ids ) {
		return array();
	}

	$children = array();
	foreach ( $terms as $tid => $term ) {
		$parent = (int) $term->parent;
		if ( ! isset( $children[ $parent ] ) ) {
			$children[ $parent ] = array();
		}
		$children[ $parent ][] = (int) $tid;
	}

	$scope = array();
	$stack = $root_ids;
	while ( array() !== $stack ) {
		$tid = (int) array_pop( $stack );
		if ( isset( $scope[ $tid ] ) || ! isset( $terms[ $tid ] ) ) {
			continue;
		}
		$scope[ $tid ] = true;
		if ( ! empty( $children[ $tid ] ) ) {
			foreach ( $children[ $tid ] as $child_id ) {
				$stack[] = (int) $child_id;
			}
		}
	}

	return $scope;
}

/**
 * 剪枝：保留 count>0 或拥有需同步后代的祖先。
 *
 * @param array<int, WP_Term> $terms term_id => term
 * @return array<int, true> 需同步的 term_id 集合
 */
function tailwindscore_nav_product_cat_sync_prune_included( array $terms ): array {
	if ( array() === $terms ) {
		return array();
	}

	$children = array();
	foreach ( $terms as $tid => $term ) {
		$parent = (int) $term->parent;
		if ( ! isset( $children[ $parent ] ) ) {
			$children[ $parent ] = array();
		}
		$children[ $parent ][] = (int) $tid;
	}

	$include = array();

	$walk = static function ( int $tid ) use ( &$walk, &$include, $terms, $children ): bool {
		$keep = ( (int) $terms[ $tid ]->count ) > 0;

		if ( ! empty( $children[ $tid ] ) ) {
			foreach ( $children[ $tid ] as $child_id ) {
				if ( $walk( $child_id ) ) {
					$keep = true;
				}
			}
		}

		if ( $keep ) {
			$include[ $tid ] = true;
		}

		return $keep;
	};

	if ( ! empty( $children[0] ) ) {
		foreach ( $children[0] as $root_id ) {
			$walk( $root_id );
		}
	}

	// 孤儿 term（父级不在表中）仍参与剪枝。
	foreach ( $terms as $tid => $_term ) {
		if ( isset( $include[ $tid ] ) ) {
			continue;
		}
		$parent = (int) $_term->parent;
		if ( $parent > 0 && ! isset( $terms[ $parent ] ) ) {
			$walk( (int) $tid );
		}
	}

	return $include;
}

/**
 * 最近已纳入同步的祖先 term_id；无则 0。
 *
 * @param array<int, WP_Term> $terms
 * @param array<int, true>    $include
 */
function tailwindscore_nav_product_cat_sync_menu_parent_term_id( int $term_id, array $terms, array $include ): int {
	if ( ! isset( $terms[ $term_id ] ) ) {
		return 0;
	}

	$parent = (int) $terms[ $term_id ]->parent;
	while ( $parent > 0 ) {
		if ( isset( $include[ $parent ] ) ) {
			return $parent;
		}
		if ( ! isset( $terms[ $parent ] ) ) {
			break;
		}
		$parent = (int) $terms[ $parent ]->parent;
	}

	return 0;
}

/**
 * @param array<int, WP_Term> $terms
 * @param array<int, true>    $include
 * @return array<int, list<int>> menu_parent_term_id => child term_ids（name 序）
 */
function tailwindscore_nav_product_cat_sync_group_by_menu_parent( array $terms, array $include ): array {
	$groups = array();

	foreach ( array_keys( $include ) as $tid ) {
		$tid         = (int) $tid;
		$menu_parent = tailwindscore_nav_product_cat_sync_menu_parent_term_id( $tid, $terms, $include );
		if ( ! isset( $groups[ $menu_parent ] ) ) {
			$groups[ $menu_parent ] = array();
		}
		$groups[ $menu_parent ][] = $tid;
	}

	foreach ( $groups as $parent => $ids ) {
		usort(
			$ids,
			static function ( int $a, int $b ) use ( $terms ): int {
				return strcasecmp( $terms[ $a ]->name, $terms[ $b ]->name );
			}
		);
		$groups[ $parent ] = $ids;
	}

	return $groups;
}

/**
 * 同级非 product_cat 菜单项的最大 menu_order。
 *
 * @param list<object> $items
 */
function tailwindscore_nav_product_cat_sync_max_non_cat_order( array $items, int $parent_db_id ): int {
	$max = 0;

	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent !== $parent_db_id ) {
			continue;
		}
		if ( 'taxonomy' === $item->type && 'product_cat' === $item->object ) {
			continue;
		}
		$max = max( $max, (int) $item->menu_order );
	}

	return $max;
}

/**
 * 回读现有菜单项字段，避免 wp_update_nav_menu_item 用空默认值覆盖。
 *
 * @return array<string, mixed>
 */
function tailwindscore_nav_product_cat_sync_existing_item_args( int $db_id ): array {
	$post = get_post( $db_id );
	if ( ! $post ) {
		return array();
	}

	$classes = get_post_meta( $db_id, '_menu_item_classes', true );
	if ( ! is_array( $classes ) ) {
		$classes = array();
	}

	return array(
		'menu-item-title'       => $post->post_title,
		'menu-item-description' => $post->post_content,
		'menu-item-attr-title'  => $post->post_excerpt,
		'menu-item-target'      => (string) get_post_meta( $db_id, '_menu_item_target', true ),
		'menu-item-classes'     => implode( ' ', array_map( 'strval', $classes ) ),
		'menu-item-xfn'         => (string) get_post_meta( $db_id, '_menu_item_xfn', true ),
		'menu-item-status'      => 'publish',
	);
}

/**
 * 将单个 product_cat 写入菜单（新建或更新父子/排序）。
 *
 * @return array{db_id: int, created: bool}|WP_Error
 */
function tailwindscore_nav_product_cat_sync_upsert_item(
	int $menu_id,
	WP_Term $term,
	int $parent_db_id,
	int $position,
	int $existing_db_id
) {
	$args = array(
		'menu-item-object-id' => (int) $term->term_id,
		'menu-item-object'    => 'product_cat',
		'menu-item-parent-id' => $parent_db_id,
		'menu-item-position'  => $position,
		'menu-item-type'      => 'taxonomy',
		'menu-item-url'       => '',
		'menu-item-status'    => 'publish',
	);

	if ( $existing_db_id > 0 ) {
		$args = array_merge( tailwindscore_nav_product_cat_sync_existing_item_args( $existing_db_id ), $args );
		$result = wp_update_nav_menu_item( $menu_id, $existing_db_id, $args );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'db_id'   => (int) $result,
			'created' => false,
		);
	}

	$args['menu-item-title'] = $term->name;
	$result                  = wp_update_nav_menu_item( $menu_id, 0, $args );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return array(
		'db_id'   => (int) $result,
		'created' => true,
	);
}

/**
 * 执行同步。
 *
 * @param int      $menu_id        菜单 ID；0 则按配置 / primary 解析。
 * @param list<int> $root_term_ids 一级分类 term_id；空数组 = 全量同步。
 * @return array{ok: bool, menu_id: int, added: int, updated: int, removed: int, message?: string}
 */
function tailwindscore_sync_product_cat_menu( int $menu_id, array $root_term_ids = array() ): array {
	$menu_id = tailwindscore_nav_product_cat_sync_resolve_menu_id( $menu_id );
	if ( $menu_id <= 0 ) {
		return array(
			'ok'      => false,
			'menu_id' => 0,
			'added'   => 0,
			'updated' => 0,
			'removed' => 0,
			'message' => __( 'No target menu found for product category sync.', 'tailwindscore' ),
		);
	}

	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array(
			'ok'      => false,
			'menu_id' => $menu_id,
			'added'   => 0,
			'updated' => 0,
			'removed' => 0,
			'message' => __( 'Product categories taxonomy is not available.', 'tailwindscore' ),
		);
	}

	$terms   = tailwindscore_nav_product_cat_sync_load_terms();
	$include = tailwindscore_nav_product_cat_sync_prune_included( $terms );

	$selective = array() !== $root_term_ids;
	$scope     = array();
	if ( $selective ) {
		$scope = tailwindscore_nav_product_cat_sync_expand_root_scope( $root_term_ids, $terms );
		if ( array() === $scope ) {
			return array(
				'ok'      => false,
				'menu_id' => $menu_id,
				'added'   => 0,
				'updated' => 0,
				'removed' => 0,
				'message' => __( 'No valid top-level product categories selected.', 'tailwindscore' ),
			);
		}
		$include = array_intersect_key( $include, $scope );
	}

	$raw_items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
	if ( ! is_array( $raw_items ) ) {
		$raw_items = array();
	}

	/** @var array<int, object> $by_term term_id => menu item（仅同步范围内） */
	$by_term   = array();
	$removed   = 0;
	$to_delete = array();

	foreach ( $raw_items as $item ) {
		if ( 'taxonomy' !== $item->type || 'product_cat' !== $item->object ) {
			continue;
		}

		$tid  = (int) $item->object_id;
		$dbid = (int) $item->ID;

		// 选择性同步：范围外的分类项完全不动。
		if ( $selective && ! isset( $scope[ $tid ] ) ) {
			continue;
		}

		if ( isset( $by_term[ $tid ] ) ) {
			$to_delete[] = $dbid;
			continue;
		}

		$by_term[ $tid ] = $item;
	}

	foreach ( $by_term as $tid => $item ) {
		if ( ! isset( $include[ $tid ] ) ) {
			$to_delete[] = (int) $item->ID;
			unset( $by_term[ $tid ] );
		}
	}

	foreach ( array_unique( $to_delete ) as $db_id ) {
		wp_delete_post( (int) $db_id, true );
		++$removed;
	}

	// 删除后重新读取，供非分类项落位计算。
	$items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
	if ( ! is_array( $items ) ) {
		$items = array();
	}

	$groups = tailwindscore_nav_product_cat_sync_group_by_menu_parent( $terms, $include );

	/** @var array<int, int> $term_to_db */
	$term_to_db = array();
	foreach ( $by_term as $tid => $item ) {
		$term_to_db[ (int) $tid ] = (int) $item->ID;
	}

	// 选择性时：范围外已有分类项也要能作为「菜单父级」引用（通常不需要），
	// 但顶级落位需知道全部顶级项的 order。
	if ( $selective ) {
		foreach ( $items as $item ) {
			if ( 'taxonomy' !== $item->type || 'product_cat' !== $item->object ) {
				continue;
			}
			$tid = (int) $item->object_id;
			if ( ! isset( $term_to_db[ $tid ] ) ) {
				$term_to_db[ $tid ] = (int) $item->ID;
			}
		}
	}

	$added   = 0;
	$updated = 0;

	$pending_parents = array( 0 );
	$done_parents    = array();

	while ( array() !== $pending_parents ) {
		$parent_term = (int) array_shift( $pending_parents );
		if ( isset( $done_parents[ $parent_term ] ) ) {
			continue;
		}
		$done_parents[ $parent_term ] = true;

		if ( empty( $groups[ $parent_term ] ) ) {
			continue;
		}

		$parent_db = 0;
		if ( $parent_term > 0 ) {
			if ( empty( $term_to_db[ $parent_term ] ) ) {
				continue;
			}
			$parent_db = $term_to_db[ $parent_term ];
		}

		$is_top_level_group = ( 0 === $parent_term );
		$next_append        = 0;
		if ( $is_top_level_group ) {
			foreach ( $items as $item ) {
				if ( 0 === (int) $item->menu_item_parent ) {
					$next_append = max( $next_append, (int) $item->menu_order );
				}
			}
		} else {
			$next_append = tailwindscore_nav_product_cat_sync_max_non_cat_order( $items, $parent_db );
		}

		$child_slot = $next_append;

		foreach ( $groups[ $parent_term ] as $tid ) {
			$tid      = (int) $tid;
			$term     = $terms[ $tid ];
			$existing = isset( $by_term[ $tid ] ) ? (int) $by_term[ $tid ]->ID : 0;

			if ( $is_top_level_group && $existing > 0 ) {
				// 已有顶级项：保持原 menu_order，避免搅动未选中的兄弟项。
				$position = (int) $by_term[ $tid ]->menu_order;
			} else {
				++$child_slot;
				$position = $child_slot;
			}

			$result = tailwindscore_nav_product_cat_sync_upsert_item(
				$menu_id,
				$term,
				$parent_db,
				$position,
				$existing
			);

			if ( is_wp_error( $result ) ) {
				continue;
			}

			$term_to_db[ $tid ] = $result['db_id'];
			if ( $result['created'] ) {
				++$added;
				// 新顶级项占用了 append 槽，刷新 items 缓存中的 max 不必；child_slot 已递增。
			} else {
				++$updated;
			}

			if ( ! empty( $groups[ $tid ] ) ) {
				$pending_parents[] = $tid;
			}
		}
	}

	clean_term_cache( $menu_id, 'nav_menu' );

	return array(
		'ok'      => true,
		'menu_id' => $menu_id,
		'added'   => $added,
		'updated' => $updated,
		'removed' => $removed,
	);
}

/**
 * 自动同步：同一请求内合并到 shutdown 执行一次。
 */
function tailwindscore_nav_product_cat_sync_schedule_auto(): void {
	if ( ! tailwindscore_nav_product_cat_sync_auto_enabled() ) {
		return;
	}

	if ( ! empty( $GLOBALS['tailwindscore_nav_product_cat_sync_scheduled'] ) ) {
		return;
	}

	$GLOBALS['tailwindscore_nav_product_cat_sync_scheduled'] = true;
	add_action( 'shutdown', 'tailwindscore_nav_product_cat_sync_run_scheduled', 20 );
}

/**
 * shutdown 回调：执行自动同步。
 */
function tailwindscore_nav_product_cat_sync_run_scheduled(): void {
	if ( ! tailwindscore_nav_product_cat_sync_auto_enabled() ) {
		return;
	}

	$menu_id = tailwindscore_nav_product_cat_sync_resolve_menu_id( 0 );
	if ( $menu_id <= 0 ) {
		return;
	}

	tailwindscore_sync_product_cat_menu( $menu_id );
}

/**
 * product_cat 变更时调度自动同步。
 *
 * @param mixed $arg1 Term id / tt_id 等（忽略）。
 * @param mixed $arg2 Taxonomy 或其它。
 */
function tailwindscore_nav_product_cat_sync_on_term_change( $arg1 = null, $arg2 = null ): void {
	unset( $arg1 );

	if ( null !== $arg2 && is_string( $arg2 ) && 'product_cat' !== $arg2 ) {
		return;
	}

	tailwindscore_nav_product_cat_sync_schedule_auto();
}

/**
 * edited_term_taxonomy：仅 product_cat。
 *
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function tailwindscore_nav_product_cat_sync_on_edited_term_taxonomy( $tt_id, $taxonomy ): void {
	unset( $tt_id );
	if ( 'product_cat' !== $taxonomy ) {
		return;
	}
	tailwindscore_nav_product_cat_sync_schedule_auto();
}

add_action( 'created_product_cat', 'tailwindscore_nav_product_cat_sync_on_term_change', 20, 2 );
add_action( 'edited_product_cat', 'tailwindscore_nav_product_cat_sync_on_term_change', 20, 2 );
add_action( 'delete_product_cat', 'tailwindscore_nav_product_cat_sync_on_term_change', 20, 2 );
add_action( 'edited_term_taxonomy', 'tailwindscore_nav_product_cat_sync_on_edited_term_taxonomy', 20, 2 );

/**
 * 手动同步 admin-post。
 */
function tailwindscore_nav_product_cat_sync_handle_admin_post(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to sync menus.', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_sync_product_cat_menu' );

	$menu_id = isset( $_REQUEST['menu'] ) ? absint( $_REQUEST['menu'] ) : 0;

	$root_ids = array();
	if ( isset( $_REQUEST['roots'] ) && is_array( $_REQUEST['roots'] ) ) {
		foreach ( $_REQUEST['roots'] as $root_id ) {
			$root_ids[] = absint( $root_id );
		}
	} elseif ( isset( $_REQUEST['roots'] ) && is_string( $_REQUEST['roots'] ) ) {
		foreach ( explode( ',', wp_unslash( $_REQUEST['roots'] ) ) as $root_id ) {
			$root_ids[] = absint( $root_id );
		}
	}

	$root_ids = array_values( array_unique( array_filter( $root_ids ) ) );

	$sync_all = isset( $_REQUEST['sync_all'] ) && '1' === (string) wp_unslash( $_REQUEST['sync_all'] );

	if ( ! $sync_all && array() === $root_ids ) {
		set_transient(
			'ts_nav_cat_sync_notice_' . get_current_user_id(),
			array(
				'ok'      => false,
				'menu_id' => $menu_id,
				'added'   => 0,
				'updated' => 0,
				'removed' => 0,
				'message' => __( 'Select at least one top-level category, or use Sync all.', 'tailwindscore' ),
			),
			120
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'menu'             => $menu_id,
					'ts_cat_sync_done' => '0',
				),
				admin_url( 'nav-menus.php' )
			)
		);
		exit;
	}

	$result = tailwindscore_sync_product_cat_menu( $menu_id, $sync_all ? array() : $root_ids );

	set_transient(
		'ts_nav_cat_sync_notice_' . get_current_user_id(),
		$result,
		120
	);

	$redirect_menu = $result['menu_id'] > 0 ? $result['menu_id'] : $menu_id;
	$redirect      = add_query_arg(
		array(
			'menu'             => $redirect_menu,
			'ts_cat_sync_done' => $result['ok'] ? '1' : '0',
		),
		admin_url( 'nav-menus.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_ts_sync_product_cat_menu', 'tailwindscore_nav_product_cat_sync_handle_admin_post' );

/**
 * 同步结果 admin notice。
 */
function tailwindscore_nav_product_cat_sync_admin_notice(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'nav-menus' !== $screen->id ) {
		return;
	}

	$key    = 'ts_nav_cat_sync_notice_' . get_current_user_id();
	$result = get_transient( $key );
	if ( ! is_array( $result ) ) {
		return;
	}

	delete_transient( $key );

	if ( empty( $result['ok'] ) ) {
		$message = isset( $result['message'] )
			? (string) $result['message']
			: __( 'Product category sync failed.', 'tailwindscore' );
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
		return;
	}

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: added count, 2: updated count, 3: removed count */
				__( 'Product categories synced. Added: %1$d, Updated: %2$d, Removed: %3$d.', 'tailwindscore' ),
				(int) ( $result['added'] ?? 0 ),
				(int) ( $result['updated'] ?? 0 ),
				(int) ( $result['removed'] ?? 0 )
			)
		)
	);
}
add_action( 'admin_notices', 'tailwindscore_nav_product_cat_sync_admin_notice' );

/**
 * 菜单编辑页：一级分类多选 + Sync selected / Sync all。
 *
 * @param string $hook_suffix Admin hook.
 */
function tailwindscore_nav_product_cat_sync_admin_enqueue( string $hook_suffix ): void {
	if ( 'nav-menus.php' !== $hook_suffix ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$menu_id = 0;
	if ( isset( $_GET['menu'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$menu_id = absint( $_GET['menu'] );
	}
	if ( $menu_id <= 0 ) {
		$menu_id = (int) get_user_option( 'nav_menu_recently_edited' );
	}

	$terms = tailwindscore_nav_product_cat_sync_load_terms();
	$roots = tailwindscore_nav_product_cat_sync_top_level_terms( $terms );
	$root_choices = array();
	foreach ( $roots as $term ) {
		$root_choices[] = array(
			'id'    => (int) $term->term_id,
			'name'  => $term->name,
			'count' => (int) $term->count,
		);
	}

	$action_url = admin_url( 'admin-post.php' );
	$nonce      = wp_create_nonce( 'ts_sync_product_cat_menu' );

	wp_register_script( 'tailwindscore-nav-cat-sync', false, array( 'jquery', 'nav-menu' ), '1.1.0', true );
	wp_enqueue_script( 'tailwindscore-nav-cat-sync' );
	wp_add_inline_style(
		'nav-menus',
		'#ts-sync-product-cat-panel{margin:12px 0 0;padding:12px 14px;background:#fff;border:1px solid #c3c4c7;box-shadow:0 1px 1px rgba(0,0,0,.04)}'
		. '#ts-sync-product-cat-panel h3{margin:0 0 8px;font-size:13px}'
		. '#ts-sync-product-cat-panel .ts-sync-roots{display:flex;flex-wrap:wrap;gap:6px 14px;margin:0 0 10px;max-height:9em;overflow:auto}'
		. '#ts-sync-product-cat-panel .ts-sync-roots label{display:inline-flex;align-items:center;gap:4px;white-space:nowrap}'
		. '#ts-sync-product-cat-panel .ts-sync-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}'
		. '#ts-sync-product-cat-panel .description{margin:8px 0 0}'
	);

	wp_add_inline_script(
		'tailwindscore-nav-cat-sync',
		'(function () {
			var actionUrl = ' . wp_json_encode( $action_url ) . ';
			var nonce = ' . wp_json_encode( $nonce ) . ';
			var menuIdHint = ' . wp_json_encode( (string) $menu_id ) . ';
			var roots = ' . wp_json_encode( $root_choices ) . ';
			var i18n = {
				title: ' . wp_json_encode( __( 'Sync product categories', 'tailwindscore' ) ) . ',
				selectAll: ' . wp_json_encode( __( 'Select all', 'tailwindscore' ) ) . ',
				selectNone: ' . wp_json_encode( __( 'Select none', 'tailwindscore' ) ) . ',
				syncSelected: ' . wp_json_encode( __( 'Sync selected', 'tailwindscore' ) ) . ',
				syncAll: ' . wp_json_encode( __( 'Sync all', 'tailwindscore' ) ) . ',
				help: ' . wp_json_encode( __( 'Choose one or more top-level categories to sync (subtree only). Unselected category trees in the menu are left unchanged. Empty leaf categories are skipped.', 'tailwindscore' ) ) . ',
				confirmSelected: ' . wp_json_encode( __( 'Sync the selected top-level categories into this menu?', 'tailwindscore' ) ) . ',
				confirmAll: ' . wp_json_encode( __( 'Sync ALL product categories into this menu? Missing category items will be removed.', 'tailwindscore' ) ) . ',
				needSelection: ' . wp_json_encode( __( 'Select at least one top-level category.', 'tailwindscore' ) ) . '
			};

			function currentMenuId() {
				var input = document.getElementById("menu");
				if (input && input.value) {
					return String(parseInt(input.value, 10) || 0);
				}
				return menuIdHint || "0";
			}

			function selectedRootIds() {
				return Array.prototype.map.call(
					document.querySelectorAll("#ts-sync-product-cat-panel input.ts-sync-root:checked"),
					function (el) { return el.value; }
				);
			}

			function submitSync(syncAll) {
				var menuId = currentMenuId();
				if (!syncAll) {
					var ids = selectedRootIds();
					if (!ids.length) {
						window.alert(i18n.needSelection);
						return;
					}
					if (!window.confirm(i18n.confirmSelected)) {
						return;
					}
				} else if (!window.confirm(i18n.confirmAll)) {
					return;
				}

				var form = document.createElement("form");
				form.method = "POST";
				form.action = actionUrl;
				form.style.display = "none";

				function add(name, value) {
					var input = document.createElement("input");
					input.type = "hidden";
					input.name = name;
					input.value = value;
					form.appendChild(input);
				}

				add("action", "ts_sync_product_cat_menu");
				add("_wpnonce", nonce);
				add("menu", menuId);
				if (syncAll) {
					add("sync_all", "1");
				} else {
					selectedRootIds().forEach(function (id) {
						add("roots[]", id);
					});
				}
				document.body.appendChild(form);
				form.submit();
			}

			function injectPanel() {
				var frame = document.getElementById("nav-menus-frame");
				var footer = document.getElementById("nav-menu-footer");
				if (!footer || document.getElementById("ts-sync-product-cat-panel")) {
					return;
				}

				var panel = document.createElement("div");
				panel.id = "ts-sync-product-cat-panel";

				var heading = document.createElement("h3");
				heading.textContent = i18n.title;
				panel.appendChild(heading);

				var list = document.createElement("div");
				list.className = "ts-sync-roots";
				roots.forEach(function (root) {
					var label = document.createElement("label");
					var cb = document.createElement("input");
					cb.type = "checkbox";
					cb.className = "ts-sync-root";
					cb.value = String(root.id);
					label.appendChild(cb);
					label.appendChild(document.createTextNode(" " + root.name + " (" + root.count + ")"));
					list.appendChild(label);
				});
				panel.appendChild(list);

				var actions = document.createElement("div");
				actions.className = "ts-sync-actions";

				var btnAll = document.createElement("button");
				btnAll.type = "button";
				btnAll.className = "button-link";
				btnAll.textContent = i18n.selectAll;
				btnAll.addEventListener("click", function () {
					panel.querySelectorAll("input.ts-sync-root").forEach(function (el) { el.checked = true; });
				});

				var btnNone = document.createElement("button");
				btnNone.type = "button";
				btnNone.className = "button-link";
				btnNone.textContent = i18n.selectNone;
				btnNone.addEventListener("click", function () {
					panel.querySelectorAll("input.ts-sync-root").forEach(function (el) { el.checked = false; });
				});

				var btnSelected = document.createElement("button");
				btnSelected.type = "button";
				btnSelected.className = "button button-secondary";
				btnSelected.textContent = i18n.syncSelected;
				btnSelected.addEventListener("click", function () { submitSync(false); });

				var btnSyncAll = document.createElement("button");
				btnSyncAll.type = "button";
				btnSyncAll.className = "button";
				btnSyncAll.textContent = i18n.syncAll;
				btnSyncAll.addEventListener("click", function () { submitSync(true); });

				actions.appendChild(btnAll);
				actions.appendChild(btnNone);
				actions.appendChild(btnSelected);
				actions.appendChild(btnSyncAll);
				panel.appendChild(actions);

				var help = document.createElement("p");
				help.className = "description";
				help.textContent = i18n.help;
				panel.appendChild(help);

				if (frame && footer.parentNode) {
					footer.parentNode.insertBefore(panel, footer);
				} else {
					footer.insertBefore(panel, footer.firstChild);
				}
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", injectPanel);
			} else {
				injectPanel();
			}
		})();'
	);
}
add_action( 'admin_enqueue_scripts', 'tailwindscore_nav_product_cat_sync_admin_enqueue', 30 );

/**
 * Kirki：自动同步开关与目标菜单。
 *
 * @param string                $cfg     Config ID.
 * @param array<string, string> $targets Panel / section map.
 */
function tailwindscore_nav_product_cat_sync_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$section = 'tailwindscore_nav_product_cat_sync';
	$panel   = $targets['header'] ?? 'tailwindscore_header';

	Kirki::add_section(
		$section,
		array(
			'title'       => esc_html__( '商品分类菜单同步', 'tailwindscore' ),
			'description' => esc_html__( '将非空 product_cat 同步到指定导航菜单。手动可在外观→菜单勾选一级分类后 Sync selected；空叶子跳过，有商品后代的空父级仍保留。', 'tailwindscore' ),
			'panel'       => $panel,
			'priority'    => 45,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'nav_product_cat_sync_auto',
			'label'       => esc_html__( '分类变更时自动同步', 'tailwindscore' ),
			'description' => esc_html__( '创建 / 编辑 / 删除商品分类，或分类商品数量变化时，同步到下方目标菜单。默认关闭。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => false,
			'priority'    => 10,
		)
	);

	$choices = array(
		0 => esc_html__( 'Primary 菜单位置', 'tailwindscore' ),
	);
	$menus   = wp_get_nav_menus();
	if ( is_array( $menus ) ) {
		foreach ( $menus as $menu ) {
			$choices[ (int) $menu->term_id ] = $menu->name;
		}
	}

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'select',
			'settings'    => 'nav_product_cat_sync_menu_id',
			'label'       => esc_html__( '自动同步目标菜单', 'tailwindscore' ),
			'description' => esc_html__( '手动同步始终针对当前正在编辑的菜单。', 'tailwindscore' ),
			'section'     => $section,
			'default'     => 0,
			'priority'    => 20,
			'choices'     => $choices,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_nav_product_cat_sync_kirki_register', 10, 2 );
