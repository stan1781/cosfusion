<?php
declare(strict_types=1);

/**
 * 心愿单数据仓储：仅父商品 ID，user meta 读写、去重、上限。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist repository.
 */
class Tailwindscore_Wishlist_Repository {

	public const META_KEY  = '_ts_wishlist';
	public const MAX_ITEMS = 100;

	/**
	 * @param array{productId?:int} $item 条目。
	 */
	public static function item_key( array $item ): string {
		return (string) absint( $item['productId'] ?? 0 );
	}

	/**
	 * 规范化为父商品；变体 ID 会回退到父商品。忽略 variationId。
	 *
	 * @param mixed $raw 原始数据。
	 * @return array{productId:int,addedAt:int}|null
	 */
	public static function normalize_item( $raw ): ?array {
		if ( ! is_array( $raw ) ) {
			return null;
		}

		$product_id = isset( $raw['productId'] ) ? absint( $raw['productId'] ) : 0;
		$added_at   = isset( $raw['addedAt'] ) ? absint( $raw['addedAt'] ) : time();

		// 兼容旧数据：若只写了 variationId，用其解析父商品。
		if ( $product_id < 1 && isset( $raw['variationId'] ) ) {
			$product_id = absint( $raw['variationId'] );
		}

		if ( $product_id < 1 ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return null;
		}

		if ( $product->is_type( 'variation' ) ) {
			$product_id = (int) $product->get_parent_id();
			$product    = wc_get_product( $product_id );
		}

		if ( ! $product || 'publish' !== $product->get_status() ) {
			return null;
		}

		return array(
			'productId' => $product_id,
			'addedAt'   => $added_at > 0 ? $added_at : time(),
		);
	}

	/**
	 * @param mixed $items 原始列表。
	 * @return list<array{productId:int,addedAt:int}>
	 */
	public static function normalize_list( $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$normalized = array();
		$seen       = array();

		foreach ( $items as $raw ) {
			$item = self::normalize_item( $raw );

			if ( null === $item ) {
				continue;
			}

			$key = self::item_key( $item );

			if ( isset( $seen[ $key ] ) || '0' === $key ) {
				continue;
			}

			$seen[ $key ] = true;
			$normalized[] = $item;

			if ( count( $normalized ) >= self::MAX_ITEMS ) {
				break;
			}
		}

		return $normalized;
	}

	/**
	 * @param int $user_id 用户 ID。
	 * @return list<array{productId:int,addedAt:int}>
	 */
	public static function get_items( int $user_id ): array {
		if ( $user_id < 1 ) {
			return array();
		}

		return self::normalize_list( get_user_meta( $user_id, self::META_KEY, true ) );
	}

	/**
	 * @param int                                      $user_id 用户 ID。
	 * @param list<array{productId:int,addedAt:int}> $items   条目。
	 * @return list<array{productId:int,addedAt:int}>
	 */
	public static function set_items( int $user_id, array $items ): array {
		if ( $user_id < 1 ) {
			return array();
		}

		$normalized = self::normalize_list( $items );
		update_user_meta( $user_id, self::META_KEY, $normalized );

		return $normalized;
	}

	/**
	 * @param array{productId:int} $item 目标。
	 * @return array{items:list<array{productId:int,addedAt:int}>,added:bool,error?:string}
	 */
	public static function toggle( int $user_id, array $item ): array {
		$normalized = self::normalize_item(
			array(
				'productId' => $item['productId'] ?? 0,
				'addedAt'   => time(),
			)
		);

		if ( null === $normalized ) {
			return array(
				'items' => self::get_items( $user_id ),
				'added' => false,
				'error' => 'invalid_product',
			);
		}

		$items = self::get_items( $user_id );
		$key   = self::item_key( $normalized );

		foreach ( $items as $index => $existing ) {
			if ( self::item_key( $existing ) === $key ) {
				unset( $items[ $index ] );
				return array(
					'items' => self::set_items( $user_id, array_values( $items ) ),
					'added' => false,
				);
			}
		}

		if ( count( $items ) >= self::MAX_ITEMS ) {
			return array(
				'items' => $items,
				'added' => false,
				'error' => 'limit',
			);
		}

		array_unshift( $items, $normalized );

		return array(
			'items' => self::set_items( $user_id, $items ),
			'added' => true,
		);
	}

	/**
	 * @param array{productId:int} $item 目标。
	 * @return list<array{productId:int,addedAt:int}>
	 */
	public static function remove( int $user_id, array $item ): array {
		$key = self::item_key( $item );

		$items = array_values(
			array_filter(
				self::get_items( $user_id ),
				static function ( array $existing ) use ( $key ): bool {
					return self::item_key( $existing ) !== $key;
				}
			)
		);

		return self::set_items( $user_id, $items );
	}

	/**
	 * @param list<array{productId?:int,addedAt?:int}> $guest_items 访客列表。
	 * @return list<array{productId:int,addedAt:int}>
	 */
	public static function merge( int $user_id, array $guest_items ): array {
		$existing = self::get_items( $user_id );
		$seen     = array();
		$merged   = array();

		foreach ( $existing as $item ) {
			$key          = self::item_key( $item );
			$seen[ $key ] = true;
			$merged[]     = $item;
		}

		foreach ( self::normalize_list( $guest_items ) as $item ) {
			$key = self::item_key( $item );

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$merged[]     = $item;

			if ( count( $merged ) >= self::MAX_ITEMS ) {
				break;
			}
		}

		return self::set_items( $user_id, $merged );
	}

	/**
	 * @param array{productId:int,addedAt:int} $item 条目。
	 * @return array<string, mixed>|null
	 */
	public static function enrich_item( array $item ): ?array {
		$product_id = (int) $item['productId'];
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return null;
		}

		$is_variable    = $product->is_type( 'variable' );
		$can_add_direct = ! $is_variable && $product->is_purchasable() && $product->is_in_stock();

		return array(
			'productId'    => $product_id,
			'addedAt'      => (int) $item['addedAt'],
			'name'         => $product->get_name(),
			'priceHtml'    => $product->get_price_html(),
			'image'        => $product->get_image( 'woocommerce_thumbnail' ),
			'permalink'    => $product->get_permalink(),
			'isVariable'   => $is_variable,
			'canAddDirect' => $can_add_direct,
		);
	}

	/**
	 * @param list<array{productId:int,addedAt:int}> $items 条目。
	 * @return list<array<string, mixed>>
	 */
	public static function enrich_list( array $items ): array {
		$out = array();

		foreach ( $items as $item ) {
			$enriched = self::enrich_item( $item );

			if ( null !== $enriched ) {
				$out[] = $enriched;
			}
		}

		return $out;
	}
}
