<?php
declare(strict_types=1);

/**
 * 产品组后台 UI。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Metabox、列表列、保存。
 */
final class Tailwindscore_Product_Group_Admin {

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', array( self::class, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Tailwindscore_Product_Group_Cpt::POST_TYPE, array( self::class, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . Tailwindscore_Product_Group_Cpt::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . Tailwindscore_Product_Group_Cpt::POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( self::class, 'render_notices' ) );
		add_action( 'wp_ajax_ts_pg_json_search_products', array( self::class, 'ajax_json_search_products' ) );
		add_action( 'pre_get_posts', array( self::class, 'expand_list_search' ) );
	}

	/**
	 * 列表搜索：组标题 OR 组内商品 SKU / 商品名。
	 *
	 * @param WP_Query $query 主查询。
	 */
	public static function expand_list_search( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( Tailwindscore_Product_Group_Cpt::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$term = $query->get( 's' );
		if ( ! is_string( $term ) ) {
			return;
		}
		$term = trim( $term );
		if ( '' === $term ) {
			return;
		}

		$title_ids = get_posts(
			array(
				'post_type'              => Tailwindscore_Product_Group_Cpt::POST_TYPE,
				'post_status'            => 'any',
				's'                      => $term,
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( ! is_array( $title_ids ) ) {
			$title_ids = array();
		}

		$ids = array_values(
			array_unique(
				array_merge(
					array_map( 'absint', $title_ids ),
					self::find_group_ids_by_product_term( $term )
				)
			)
		);

		$query->set( 's', '' );
		$query->set( 'post__in', array() === $ids ? array( 0 ) : $ids );
	}

	/**
	 * 按 SKU / 商品名找到所属产品组（含变体 → 父商品）。
	 *
	 * @param string $term 搜索词。
	 * @return int[]
	 */
	private static function find_group_ids_by_product_term( string $term ): array {
		if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Data_Store', false ) ) {
			return array();
		}

		try {
			$data_store = WC_Data_Store::load( 'product' );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			return array();
		}

		$limit = (int) apply_filters( 'tailwindscore/product_groups/admin_search_product_limit', 100 );
		$limit = max( 1, min( 200, $limit ) );
		$ids   = $data_store->search_products( $term, '', true, false, $limit );
		if ( ! is_array( $ids ) || array() === $ids ) {
			return array();
		}

		$group_ids = array();
		foreach ( $ids as $product_id ) {
			$product_id = absint( $product_id );
			if ( $product_id < 1 ) {
				continue;
			}

			$candidates = array( $product_id );
			$product    = wc_get_product( $product_id );
			if ( $product instanceof WC_Product ) {
				$parent_id = (int) $product->get_parent_id();
				if ( $parent_id > 0 ) {
					$candidates[] = $parent_id;
				}
			}

			foreach ( array_unique( $candidates ) as $candidate_id ) {
				$group_id = Tailwindscore_Product_Group_Repository::get_group_id_for_product( (int) $candidate_id );
				if ( $group_id > 0 ) {
					$group_ids[] = $group_id;
				}
			}
		}

		return array_values( array_unique( $group_ids ) );
	}

	/**
	 * 后台展示标签：优先 SKU，无 SKU 时回退商品名。
	 *
	 * @param WC_Product $product 商品。
	 */
	public static function format_product_label( WC_Product $product ): string {
		$sku  = trim( (string) $product->get_sku() );
		$name = $product->get_name();
		if ( '' !== $sku ) {
			return $sku . ' — ' . $name;
		}
		return $name;
	}

	/**
	 * 搜索结果仅突出 SKU（无 SKU 则名称）。
	 *
	 * @param WC_Product $product 商品。
	 */
	public static function format_product_sku_label( WC_Product $product ): string {
		$sku = trim( (string) $product->get_sku() );
		if ( '' !== $sku ) {
			return $sku;
		}
		return $product->get_name();
	}

	/**
	 * 产品组编辑页用的商品搜索（结果以 SKU 为主）。
	 *
	 * @return void
	 */
	public static function ajax_json_search_products(): void {
		check_ajax_referer( 'search-products', 'security' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( -1 );
		}

		$term = isset( $_GET['term'] ) ? (string) wp_unslash( $_GET['term'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$term = wc_clean( $term );
		if ( '' === $term ) {
			wp_die();
		}

		$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', true, false, $limit );

		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$products[ $id ] = rawurldecode( wp_strip_all_tags( self::format_product_label( $product ) ) );
		}

		wp_send_json( $products );
	}

	/**
	 * @param string $hook 当前后台页面。
	 * @return void
	 */
	public static function enqueue_scripts( string $hook ): void {
		global $post_type;

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( Tailwindscore_Product_Group_Cpt::POST_TYPE !== $post_type ) {
			return;
		}

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_register_style( 'ts-product-groups-admin', false, array( 'woocommerce_admin_styles' ), TAILWINDSCORE_MODULE_PRODUCT_GROUPS_VERSION );
		wp_enqueue_style( 'ts-product-groups-admin' );
		wp_add_inline_style( 'ts-product-groups-admin', self::admin_css() );

		$js = <<<'JS'
		jQuery(function ($) {
			var $tbody = $('#ts-pg-attributes-body');
			if ($tbody.length) {
				$tbody.sortable({ handle: '.ts-pg-attributes__handle', axis: 'y', items: 'tr' });
			}
			function syncActions() {
				var checked = {};
				$tbody.find('input[type=checkbox]:checked').each(function () {
					checked[$(this).val()] = true;
				});
				$('.ts-pg-attr-actions tbody tr').each(function () {
					var attr = $(this).data('attr');
					$(this).toggle(!!checked[attr]);
				});
			}
			$tbody.on('change', 'input[type=checkbox]', syncActions);
			syncActions();
		});
		JS;
		wp_add_inline_script( 'wc-enhanced-select', $js );
	}

	/**
	 * 后台 metabox 样式（覆盖 Woo options_panel 对 label 的 -150px 左浮）。
	 */
	private static function admin_css(): string {
		return <<<'CSS'
.woocommerce_options_panel .ts-pg-attributes-wrap {
	margin-left: 162px;
	max-width: 40rem;
}
.woocommerce_options_panel .ts-pg-attributes-wrap .widefat {
	margin: 0;
}
.woocommerce_options_panel .ts-pg-attributes-wrap .widefat th,
.woocommerce_options_panel .ts-pg-attributes-wrap .widefat td {
	padding: 8px 10px;
	vertical-align: middle;
}
.woocommerce_options_panel .ts-pg-attributes-wrap .ts-pg-attributes__handle {
	cursor: move;
	color: #646970;
	margin-right: 6px;
	vertical-align: middle;
}
.woocommerce_options_panel .ts-pg-attributes-wrap input[type="checkbox"] {
	float: none !important;
	margin: 0 8px 0 0 !important;
	width: auto !important;
	vertical-align: middle;
}
.woocommerce_options_panel .ts-pg-attributes-wrap .ts-pg-attr-name {
	float: none !important;
	width: auto !important;
	margin: 0 !important;
	padding: 0 !important;
	display: inline !important;
	color: #1d2327 !important;
	font-size: 13px !important;
	font-weight: 600 !important;
	line-height: 1.4 !important;
}
.woocommerce_options_panel .ts-pg-attr-actions {
	margin-top: 1rem;
}
.woocommerce_options_panel .ts-pg-attr-actions > label {
	float: left;
	width: 150px;
	padding: 0;
	margin: 0;
}
.woocommerce_options_panel .ts-pg-attr-actions table {
	max-width: 40rem;
	margin-left: 162px;
	clear: none;
}
.woocommerce_options_panel .ts-pg-attr-actions table label {
	float: none !important;
	width: auto !important;
	margin: 0 !important;
}
CSS;
	}

	/**
	 * @return void
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'ts_product_group_settings',
			__( '产品组设置', 'tailwindscore' ),
			array( self::class, 'render_meta_box' ),
			Tailwindscore_Product_Group_Cpt::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * @param WP_Post $post 当前组。
	 * @return void
	 */
	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'ts_product_group_save', 'ts_product_group_nonce' );

		// 兜底：确保 options_panel label 负边距不会吞掉属性名（不依赖 enqueue 是否成功挂上）。
		echo '<style id="ts-pg-admin-css">' . self::admin_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 静态 CSS。

		$product_ids  = Tailwindscore_Product_Group_Repository::get_product_ids( (int) $post->ID );
		$attributes   = Tailwindscore_Product_Group_Repository::get_attributes( (int) $post->ID );
		$attr_actions = Tailwindscore_Product_Group_Repository::get_attr_actions( (int) $post->ID );
		$taxonomies   = self::get_attribute_taxonomies();
		?>
		<div class="panel woocommerce_options_panel">
			<div class="options_group">
				<p class="form-field">
					<label for="ts-pg-product-ids"><?php esc_html_e( 'Products', 'tailwindscore' ); ?></label>
					<select
						class="wc-product-search"
						multiple="multiple"
						style="width: 85%;"
						id="ts-pg-product-ids"
						name="ts_pg_product_ids[]"
						data-placeholder="<?php esc_attr_e( 'Search by SKU or product name&hellip;', 'tailwindscore' ); ?>"
						data-action="ts_pg_json_search_products"
					>
						<?php foreach ( $product_ids as $product_id ) : ?>
							<?php
							$product = wc_get_product( $product_id );
							if ( ! $product instanceof WC_Product ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( (string) $product_id ); ?>" selected="selected">
								<?php echo esc_html( self::format_product_label( $product ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php echo wp_kses_post( wc_help_tip( __( '同一商品只能属于一个产品组。', 'tailwindscore' ) ) ); ?>
				</p>

				<div class="form-field">
					<label><?php esc_html_e( 'Affects attributes', 'tailwindscore' ); ?></label>
					<?php if ( array() === $taxonomies ) : ?>
						<p class="ts-pg-attributes-wrap"><?php esc_html_e( '请先在商品属性中创建属性。', 'tailwindscore' ); ?></p>
					<?php else : ?>
						<?php
						$ordered = $attributes;
						foreach ( array_keys( $taxonomies ) as $slug ) {
							if ( ! in_array( $slug, $ordered, true ) ) {
								$ordered[] = $slug;
							}
						}
						?>
						<div class="ts-pg-attributes-wrap">
							<table class="widefat striped" id="ts-pg-attributes">
								<thead>
									<tr>
										<th style="width:2.5rem;"></th>
										<th><?php esc_html_e( 'Include', 'tailwindscore' ); ?></th>
										<th><?php esc_html_e( 'Attribute', 'tailwindscore' ); ?></th>
									</tr>
								</thead>
								<tbody id="ts-pg-attributes-body">
									<?php foreach ( $ordered as $slug ) : ?>
										<?php
										if ( ! isset( $taxonomies[ $slug ] ) ) {
											continue;
										}
										$id      = 'ts-pg-attr-' . sanitize_html_class( $slug );
										$checked = in_array( $slug, $attributes, true );
										?>
										<tr data-attr="<?php echo esc_attr( $slug ); ?>">
											<td>
												<span class="ts-pg-attributes__handle dashicons dashicons-menu" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'tailwindscore' ); ?>"></span>
											</td>
											<td>
												<input
													id="<?php echo esc_attr( $id ); ?>"
													type="checkbox"
													name="ts_pg_attributes[]"
													value="<?php echo esc_attr( $slug ); ?>"
													aria-label="<?php echo esc_attr( $taxonomies[ $slug ] ); ?>"
													<?php checked( $checked ); ?>
												/>
											</td>
											<td>
												<span class="ts-pg-attr-name"><?php echo esc_html( $taxonomies[ $slug ] ); ?></span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>

				<div class="form-field ts-pg-attr-actions">
					<label><?php esc_html_e( 'Per-attribute action', 'tailwindscore' ); ?></label>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Attribute', 'tailwindscore' ); ?></th>
								<th><?php esc_html_e( 'Action', 'tailwindscore' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $taxonomies as $slug => $label ) : ?>
								<?php
								$action = $attr_actions[ $slug ] ?? Tailwindscore_Product_Group_Repository::ACTION_NONE;
								?>
								<tr data-attr="<?php echo esc_attr( $slug ); ?>">
									<td><?php echo esc_html( $label ); ?></td>
									<td>
										<select name="ts_pg_attr_actions[<?php echo esc_attr( $slug ); ?>]">
											<option value="none" <?php selected( $action, 'none' ); ?>><?php esc_html_e( 'None', 'tailwindscore' ); ?></option>
											<option value="disable_image" <?php selected( $action, 'disable_image' ); ?>><?php esc_html_e( 'Disable image display', 'tailwindscore' ); ?></option>
											<option value="hide_row" <?php selected( $action, 'hide_row' ); ?>><?php esc_html_e( 'Hide row', 'tailwindscore' ); ?></option>
										</select>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int     $post_id 组 ID。
	 * @param WP_Post $post    文章对象。
	 * @return void
	 */
	public static function save_meta( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['ts_product_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ts_product_group_nonce'] ) ), 'ts_product_group_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$product_ids = isset( $_POST['ts_pg_product_ids'] ) && is_array( $_POST['ts_pg_product_ids'] )
			? array_map( 'absint', wp_unslash( $_POST['ts_pg_product_ids'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$attributes = isset( $_POST['ts_pg_attributes'] ) && is_array( $_POST['ts_pg_attributes'] )
			? array_map( 'sanitize_title', wp_unslash( $_POST['ts_pg_attributes'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$raw_actions = isset( $_POST['ts_pg_attr_actions'] ) && is_array( $_POST['ts_pg_attr_actions'] )
			? wp_unslash( $_POST['ts_pg_attr_actions'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$attr_actions = array();
		foreach ( $raw_actions as $attr => $action ) {
			$attr_actions[ sanitize_title( (string) $attr ) ] = sanitize_key( (string) $action );
		}

		$notices = Tailwindscore_Product_Group_Repository::save_group_meta(
			$post_id,
			$product_ids,
			$attributes,
			$attr_actions
		);

		if ( array() !== $notices ) {
			set_transient( 'ts_pg_admin_notices_' . get_current_user_id(), $notices, 60 );
		}
	}

	/**
	 * @return void
	 */
	public static function render_notices(): void {
		$key     = 'ts_pg_admin_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		if ( ! is_array( $notices ) || array() === $notices ) {
			return;
		}

		delete_transient( $key );

		foreach ( $notices as $message ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html( (string) $message )
			);
		}
	}

	/**
	 * @param array<string, string> $columns 列。
	 * @return array<string, string>
	 */
	public static function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['ts_pg_products']    = __( 'Products', 'tailwindscore' );
				$new['ts_pg_attributes']  = __( 'Attributes', 'tailwindscore' );
			}
		}
		return $new;
	}

	/**
	 * @param string $column  列键。
	 * @param int    $post_id 组 ID。
	 * @return void
	 */
	public static function column_content( string $column, int $post_id ): void {
		if ( 'ts_pg_products' === $column ) {
			$ids = Tailwindscore_Product_Group_Repository::get_product_ids( $post_id );
			if ( array() === $ids ) {
				echo '&mdash;';
				return;
			}
			$labels = array();
			foreach ( $ids as $id ) {
				$product = wc_get_product( $id );
				$labels[] = $product instanceof WC_Product
					? self::format_product_sku_label( $product )
					: (string) $id;
			}
			echo esc_html( implode( ', ', $labels ) );
			return;
		}

		if ( 'ts_pg_attributes' === $column ) {
			$attrs = Tailwindscore_Product_Group_Repository::get_attributes( $post_id );
			if ( array() === $attrs ) {
				echo '&mdash;';
				return;
			}
			$labels = array();
			foreach ( $attrs as $slug ) {
				$labels[] = wc_attribute_label( $slug );
			}
			echo esc_html( implode( ', ', $labels ) );
		}
	}

	/**
	 * @return array<string, string> slug => label
	 */
	private static function get_attribute_taxonomies(): array {
		$out = array();
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $out;
		}

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$name = isset( $tax->attribute_name ) ? (string) $tax->attribute_name : '';
			if ( '' === $name ) {
				continue;
			}
			$slug = function_exists( 'wc_attribute_taxonomy_name' )
				? wc_attribute_taxonomy_name( $name )
				: 'pa_' . $name;
			$label = isset( $tax->attribute_label ) && '' !== (string) $tax->attribute_label
				? (string) $tax->attribute_label
				: $name;
			$out[ $slug ] = $label;
		}

		return $out;
	}
}
