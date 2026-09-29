<?php
declare(strict_types=1);

/**
 * Iconic Linked Variations → 产品组迁移。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 挂在 Product Groups 列表入口（不进侧栏菜单）+ 可选 WP-CLI。
 */
final class Tailwindscore_Product_Group_Migrate {

	public const PAGE_SLUG = 'ts-product-groups-migrate';

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_page' ) );
		add_action( 'admin_menu', array( self::class, 'hide_menu_item' ), 999 );
		add_action( 'admin_post_ts_pg_migrate_iconic', array( self::class, 'handle_admin_post' ) );
		add_filter( 'views_edit-' . Tailwindscore_Product_Group_Cpt::POST_TYPE, array( self::class, 'list_views_link' ) );

		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command(
				'ts-pg-migrate-iconic',
				static function ( array $args, array $assoc_args ): void {
					Tailwindscore_Product_Group_Migrate::cli_migrate( $args, $assoc_args );
				}
			);
		}
	}

	/**
	 * 注册页面（父级与 CPT 同挂 Products，便于权限与高亮）。
	 *
	 * @return void
	 */
	public static function register_page(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Migrate from Iconic', 'tailwindscore' ),
			__( 'Migrate from Iconic', 'tailwindscore' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * 一次性工具：不出现在侧栏，仅从 Product Groups 列表进入。
	 *
	 * @return void
	 */
	public static function hide_menu_item(): void {
		remove_submenu_page( 'edit.php?post_type=product', self::PAGE_SLUG );
	}

	/**
	 * 列表页 views 行增加迁移入口。
	 *
	 * @param array<string, string> $views 视图链接。
	 * @return array<string, string>
	 */
	public static function list_views_link( array $views ): array {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $views;
		}

		$views['ts_pg_migrate'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( self::page_url() ),
			esc_html__( 'Migrate from Iconic', 'tailwindscore' )
		);

		return $views;
	}

	/**
	 * 迁移页 URL。
	 */
	public static function page_url(): string {
		return add_query_arg(
			array(
				'post_type' => 'product',
				'page'      => self::PAGE_SLUG,
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$result = get_transient( 'ts_pg_migrate_result_' . get_current_user_id() );
		if ( is_array( $result ) ) {
			delete_transient( 'ts_pg_migrate_result_' . get_current_user_id() );
		}

		$list_url = admin_url( 'edit.php?post_type=' . Tailwindscore_Product_Group_Cpt::POST_TYPE );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Migrate from Iconic', 'tailwindscore' ); ?></h1>
			<p>
				<a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Product Groups', 'tailwindscore' ); ?></a>
			</p>
			<p><?php esc_html_e( 'Import wp_iconic_woo_linked_variations into Product Groups. Does not drop the Iconic table.', 'tailwindscore' ); ?></p>
			<?php if ( is_array( $result ) ) : ?>
				<div class="notice notice-<?php echo ! empty( $result['ok'] ) ? 'success' : 'warning'; ?> is-dismissible">
					<pre style="white-space:pre-wrap;"><?php echo esc_html( (string) ( $result['report'] ?? '' ) ); ?></pre>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ts_pg_migrate_iconic" />
				<?php wp_nonce_field( 'ts_pg_migrate_iconic' ); ?>
				<p>
					<label>
						<input type="checkbox" name="dry_run" value="1" checked />
						<?php esc_html_e( 'Dry run (report only)', 'tailwindscore' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="force" value="1" />
						<?php esc_html_e( 'Force (overwrite already migrated groups)', 'tailwindscore' ); ?>
					</label>
				</p>
				<?php submit_button( __( 'Run migration', 'tailwindscore' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	public static function handle_admin_post(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'tailwindscore' ) );
		}
		check_admin_referer( 'ts_pg_migrate_iconic' );

		$dry_run = isset( $_POST['dry_run'] );
		$force   = isset( $_POST['force'] );
		$report  = self::run( $dry_run, $force );

		set_transient(
			'ts_pg_migrate_result_' . get_current_user_id(),
			array(
				'ok'     => true,
				'report' => $report,
			),
			120
		);

		wp_safe_redirect( self::page_url() );
		exit;
	}

	/**
	 * WP-CLI 入口。
	 *
	 * @param array<int, string>   $args       位置参数。
	 * @param array<string, mixed> $assoc_args 选项。
	 * @return void
	 */
	public static function cli_migrate( array $args, array $assoc_args ): void {
		$dry_run = isset( $assoc_args['dry-run'] );
		$force   = isset( $assoc_args['force'] );
		$report  = self::run( $dry_run, $force );
		if ( class_exists( '\WP_CLI' ) ) {
			\WP_CLI::log( $report );
			\WP_CLI::success( 'Done.' );
		}
	}

	/**
	 * @param bool $dry_run 试跑。
	 * @param bool $force   强制。
	 */
	public static function run( bool $dry_run, bool $force ): string {
		global $wpdb;

		$table = $wpdb->prefix . 'iconic_woo_linked_variations';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $table !== $exists ) {
			return 'Iconic table not found: ' . $table;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" );
		if ( ! is_array( $rows ) || array() === $rows ) {
			return 'No Iconic linked variation rows.';
		}

		$lines         = array();
		$created       = 0;
		$skipped       = 0;
		$updated       = 0;
		$claimed_ids   = array();

		foreach ( $rows as $row ) {
			$iconic_post_id = isset( $row->post_id ) ? (int) $row->post_id : 0;
			$product_ids    = array_map( 'absint', (array) maybe_unserialize( $row->product_ids ?? '' ) );
			$product_ids    = array_values( array_filter( $product_ids ) );
			$attributes     = array_map( 'sanitize_title', (array) maybe_unserialize( $row->attributes ?? '' ) );
			$attributes     = array_values( array_filter( $attributes ) );
			$show_image     = ! empty( $row->show_image );

			$title = '';
			if ( $iconic_post_id > 0 ) {
				$title = (string) get_the_title( $iconic_post_id );
			}
			if ( '' === trim( $title ) ) {
				$title = sprintf( 'Linked group #%d', $iconic_post_id > 0 ? $iconic_post_id : (int) ( $row->id ?? 0 ) );
			}

			// 一组一品：跳过已被前面组占用的商品。
			$unique_ids = array();
			$conflicts  = array();
			foreach ( $product_ids as $pid ) {
				if ( isset( $claimed_ids[ $pid ] ) ) {
					$conflicts[] = $pid;
					continue;
				}
				$product = wc_get_product( $pid );
				if ( ! $product instanceof WC_Product ) {
					$conflicts[] = $pid;
					continue;
				}
				$unique_ids[]           = $pid;
				$claimed_ids[ $pid ]    = true;
			}

			$attr_actions = array();
			foreach ( $attributes as $i => $attr ) {
				if ( 0 === $i && $show_image ) {
					$attr_actions[ $attr ] = Tailwindscore_Product_Group_Repository::ACTION_DISABLE_IMAGE;
				}
			}
			$display_attribute = $attributes[0] ?? '';

			$existing = $iconic_post_id > 0
				? Tailwindscore_Product_Group_Repository::find_group_by_iconic_id( $iconic_post_id )
				: 0;

			$line = sprintf(
				'#%s "%s" products=%d attrs=%d conflicts=%d existing=%d',
				(string) ( $row->id ?? '?' ),
				$title,
				count( $unique_ids ),
				count( $attributes ),
				count( $conflicts ),
				$existing
			);

			if ( count( $unique_ids ) < 2 ) {
				$lines[] = $line . ' → skip (<2 products)';
				++$skipped;
				continue;
			}

			if ( $existing > 0 && ! $force ) {
				$lines[] = $line . ' → skip (already migrated)';
				++$skipped;
				continue;
			}

			if ( $dry_run ) {
				$lines[] = $line . ' → would ' . ( $existing > 0 ? 'update' : 'create' );
				if ( $existing > 0 ) {
					++$updated;
				} else {
					++$created;
				}
				continue;
			}

			if ( $existing > 0 ) {
				$group_id = $existing;
				wp_update_post(
					array(
						'ID'         => $group_id,
						'post_title' => $title,
						'post_status'=> 'publish',
					)
				);
				++$updated;
			} else {
				$group_id = wp_insert_post(
					array(
						'post_type'   => Tailwindscore_Product_Group_Cpt::POST_TYPE,
						'post_title'  => $title,
						'post_status' => 'publish',
					),
					true
				);
				if ( is_wp_error( $group_id ) || ! is_int( $group_id ) || $group_id < 1 ) {
					$lines[] = $line . ' → error creating post';
					++$skipped;
					continue;
				}
				++$created;
			}

			Tailwindscore_Product_Group_Repository::save_group_meta(
				$group_id,
				$unique_ids,
				$display_attribute,
				$attr_actions,
				Tailwindscore_Product_Group_Repository::MODE_THUMBNAIL
			);
			if ( $iconic_post_id > 0 ) {
				update_post_meta( $group_id, Tailwindscore_Product_Group_Repository::META_MIGRATED_FROM, $iconic_post_id );
			}

			$lines[] = $line . ' → ' . ( $existing > 0 ? 'updated' : 'created' ) . ' group #' . $group_id;
		}

		$summary = sprintf(
			"dry_run=%s force=%s created=%d updated=%d skipped=%d\n",
			$dry_run ? 'yes' : 'no',
			$force ? 'yes' : 'no',
			$created,
			$updated,
			$skipped
		);

		return $summary . implode( "\n", $lines );
	}
}
