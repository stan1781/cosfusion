<?php
declare(strict_types=1);

/**
 * 产品组数据读写。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 组 meta 与一组一品约束。
 */
final class Tailwindscore_Product_Group_Repository {

	public const META_PRODUCT_IDS        = '_ts_pg_product_ids';
	public const META_ATTRIBUTES         = '_ts_pg_attributes';
	public const META_ATTR_ACTIONS       = '_ts_pg_attr_actions';
	public const META_DISPLAY_ATTRIBUTE  = '_ts_pg_display_attribute';
	public const META_DISPLAY_MODE       = '_ts_pg_display_mode';
	public const META_MIGRATED_FROM      = '_ts_pg_migrated_from_iconic';
	public const META_PRODUCT_GROUP      = '_ts_pg_group_id';

	public const ACTION_NONE          = 'none';
	public const ACTION_DISABLE_IMAGE = 'disable_image';
	public const ACTION_HIDE_ROW      = 'hide_row';

	public const MODE_THUMBNAIL = 'thumbnail';
	public const MODE_BUTTON    = 'button';

	/**
	 * @return string[]
	 */
	public static function allowed_actions(): array {
		return array(
			self::ACTION_NONE,
			self::ACTION_DISABLE_IMAGE,
			self::ACTION_HIDE_ROW,
		);
	}

	/**
	 * @return string[]
	 */
	public static function allowed_display_modes(): array {
		return array(
			self::MODE_THUMBNAIL,
			self::MODE_BUTTON,
		);
	}

