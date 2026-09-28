<?php
declare(strict_types=1);

/**
 * 产品组 CSV 导出 / 导入（缺全局属性则跳过并提示；不创建属性 / term / 商品属性）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 列表入口 + 隐藏后台页。
 */
final class Tailwindscore_Product_Group_Csv {

	public const PAGE_SLUG = 'ts-product-groups-csv';

	public const COLUMNS = array(
		'group_id',
		'title',
		'skus',
		'attributes',
		'attr_actions',
	);

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_page' ) );
		add_action( 'admin_menu', array( self::class, 'hide_menu_item' ), 999 );
		add_action( 'admin_post_ts_pg_csv_export', array( self::class, 'handle_export' ) );
		add_action( 'admin_post_ts_pg_csv_import', array( self::class, 'handle_import' ) );
		add_filter( 'views_edit-' . Tailwindscore_Product_Group_Cpt::POST_TYPE, array( self::class, 'list_views_link' ) );
	}

	/**
	 * @return void
	 */
	public static function register_page(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Product Groups CSV', 'tailwindscore' ),
			__( 'Product Groups CSV', 'tailwindscore' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * @return void
	 */
	public static function hide_menu_item(): void {
		remove_submenu_page( 'edit.php?post_type=product', self::PAGE_SLUG );
	}

	/**
	 * @param array<string, string> $views 视图。
	 * @return array<string, string>
	 */
	public static function list_views_link( array $views ): array {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $views;
		}

		$views['ts_pg_csv'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( self::page_url() ),
			esc_html__( 'Import / Export CSV', 'tailwindscore' )
		);

		return $views;
	}

	/**
	 * 工具页 URL。
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

		$result = get_transient( 'ts_pg_csv_result_' . get_current_user_id() );
		if ( is_array( $result ) ) {
			delete_transient( 'ts_pg_csv_result_' . get_current_user_id() );
		}

		$list_url = admin_url( 'edit.php?post_type=' . Tailwindscore_Product_Group_Cpt::POST_TYPE );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Product Groups CSV', 'tailwindscore' ); ?></h1>
			<p>
				<a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Product Groups', 'tailwindscore' ); ?></a>
			</p>
			<p>
				<?php esc_html_e( 'Export or import groups by SKU. Unknown global attributes are reported and the row is skipped (attributes are not created here). Attribute terms / product values belong in feed-export.', 'tailwindscore' ); ?>
			</p>
			<p><code>group_id,title,skus,attributes,attr_actions</code></p>
			<p class="description">
				<?php esc_html_e( 'skus / attributes: pipe-separated. attr_actions: pa_style:disable_image|pa_color:none (actions: none, disable_image, hide_row).', 'tailwindscore' ); ?>
			</p>

			<?php if ( is_array( $result ) ) : ?>
				<div class="notice notice-<?php echo ! empty( $result['ok'] ) ? 'success' : 'warning'; ?> is-dismissible">
					<pre style="white-space:pre-wrap;"><?php echo esc_html( (string) ( $result['report'] ?? '' ) ); ?></pre>
				</div>
			<?php endif; ?>

			<hr />
			<h2><?php esc_html_e( 'Export', 'tailwindscore' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ts_pg_csv_export" />
				<?php wp_nonce_field( 'ts_pg_csv_export' ); ?>
				<?php submit_button( __( 'Download CSV', 'tailwindscore' ), 'secondary', 'submit', false ); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Import', 'tailwindscore' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="ts_pg_csv_import" />
				<?php wp_nonce_field( 'ts_pg_csv_import' ); ?>
				<p>
					<input type="file" name="csv_file" accept=".csv,text/csv" required />
				</p>
				<p>
					<label>
						<input type="checkbox" name="dry_run" value="1" checked />
						<?php esc_html_e( 'Dry run (report only)', 'tailwindscore' ); ?>
					</label>
				</p>
				<?php submit_button( __( 'Run import', 'tailwindscore' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	public static function handle_export(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'tailwindscore' ) );
		}
		check_admin_referer( 'ts_pg_csv_export' );

		$csv = self::build_export_csv();
		$filename = 'product-groups-' . gmdate( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		// Excel UTF-8 BOM.
		echo "\xEF\xBB\xBF";
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download.
		exit;
	}

	/**
	 * @return void
	 */
	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Forbidden', 'tailwindscore' ) );
		}
		check_admin_referer( 'ts_pg_csv_import' );

		$dry_run = isset( $_POST['dry_run'] );
		$file    = isset( $_FILES['csv_file'] ) && is_array( $_FILES['csv_file'] ) ? $_FILES['csv_file'] : null;

		if ( ! $file || ! isset( $file['tmp_name'], $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			self::store_result( false, 'Upload failed.' );
			wp_safe_redirect( self::page_url() );
			exit;
		}

		$tmp = (string) $file['tmp_name'];
		$report = self::import_file( $tmp, $dry_run );
		self::store_result( true, $report );
		wp_safe_redirect( self::page_url() );
		exit;
	}

	/**
	 * @param bool   $ok     是否成功。
	 * @param string $report 报告。
	 */
	private static function store_result( bool $ok, string $report ): void {
		set_transient(
			'ts_pg_csv_result_' . get_current_user_id(),
			array(
				'ok'     => $ok,
				'report' => $report,
			),
			120
		);
	}

	/**
	 * 导出全部产品组为 CSV 文本。
	 */
	public static function build_export_csv(): string {
		$ids = get_posts(
			array(
				'post_type'              => Tailwindscore_Product_Group_Cpt::POST_TYPE,
				'post_status'            => array( 'publish', 'private', 'draft' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		$fh = fopen( 'php://temp', 'r+' );
		if ( false === $fh ) {
			return '';
		}

		fputcsv( $fh, self::COLUMNS );

		foreach ( $ids as $group_id ) {
			$group_id = (int) $group_id;
			$post     = get_post( $group_id );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$product_ids = Tailwindscore_Product_Group_Repository::get_product_ids( $group_id );
			$skus        = array();
			foreach ( $product_ids as $pid ) {
				$product = wc_get_product( $pid );
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
				$sku = trim( (string) $product->get_sku() );
				$skus[] = '' !== $sku ? $sku : '#' . $pid;
			}

			$attributes = Tailwindscore_Product_Group_Repository::get_attributes( $group_id );
			$actions    = Tailwindscore_Product_Group_Repository::get_attr_actions( $group_id );
			$action_bits = array();
			foreach ( $attributes as $attr ) {
				$action        = $actions[ $attr ] ?? Tailwindscore_Product_Group_Repository::ACTION_NONE;
				$action_bits[] = $attr . ':' . $action;
			}

			fputcsv(
				$fh,
				array(
					(string) $group_id,
					$post->post_title,
					implode( '|', $skus ),
					implode( '|', $attributes ),
					implode( '|', $action_bits ),
				)
			);
		}

		rewind( $fh );
		$csv = stream_get_contents( $fh );
		fclose( $fh );

		return is_string( $csv ) ? $csv : '';
	}

	/**
	 * @param string $path    上传临时路径。
	 * @param bool   $dry_run 试跑。
	 */
	public static function import_file( string $path, bool $dry_run ): string {
		$handle = fopen( $path, 'r' );
		if ( false === $handle ) {
			return 'Cannot open CSV.';
		}

		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) || array() === $header ) {
			fclose( $handle );
			return 'Empty CSV.';
		}

		$header[0] = self::strip_bom( (string) $header[0] );
		$map       = self::map_header( $header );
		if ( ! isset( $map['title'], $map['skus'] ) ) {
			fclose( $handle );
			return 'CSV must include title and skus columns.';
		}

		$lines   = array();
		$created = 0;
		$updated = 0;
		$skipped = 0;
		$row_num = 1;

		while ( true ) {
			$row = fgetcsv( $handle );
			if ( false === $row ) {
				break;
			}
			++$row_num;
			if ( self::row_is_empty( $row ) ) {
				continue;
			}

			$parsed = self::parse_row( $row, $map, $row_num );
			if ( isset( $parsed['error'] ) ) {
				$lines[] = $parsed['error'];
				++$skipped;
				continue;
			}

			/** @var array{group_id:int,title:string,product_ids:int[],attributes:string[],attr_actions:array<string,string>,missing_attrs:string[],missing_skus?:string[]} $parsed */
			$missing = isset( $parsed['missing_skus'] ) && is_array( $parsed['missing_skus'] )
				? $parsed['missing_skus']
				: array();
			$missing_attrs = $parsed['missing_attrs'];
			$line          = sprintf(
				'row %d title="%s" products=%d missing_skus=%d attrs=%d missing_attrs=[%s] group_id=%s',
				$row_num,
				$parsed['title'],
				count( $parsed['product_ids'] ),
				count( $missing ),
				count( $parsed['attributes'] ),
				implode( ',', $missing_attrs ),
				$parsed['group_id'] > 0 ? (string) $parsed['group_id'] : 'new'
			);
			if ( array() !== $missing ) {
				$line .= ' missing_sku_list=[' . implode( ',', $missing ) . ']';
			}

			if ( array() !== $missing_attrs ) {
				$lines[] = $line . ' → skip (unknown global attributes)';
				++$skipped;
				continue;
			}

			if ( count( $parsed['product_ids'] ) < 2 ) {
				$lines[] = $line . ' → skip (<2 resolvable products)';
				++$skipped;
				continue;
			}

			if ( $dry_run ) {
				$action = $parsed['group_id'] > 0 && self::is_group( $parsed['group_id'] ) ? 'update' : 'create';
				$lines[] = $line . ' → would ' . $action;
				if ( 'update' === $action ) {
					++$updated;
				} else {
					++$created;
				}
				continue;
			}

			$group_id = $parsed['group_id'];
			if ( $group_id > 0 && self::is_group( $group_id ) ) {
				wp_update_post(
					array(
						'ID'          => $group_id,
						'post_title'  => $parsed['title'],
						'post_status' => 'publish',
					)
				);
				++$updated;
				$verb = 'updated';
			} else {
				$group_id = wp_insert_post(
					array(
						'post_type'   => Tailwindscore_Product_Group_Cpt::POST_TYPE,
						'post_title'  => $parsed['title'],
						'post_status' => 'publish',
					),
					true
				);
				if ( is_wp_error( $group_id ) || ! is_int( $group_id ) || $group_id < 1 ) {
					$lines[] = $line . ' → error creating group';
					++$skipped;
					continue;
				}
				++$created;
				$verb = 'created';
			}

			Tailwindscore_Product_Group_Repository::save_group_meta(
				$group_id,
				$parsed['product_ids'],
				$parsed['attributes'],
				$parsed['attr_actions']
			);

			$lines[] = $line . ' → ' . $verb . ' #' . $group_id;
		}

		fclose( $handle );

		$summary = sprintf(
			"dry_run=%s created=%d updated=%d skipped=%d\n",
			$dry_run ? 'yes' : 'no',
			$created,
			$updated,
			$skipped
		);

		return $summary . implode( "\n", $lines );
	}

	/**
	 * @param string[]              $row 行。
	 * @param array<string, int>    $map 列映射。
	 * @param int                   $row_num 行号。
	 * @return array<string, mixed>
	 */
	private static function parse_row( array $row, array $map, int $row_num ): array {
		$title = isset( $map['title'] ) ? trim( (string) ( $row[ $map['title'] ] ?? '' ) ) : '';
		if ( '' === $title ) {
			return array( 'error' => sprintf( 'row %d → skip (empty title)', $row_num ) );
		}

		$group_id = 0;
		if ( isset( $map['group_id'] ) ) {
			$group_id = absint( $row[ $map['group_id'] ] ?? 0 );
		}

		$skus_raw = isset( $map['skus'] ) ? (string) ( $row[ $map['skus'] ] ?? '' ) : '';
		$sku_bits = self::split_pipe( $skus_raw );
		$product_ids = array();
		$missing = array();
		foreach ( $sku_bits as $sku ) {
			if ( str_starts_with( $sku, '#' ) ) {
				$pid = absint( substr( $sku, 1 ) );
				$product = $pid > 0 ? wc_get_product( $pid ) : null;
				if ( $product instanceof WC_Product ) {
					$parent = (int) $product->get_parent_id();
					$product_ids[] = $parent > 0 ? $parent : $pid;
				} else {
					$missing[] = $sku;
				}
				continue;
			}

			$pid = wc_get_product_id_by_sku( $sku );
			if ( $pid < 1 ) {
				$missing[] = $sku;
				continue;
			}
			$product = wc_get_product( $pid );
			if ( ! $product instanceof WC_Product ) {
				$missing[] = $sku;
				continue;
			}
			$parent = (int) $product->get_parent_id();
			$product_ids[] = $parent > 0 ? $parent : $pid;
		}
		$product_ids = array_values( array_unique( array_filter( $product_ids ) ) );

		$attrs_raw = isset( $map['attributes'] ) ? (string) ( $row[ $map['attributes'] ] ?? '' ) : '';
		$attr_bits = self::split_pipe( $attrs_raw );
		$attributes = array();
		$missing_attrs = array();
		foreach ( $attr_bits as $raw_attr ) {
			$normalized = self::normalize_attribute_key( $raw_attr );
			if ( '' === $normalized ) {
				continue;
			}
			$attributes[] = $normalized;
			if ( ! taxonomy_exists( $normalized ) ) {
				$missing_attrs[] = $normalized;
			}
		}
		$attributes    = array_values( array_unique( $attributes ) );
		$missing_attrs = array_values( array_unique( $missing_attrs ) );

		$actions_raw = isset( $map['attr_actions'] ) ? (string) ( $row[ $map['attr_actions'] ] ?? '' ) : '';
		$attr_actions = self::parse_attr_actions( $actions_raw, $attributes );

		$out = array(
			'group_id'      => $group_id,
			'title'         => $title,
			'product_ids'   => $product_ids,
			'attributes'    => $attributes,
			'attr_actions'  => $attr_actions,
			'missing_attrs' => $missing_attrs,
		);

		if ( array() !== $missing ) {
			$out['missing_skus'] = $missing;
		}

		return $out;
	}

	/**
	 * @param string   $raw        原串。
	 * @param string[] $attributes 属性列表。
	 * @return array<string, string>
	 */
	private static function parse_attr_actions( string $raw, array $attributes ): array {
		$allowed = Tailwindscore_Product_Group_Repository::allowed_actions();
		$parsed  = array();
		foreach ( self::split_pipe( $raw ) as $bit ) {
			if ( ! str_contains( $bit, ':' ) ) {
				continue;
			}
			list( $attr, $action ) = array_map( 'trim', explode( ':', $bit, 2 ) );
			$attr   = self::normalize_attribute_key( $attr );
			$action = sanitize_key( $action );
			if ( '' === $attr || ! in_array( $action, $allowed, true ) ) {
				continue;
			}
			$parsed[ $attr ] = $action;
		}

		$out = array();
		foreach ( $attributes as $attr ) {
			$out[ $attr ] = $parsed[ $attr ] ?? Tailwindscore_Product_Group_Repository::ACTION_NONE;
		}
		return $out;
	}

	/**
	 * @param string $raw 原始属性键。
	 */
	private static function normalize_attribute_key( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		$slug = wc_sanitize_taxonomy_name( $raw );
		if ( ! str_starts_with( $slug, 'pa_' ) ) {
			$slug = wc_attribute_taxonomy_name( $slug );
		}
		return $slug;
	}

	/**
	 * @param string[] $header 表头。
	 * @return array<string, int>
	 */
	private static function map_header( array $header ): array {
		$map = array();
		foreach ( $header as $i => $col ) {
			$key = strtolower( trim( (string) $col ) );
			$key = str_replace( array( ' ', '-' ), '_', $key );
			if ( in_array( $key, self::COLUMNS, true ) ) {
				$map[ $key ] = (int) $i;
			}
		}
		return $map;
	}

	/**
	 * @param string $value 原始。
	 * @return string[]
	 */
	private static function split_pipe( string $value ): array {
		$value = trim( $value );
		if ( '' === $value ) {
			return array();
		}
		$parts = array_map( 'trim', explode( '|', $value ) );
		return array_values( array_filter( $parts, static fn( string $p ): bool => '' !== $p ) );
	}

	/**
	 * @param string[] $row 行。
	 */
	private static function row_is_empty( array $row ): bool {
		foreach ( $row as $cell ) {
			if ( '' !== trim( (string) $cell ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $value 可能带 BOM。
	 */
	private static function strip_bom( string $value ): string {
		if ( str_starts_with( $value, "\xEF\xBB\xBF" ) ) {
			return substr( $value, 3 );
		}
		return $value;
	}

	/**
	 * @param int $group_id 组 ID。
	 */
	private static function is_group( int $group_id ): bool {
		return Tailwindscore_Product_Group_Repository::is_valid_group( $group_id );
	}
}
