<?php
declare(strict_types=1);

/**
 * 将主题旧 _variation_images 一次性迁入 WC 官方变体相册。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 旧 meta → set_gallery_image_ids（不覆盖已有官方数据）。
 */
class Tailwindscore_Gallery_Variation_Migrate {

	public const OPTION_DONE = 'tailwindscore_gallery_migrated_to_wc_variation_gallery';

	public const ACTION_HOOK = 'tailwindscore_gallery_migrate_variation_batch';

	public const BATCH_SIZE = 50;

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'maybe_schedule' ), 20 );
		add_action( self::ACTION_HOOK, array( self::class, 'process_batch' ), 10, 1 );
	}

	/**
	 * 尚未完成且 WC 支持 set_gallery_image_ids 时排队首批。
	 *
	 * @return void
	 */
	public static function maybe_schedule(): void {
		if ( get_option( self::OPTION_DONE ) ) {
			return;
		}

		if ( ! class_exists( 'WC_Product_Variation', false ) ) {
			return;
		}

		if ( ! method_exists( 'WC_Product_Variation', 'set_gallery_image_ids' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ACTION_HOOK, null, 'tailwindscore-gallery' ) ) {
			return;
		}

		self::enqueue_batch( 0 );
	}

	/**
	 * @param int $offset 查询偏移。
	 * @return void
	 */
	public static function enqueue_batch( int $offset ): void {
		$offset = max( 0, $offset );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ACTION_HOOK, array( $offset ), 'tailwindscore-gallery' ) ) {
				return;
			}
			as_enqueue_async_action( self::ACTION_HOOK, array( $offset ), 'tailwindscore-gallery' );
			return;
		}

		// 无 Action Scheduler：同步分批；超时后下次 admin_init 从 offset 0 重扫（已迁变体会跳过）。
		self::process_batch( $offset );
	}

	/**
	 * @param int|string $offset 查询偏移。
	 * @return void
	 */
	public static function process_batch( $offset = 0 ): void {
		$offset = absint( $offset );

		if ( get_option( self::OPTION_DONE ) ) {
			return;
		}

		if ( ! method_exists( 'WC_Product_Variation', 'set_gallery_image_ids' ) ) {
			update_option( self::OPTION_DONE, '1', false );
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = '_variation_images'
				AND p.post_type = 'product_variation'
				AND p.post_status != 'trash'
				ORDER BY pm.post_id ASC
				LIMIT %d OFFSET %d",
				self::BATCH_SIZE,
				$offset
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			update_option( self::OPTION_DONE, '1', false );
			return;
		}

		foreach ( $rows as $row ) {
			$variation_id = isset( $row['post_id'] ) ? absint( $row['post_id'] ) : 0;
			if ( $variation_id < 1 ) {
				continue;
			}

			$product = wc_get_product( $variation_id );
			if ( ! $product instanceof WC_Product_Variation ) {
				continue;
			}

			if ( method_exists( $product, 'get_gallery_image_ids' ) ) {
				$existing = $product->get_gallery_image_ids();
				if ( is_array( $existing ) && ! empty( $existing ) ) {
					continue;
				}
			}

			$legacy = maybe_unserialize( $row['meta_value'] ?? '' );
			if ( ! is_array( $legacy ) ) {
				$legacy = get_post_meta( $variation_id, '_variation_images', true );
			}
			if ( ! is_array( $legacy ) || empty( $legacy ) ) {
				continue;
			}

			$ids = array_values( array_unique( array_filter( array_map( 'absint', $legacy ) ) ) );
			if ( empty( $ids ) ) {
				continue;
			}

			$product->set_gallery_image_ids( $ids );
			$product->save();
		}

		if ( count( $rows ) < self::BATCH_SIZE ) {
			update_option( self::OPTION_DONE, '1', false );
			return;
		}

		self::enqueue_batch( $offset + self::BATCH_SIZE );
	}
}