	/**
	 * 商品所属组 ID（publish/private/draft）。
	 *
	 * @param int $product_id 商品 ID。
	 */
	public static function get_group_id_for_product( int $product_id ): int {
		$product_id = absint( $product_id );
		if ( $product_id < 1 ) {
			return 0;
		}

		$cached = (int) get_post_meta( $product_id, self::META_PRODUCT_GROUP, true );
		if ( $cached > 0 && self::is_valid_group( $cached ) ) {
			$ids = self::get_product_ids( $cached );
			if ( in_array( $product_id, $ids, true ) ) {
				return $cached;
			}
		}

		$group_id = self::scan_group_containing_product( $product_id, 0 );
		if ( $group_id > 0 ) {
			update_post_meta( $product_id, self::META_PRODUCT_GROUP, $group_id );
		} else {
			delete_post_meta( $product_id, self::META_PRODUCT_GROUP );
		}

		return $group_id;
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return WP_Post|null
	 */
	public static function get_group_for_product( int $product_id ): ?WP_Post {
		$group_id = self::get_group_id_for_product( $product_id );
		if ( $group_id < 1 ) {
			return null;
		}

		$post = get_post( $group_id );
		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * @param int $group_id 组 ID。
	 * @return int[]
	 */
	public static function get_product_ids( int $group_id ): array {
		$raw = get_post_meta( $group_id, self::META_PRODUCT_IDS, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$ids = array_map( 'absint', $raw );
		return array_values( array_filter( $ids ) );
	}

	/**
	 * @param int $group_id 组 ID。
	 * @return string[]
	 */
	public static function get_attributes( int $group_id ): array {
		$raw = get_post_meta( $group_id, self::META_ATTRIBUTES, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out = array();
		foreach ( $raw as $attr ) {
			$attr = sanitize_title( (string) $attr );
			if ( '' !== $attr ) {
				$out[] = $attr;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param int $group_id 组 ID。
	 * @return array<string, string>
	 */
	public static function get_attr_actions( int $group_id ): array {
		$raw = get_post_meta( $group_id, self::META_ATTR_ACTIONS, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$allowed = self::allowed_actions();
		$out     = array();
		foreach ( $raw as $attr => $action ) {
			$attr   = sanitize_title( (string) $attr );
			$action = (string) $action;
			if ( '' === $attr || ! in_array( $action, $allowed, true ) ) {
				continue;
			}
			$out[ $attr ] = $action;
		}

		return $out;
	}

	/**
	 * 某属性在组内的动作（无组 / 未配置 → none）。
	 *
	 * @param int    $product_id     商品 ID。
	 * @param string $attribute_name 属性键。
	 */
	public static function get_attr_action_for_product( int $product_id, string $attribute_name ): string {
		$group_id = self::get_group_id_for_product( $product_id );
		if ( $group_id < 1 ) {
			return self::ACTION_NONE;
		}

		$actions = self::get_attr_actions( $group_id );
		if ( array() === $actions ) {
			return self::ACTION_NONE;
		}

		$direct = self::lookup_action( $actions, $attribute_name );
		if ( self::ACTION_NONE !== $direct ) {
			return $direct;
		}

		// disable_image：若动作配在组属性（如 Style）上，但当前商品没有该变体维，
		// 则回退到商品上的颜色类属性（如 Color），避免设置成空操作。
		return self::resolve_disable_image_fallback( $product_id, $group_id, $attribute_name, $actions );
	}

	/**
	 * 组缩略图 / 行标题用的显示属性（独立于变体动作）。
	 *
	 * @param int $group_id 组 ID。
	 */
	public static function get_display_attribute( int $group_id ): string {
		$raw = sanitize_title( (string) get_post_meta( $group_id, self::META_DISPLAY_ATTRIBUTE, true ) );
		if ( '' !== $raw ) {
			return $raw;
		}

		// 兼容旧数据：勾选列表首项。
		$attrs = self::get_attributes( $group_id );
		return $attrs[0] ?? '';
	}

	/**
	 * 组前台显示模式：缩略图或文字按钮。
	 *
	 * @param int $group_id 组 ID。
	 */
	public static function get_display_mode( int $group_id ): string {
		$raw = sanitize_key( (string) get_post_meta( $group_id, self::META_DISPLAY_MODE, true ) );
		if ( in_array( $raw, self::allowed_display_modes(), true ) ) {
			return $raw;
		}

		return self::MODE_THUMBNAIL;
	}

	/**
	 * @deprecated 使用 get_display_attribute()。
	 *
	 * @param int $group_id 组 ID。
	 */
	public static function get_primary_attribute( int $group_id ): string {
		return self::get_display_attribute( $group_id );
	}

	/**
	 * @param array<string, string> $actions        动作表。
	 * @param string                $attribute_name 属性键。
	 */
	private static function lookup_action( array $actions, string $attribute_name ): string {
		$candidates = self::attribute_key_candidates( $attribute_name );
		foreach ( $candidates as $key ) {
			if ( isset( $actions[ $key ] ) ) {
				return $actions[ $key ];
			}
		}
		return self::ACTION_NONE;
	}

	/**
	 * @param string $attribute_name 属性键。
	 * @return string[]
	 */
	private static function attribute_key_candidates( string $attribute_name ): array {
		$raw  = strtolower( trim( $attribute_name ) );
		$slug = sanitize_title( $attribute_name );
		$out  = array_filter( array( $slug, $raw ) );
		if ( str_starts_with( $slug, 'pa_' ) ) {
			$out[] = substr( $slug, 3 );
		} elseif ( '' !== $slug ) {
			$out[] = 'pa_' . $slug;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param int                   $product_id     商品。
	 * @param int                   $group_id       组。
	 * @param string                $attribute_name 正在查询的属性。
	 * @param array<string, string> $actions        组动作。
	 */
	private static function resolve_disable_image_fallback( int $product_id, int $group_id, string $attribute_name, array $actions ): string {
		$disable_attrs = array();
		foreach ( $actions as $attr => $action ) {
			if ( self::ACTION_DISABLE_IMAGE === $action ) {
				$disable_attrs[] = (string) $attr;
			}
		}
		if ( array() === $disable_attrs ) {
			return self::ACTION_NONE;
		}

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return self::ACTION_NONE;
		}

		$product_attrs = array_map( 'strtolower', array_keys( $product->get_variation_attributes() ) );
		$has_configured = false;
		foreach ( $disable_attrs as $cfg ) {
			foreach ( self::attribute_key_candidates( $cfg ) as $cand ) {
				if ( in_array( strtolower( $cand ), $product_attrs, true ) ) {
					$has_configured = true;
					break 2;
				}
			}
		}

		// 配置的 Style 等已存在于商品上时，不回退到 Color。
		if ( $has_configured ) {
			return self::ACTION_NONE;
		}

		// 仅当查询的是颜色类属性时回退生效（勿调用 is_color_attribute，避免递归进本 filter）。
		if ( ! class_exists( 'Tailwindscore_Variation_Settings' ) ) {
			return self::ACTION_NONE;
		}

		$color_attrs = array_map( 'strtolower', Tailwindscore_Variation_Settings::get_color_attributes() );
		foreach ( self::attribute_key_candidates( $attribute_name ) as $cand ) {
			if ( in_array( strtolower( $cand ), $color_attrs, true ) ) {
				return self::ACTION_DISABLE_IMAGE;
			}
		}

		return self::ACTION_NONE;
	}

	/**
	 * @param int                   $group_id          组 ID。
	 * @param int[]                 $product_ids       商品 ID。
	 * @param string                $display_attribute 组缩略图显示属性。
	 * @param array<string, string> $attr_actions      属性动作（可独立于显示属性）。
	 * @param string                $display_mode      thumbnail | button。
	 * @return string[] 被移出旧组的商品短说明（供 admin notice）。
	 */
	public static function save_group_meta( int $group_id, array $product_ids, string $display_attribute, array $attr_actions, string $display_mode = self::MODE_THUMBNAIL ): array {
		$group_id = absint( $group_id );
		if ( $group_id < 1 || ! self::is_valid_group( $group_id ) ) {
			return array();
		}

		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		$display_attribute = sanitize_title( $display_attribute );
		$display_mode      = sanitize_key( $display_mode );
		if ( ! in_array( $display_mode, self::allowed_display_modes(), true ) ) {
			$display_mode = self::MODE_THUMBNAIL;
		}

		$allowed       = self::allowed_actions();
		$clean_actions = array();
		foreach ( $attr_actions as $attr => $action ) {
			$attr   = sanitize_title( (string) $attr );
			$action = (string) $action;
			if ( '' === $attr || ! in_array( $action, $allowed, true ) || self::ACTION_NONE === $action ) {
				continue;
			}
			// Display 属性（多为非变体 Style）不参与变体动作。
			if ( '' !== $display_attribute && $attr === $display_attribute ) {
				continue;
			}
			$clean_actions[ $attr ] = $action;
		}

		// 列表 / CSV 摘要：显示属性 + 有动作的属性。
		$attributes = array();
		if ( '' !== $display_attribute ) {
			$attributes[] = $display_attribute;
		}
		foreach ( array_keys( $clean_actions ) as $attr ) {
			if ( ! in_array( $attr, $attributes, true ) ) {
				$attributes[] = $attr;
			}
		}

		$notices = self::enforce_one_group_per_product( $group_id, $product_ids );

		$previous = self::get_product_ids( $group_id );
		foreach ( $previous as $old_id ) {
			if ( ! in_array( $old_id, $product_ids, true ) ) {
				delete_post_meta( $old_id, self::META_PRODUCT_GROUP );
			}
		}

		update_post_meta( $group_id, self::META_PRODUCT_IDS, $product_ids );
		update_post_meta( $group_id, self::META_ATTRIBUTES, $attributes );
		update_post_meta( $group_id, self::META_ATTR_ACTIONS, $clean_actions );
		if ( '' === $display_attribute ) {
			delete_post_meta( $group_id, self::META_DISPLAY_ATTRIBUTE );
		} else {
			update_post_meta( $group_id, self::META_DISPLAY_ATTRIBUTE, $display_attribute );
		}
		// 默认 thumbnail 不占 meta；仅 button 持久化。
		if ( self::MODE_BUTTON === $display_mode ) {
			update_post_meta( $group_id, self::META_DISPLAY_MODE, self::MODE_BUTTON );
		} else {
			delete_post_meta( $group_id, self::META_DISPLAY_MODE );
		}

		foreach ( $product_ids as $pid ) {
			update_post_meta( $pid, self::META_PRODUCT_GROUP, $group_id );
		}

		return $notices;
	}

	/**
	 * @param int   $group_id    当前组。
	 * @param int[] $product_ids 要写入的商品。
	 * @return string[]
	 */
	private static function enforce_one_group_per_product( int $group_id, array $product_ids ): array {
		$notices = array();

		foreach ( $product_ids as $product_id ) {
			$other = self::find_other_group_containing( $product_id, $group_id );
			if ( $other < 1 ) {
				continue;
			}

			$ids = self::get_product_ids( $other );
			$ids = array_values( array_filter( $ids, static fn( int $id ): bool => $id !== $product_id ) );
			update_post_meta( $other, self::META_PRODUCT_IDS, $ids );

			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
			$name    = $product instanceof WC_Product ? $product->get_formatted_name() : (string) $product_id;
			$other_t = get_the_title( $other );
			$notices[] = sprintf(
				/* translators: 1: product name, 2: previous group title */
				__( '「%1$s」已从组「%2$s」移出。', 'tailwindscore' ),
				$name,
				$other_t ? $other_t : (string) $other
			);
		}

		return $notices;
	}

	/**
	 * @param int $product_id     商品。
	 * @param int $exclude_group  排除的组。
	 */
	private static function find_other_group_containing( int $product_id, int $exclude_group ): int {
		$cached = (int) get_post_meta( $product_id, self::META_PRODUCT_GROUP, true );
		if ( $cached > 0 && $cached !== $exclude_group && self::is_valid_group( $cached ) ) {
			$ids = self::get_product_ids( $cached );
			if ( in_array( $product_id, $ids, true ) ) {
				return $cached;
			}
		}

		return self::scan_group_containing_product( $product_id, $exclude_group );
	}

	/**
	 * 扫描组列表（索引缺失时的兜底）。
	 *
	 * @param int $product_id    商品。
	 * @param int $exclude_group 排除。
	 */
	private static function scan_group_containing_product( int $product_id, int $exclude_group ): int {
		$query = new WP_Query(
			array(
				'post_type'              => Tailwindscore_Product_Group_Cpt::POST_TYPE,
				'post_status'            => array( 'publish', 'private', 'draft' ),
				'posts_per_page'         => 100,
				'fields'                 => 'ids',
				'post__not_in'           => $exclude_group > 0 ? array( $exclude_group ) : array(),
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $query->posts as $gid ) {
			$gid = (int) $gid;
			if ( in_array( $product_id, self::get_product_ids( $gid ), true ) ) {
				return $gid;
			}
		}

		return 0;
	}

	/**
	 * @param int $group_id 组 ID。
	 */
	public static function is_valid_group( int $group_id ): bool {
		$post = get_post( $group_id );
		if ( ! $post instanceof WP_Post || Tailwindscore_Product_Group_Cpt::POST_TYPE !== $post->post_type ) {
			return false;
		}

		return in_array( $post->post_status, array( 'publish', 'private', 'draft' ), true );
	}

	/**
	 * 按 Iconic 来源查找已迁移组。
	 *
	 * @param int $iconic_post_id Iconic CPT ID。
	 */
	public static function find_group_by_iconic_id( int $iconic_post_id ): int {
		$iconic_post_id = absint( $iconic_post_id );
		if ( $iconic_post_id < 1 ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'              => Tailwindscore_Product_Group_Cpt::POST_TYPE,
				'post_status'            => array( 'publish', 'private', 'draft' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_MIGRATED_FROM,
						'value' => $iconic_post_id,
					),
				),
			)
		);

		return isset( $query->posts[0] ) ? (int) $query->posts[0] : 0;
	}
}
