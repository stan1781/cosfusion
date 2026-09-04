<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Kirki theme_mod 导入 / 导出（白名单 JSON，非全量 theme_mods）。
 *
 * 外观 → 导入/导出
 *
 * @package TailwindScore
 */

const TAILWINDSCORE_KIRKI_EXPORT_SCHEMA         = 'tailwindscore.kirki.v1';
const TAILWINDSCORE_KIRKI_IMPORT_TRANSIENT_PREFIX = 'ts_kirki_import_';
const TAILWINDSCORE_KIRKI_BACKUP_OPTION         = 'tailwindscore_kirki_local_backup';

/**
 * 站点专属键：跨站默认跳过（Woo rate / term / attachment ID）。
 *
 * @return list<string>
 */
function tailwindscore_kirki_export_site_bound_keys(): array {
	$keys = array_keys( tailwindscore_kirki_export_site_bound_key_labels() );

	/**
	 * @param list<string> $keys
	 */
	return apply_filters( 'tailwindscore/kirki/export/site_bound_keys', $keys );
}

/**
 * 站点专属键中文说明（与 Customizer 字段 label 一致）。
 *
 * @return array<string, string>
 */
function tailwindscore_kirki_export_site_bound_key_labels(): array {
	$labels = array(
		'shipping_hide_rates_ids'         => __( '免运达标后隐藏的付费 rate ID', 'tailwindscore' ),
		'not_found_products_category_id'  => __( '404 人气商品分类 ID', 'tailwindscore' ),
		'shop_archive_header_bg_default'  => __( '默认归档 Hero 背景（附件 ID）', 'tailwindscore' ),
		'shop_page_header_bg'             => __( '商店页 Hero 背景（附件 ID）', 'tailwindscore' ),
		'footer_payment_image'            => __( '页脚支付图标（附件 ID）', 'tailwindscore' ),
		'nav_product_cat_sync_menu_id'    => __( '商品分类菜单同步目标菜单 ID', 'tailwindscore' ),
	);

	/**
	 * @param array<string, string> $labels
	 */
	return apply_filters( 'tailwindscore/kirki/export/site_bound_key_labels', $labels );
}

/**
 * 媒体相关键（URL 或含 URL 的 repeater）；导入时可做域名替换。
 *
 * @return list<string>
 */
function tailwindscore_kirki_export_media_keys(): array {
	$keys = array(
		'tailwindscore_logo_pc',
		'tailwindscore_logo_mobile',
		'account_auth_hero_image',
		'not_found_illustration',
		'home_hero_slides',
		'not_found_categories',
		'header_bar_items',
		'product_assurances_items',
	);

	/**
	 * @param list<string> $keys
	 */
	return apply_filters( 'tailwindscore/kirki/export/media_keys', $keys );
}

/**
 * 不可导出的占位 / custom 键（Kirki 5 新 Field API 可能去掉 type）。
 *
 * @return list<string>
 */
function tailwindscore_kirki_export_excluded_keys(): array {
	$keys = array(
		'footer_nav_menu_notice',
		'tailwindscore_checkout_settings_notice',
	);

	/**
	 * @param list<string> $keys
	 */
	return apply_filters( 'tailwindscore/kirki/export/excluded_keys', $keys );
}

/**
 * 从 Kirki 注册表收集可导出的 theme_mod 键（排除 custom 占位）。
 *
 * Kirki 5：多数字段在 `$all_fields`；旧兼容层在 `$fields`。
 *
 * @return list<string>
 */
function tailwindscore_kirki_exportable_setting_keys(): array {
	$keys     = array();
	$sources  = array();
	$excluded = array_flip( tailwindscore_kirki_export_excluded_keys() );

	if ( class_exists( '\Kirki\Compatibility\Kirki' ) ) {
		if ( ! empty( \Kirki\Compatibility\Kirki::$all_fields ) && is_array( \Kirki\Compatibility\Kirki::$all_fields ) ) {
			$sources[] = \Kirki\Compatibility\Kirki::$all_fields;
		}
		if ( ! empty( \Kirki\Compatibility\Kirki::$fields ) && is_array( \Kirki\Compatibility\Kirki::$fields ) ) {
			$sources[] = \Kirki\Compatibility\Kirki::$fields;
		}
	}

	$config_id = function_exists( 'tailwindscore_kirki_config_id' ) ? tailwindscore_kirki_config_id() : 'tailwindscore';

	foreach ( $sources as $registry ) {
		foreach ( $registry as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( 'custom' === $type ) {
				continue;
			}
			$option_type = isset( $field['option_type'] ) ? (string) $field['option_type'] : 'theme_mod';
			if ( 'theme_mod' !== $option_type ) {
				continue;
			}
			$field_config = isset( $field['kirki_config'] ) ? (string) $field['kirki_config'] : '';
			if ( '' !== $field_config && $field_config !== $config_id ) {
				continue;
			}
			$setting = $field['settings'] ?? null;
			if ( is_string( $setting ) && '' !== $setting ) {
				if ( isset( $excluded[ $setting ] ) ) {
					continue;
				}
				$keys[] = $setting;
			} elseif ( is_array( $setting ) ) {
				foreach ( $setting as $s ) {
					if ( is_string( $s ) && '' !== $s && ! isset( $excluded[ $s ] ) ) {
						$keys[] = $s;
					}
				}
			}
		}
	}

	$keys = array_values( array_unique( $keys ) );
	sort( $keys );

	/**
	 * @param list<string> $keys
	 */
	return apply_filters( 'tailwindscore/kirki/export/setting_keys', $keys );
}

/**
 * 构建导出 payload。
 *
 * @param bool $include_site_bound 是否包含站点专属键（rate / term / attachment ID）。
 * @return array{
 *   schema: string,
 *   exported_at: string,
 *   theme: string,
 *   stylesheet: string,
 *   home_url: string,
 *   settings: array<string, mixed>,
 *   skipped_on_import_default: list<string>,
 *   media_keys: list<string>,
 *   includes_site_bound: bool
 * }
 */
function tailwindscore_kirki_build_export_payload( bool $include_site_bound = true ): array {
	$allowlist      = array_flip( tailwindscore_kirki_exportable_setting_keys() );
	$site_bound     = tailwindscore_kirki_export_site_bound_keys();
	$site_bound_map = array_flip( $site_bound );
	$media_keys     = tailwindscore_kirki_export_media_keys();
	$mods           = get_theme_mods();
	$settings       = array();

	if ( ! is_array( $mods ) ) {
		$mods = array();
	}

	foreach ( $mods as $key => $value ) {
		if ( ! is_string( $key ) || ! isset( $allowlist[ $key ] ) ) {
			continue;
		}
		if ( ! $include_site_bound && isset( $site_bound_map[ $key ] ) ) {
			continue;
		}
		$settings[ $key ] = $value;
	}

	ksort( $settings );

	return array(
		'schema'                    => TAILWINDSCORE_KIRKI_EXPORT_SCHEMA,
		'exported_at'               => gmdate( 'c' ),
		'theme'                     => 'tailwindscore',
		'stylesheet'                => get_stylesheet(),
		'home_url'                  => home_url( '/' ),
		'settings'                  => $settings,
		// 元数据：导入端默认跳过的键（须勾选「导入站点专属 ID」才写入）。
		'skipped_on_import_default' => $site_bound,
		'media_keys'                => $media_keys,
		'includes_site_bound'       => $include_site_bound,
	);
}

/**
 * @param mixed $data 解码后的 JSON。
 * @return true|WP_Error
 */
function tailwindscore_kirki_validate_import_payload( $data ) {
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'ts_kirki_import_invalid', __( '无效的 JSON 结构。', 'tailwindscore' ) );
	}

	$schema = isset( $data['schema'] ) ? (string) $data['schema'] : '';
	if ( TAILWINDSCORE_KIRKI_EXPORT_SCHEMA !== $schema ) {
		return new WP_Error(
			'ts_kirki_import_schema',
			sprintf(
				/* translators: %s: expected schema id */
				__( '不支持的 schema（需要 %s）。', 'tailwindscore' ),
				TAILWINDSCORE_KIRKI_EXPORT_SCHEMA
			)
		);
	}

	if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
		return new WP_Error( 'ts_kirki_import_settings', __( '缺少 settings 对象。', 'tailwindscore' ) );
	}

	return true;
}

/**
 * 递归替换字符串中的源站 URL。
 *
 * @param mixed  $value    值。
 * @param string $from_url 源 home_url（带尾斜杠）。
 * @param string $to_url   目标 home_url（带尾斜杠）。
 * @return mixed
 */
function tailwindscore_kirki_replace_home_url_in_value( $value, string $from_url, string $to_url ) {
	if ( $from_url === $to_url || '' === $from_url ) {
		return $value;
	}

	if ( is_string( $value ) ) {
		return str_replace( $from_url, $to_url, $value );
	}

	if ( is_array( $value ) ) {
		$out = array();
		foreach ( $value as $k => $v ) {
			$out[ $k ] = tailwindscore_kirki_replace_home_url_in_value( $v, $from_url, $to_url );
		}
		return $out;
	}

	return $value;
}

/**
 * 计算导入计划（不写库）。
 *
 * @param array<string, mixed> $payload          已校验 payload。
 * @param bool                 $force_site_bound 是否写入站点专属键。
 * @param bool                 $replace_urls     是否替换媒体 URL 域名。
 * @return array{
 *   apply: array<string, mixed>,
 *   skip_unknown: list<string>,
 *   skip_site_bound: list<string>,
 *   create: list<string>,
 *   update: list<string>,
 *   unchanged: list<string>
 * }
 */
function tailwindscore_kirki_plan_import( array $payload, bool $force_site_bound, bool $replace_urls ): array {
	$allowlist  = array_flip( tailwindscore_kirki_exportable_setting_keys() );
	$site_bound = array_flip( tailwindscore_kirki_export_site_bound_keys() );
	$media_keys = array_flip( tailwindscore_kirki_export_media_keys() );
	$mods       = get_theme_mods();
	if ( ! is_array( $mods ) ) {
		$mods = array();
	}

	$from_url = isset( $payload['home_url'] ) ? trailingslashit( (string) $payload['home_url'] ) : '';
	$to_url   = trailingslashit( home_url( '/' ) );

	$apply           = array();
	$skip_unknown    = array();
	$skip_site_bound = array();
	$create          = array();
	$update          = array();
	$unchanged       = array();

	foreach ( $payload['settings'] as $key => $value ) {
		if ( ! is_string( $key ) ) {
			continue;
		}

		if ( ! isset( $allowlist[ $key ] ) ) {
			$skip_unknown[] = $key;
			continue;
		}

		if ( ! $force_site_bound && isset( $site_bound[ $key ] ) ) {
			$skip_site_bound[] = $key;
			continue;
		}

		if ( $replace_urls && isset( $media_keys[ $key ] ) ) {
			$value = tailwindscore_kirki_replace_home_url_in_value( $value, $from_url, $to_url );
		}

		$exists = array_key_exists( $key, $mods );
		if ( $exists && $mods[ $key ] === $value ) {
			$unchanged[] = $key;
			continue;
		}

		$apply[ $key ] = $value;
		if ( $exists ) {
			$update[] = $key;
		} else {
			$create[] = $key;
		}
	}

	return array(
		'apply'           => $apply,
		'skip_unknown'    => $skip_unknown,
		'skip_site_bound' => $skip_site_bound,
		'create'          => $create,
		'update'          => $update,
		'unchanged'       => $unchanged,
	);
}

/**
 * 执行导入计划。
 *
 * @param array<string, mixed> $apply 键 => 值。
 */
function tailwindscore_kirki_apply_import( array $apply ): void {
	foreach ( $apply as $key => $value ) {
		if ( ! is_string( $key ) ) {
			continue;
		}
		set_theme_mod( $key, $value );
	}
}

/**
 * 注册外观子菜单。
 */
function tailwindscore_kirki_import_export_register_menu(): void {
	add_theme_page(
		__( '主题设置导入/导出', 'tailwindscore' ),
		__( '导入/导出', 'tailwindscore' ),
		'edit_theme_options',
		'ts-kirki-import-export',
		'tailwindscore_kirki_import_export_render_page'
	);
}
add_action( 'admin_menu', 'tailwindscore_kirki_import_export_register_menu' );

/**
 * 下载导出 JSON。
 */
function tailwindscore_kirki_import_export_handle_download(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( '权限不足。', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_kirki_export' );

	$include_site_bound = ! empty( $_POST['ts_kirki_export_include_site_bound'] );
	$payload            = tailwindscore_kirki_build_export_payload( $include_site_bound );
	$json               = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$filename           = sprintf(
		'tailwindscore-kirki-%s.json',
		gmdate( 'Ymd-His' )
	);

	if ( false === $json ) {
		wp_die( esc_html__( '无法编码 JSON。', 'tailwindscore' ) );
	}

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . (string) strlen( $json ) );
	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON download
	exit;
}
add_action( 'admin_post_ts_kirki_export', 'tailwindscore_kirki_import_export_handle_download' );

/**
 * 上传后写入 transient，进入预览。
 */
function tailwindscore_kirki_import_export_handle_upload(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( '权限不足。', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_kirki_import_upload' );

	$redirect = admin_url( 'themes.php?page=ts-kirki-import-export' );

	if ( empty( $_FILES['ts_kirki_import_file']['tmp_name'] ) ) {
		wp_safe_redirect( add_query_arg( 'ts_kirki_notice', 'no_file', $redirect ) );
		exit;
	}

	$tmp  = (string) $_FILES['ts_kirki_import_file']['tmp_name'];
	$raw  = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local upload tmp
	$data = json_decode( is_string( $raw ) ? $raw : '', true );

	$valid = tailwindscore_kirki_validate_import_payload( $data );
	if ( is_wp_error( $valid ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'ts_kirki_notice' => 'invalid',
					'ts_kirki_msg'    => rawurlencode( $valid->get_error_message() ),
				),
				$redirect
			)
		);
		exit;
	}

	$force_site_bound = ! empty( $_POST['ts_kirki_force_site_bound'] );
	$replace_urls     = ! empty( $_POST['ts_kirki_replace_urls'] );
	$token            = wp_generate_password( 20, false );
	$plan             = tailwindscore_kirki_plan_import( $data, $force_site_bound, $replace_urls );

	set_transient(
		TAILWINDSCORE_KIRKI_IMPORT_TRANSIENT_PREFIX . $token,
		array(
			'payload'          => $data,
			'force_site_bound' => $force_site_bound,
			'replace_urls'     => $replace_urls,
			'plan'             => $plan,
			'user_id'          => get_current_user_id(),
		),
		15 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'ts_kirki_preview' => $token,
			),
			$redirect
		)
	);
	exit;
}
add_action( 'admin_post_ts_kirki_import_upload', 'tailwindscore_kirki_import_export_handle_upload' );

/**
 * 确认写入。
 */
function tailwindscore_kirki_import_export_handle_confirm(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( '权限不足。', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_kirki_import_confirm' );

	$redirect = admin_url( 'themes.php?page=ts-kirki-import-export' );
	$token    = isset( $_POST['ts_kirki_preview'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ts_kirki_preview'] ) ) : '';
	$stored   = $token ? get_transient( TAILWINDSCORE_KIRKI_IMPORT_TRANSIENT_PREFIX . $token ) : false;

	if ( ! is_array( $stored ) || (int) ( $stored['user_id'] ?? 0 ) !== get_current_user_id() ) {
		wp_safe_redirect( add_query_arg( 'ts_kirki_notice', 'expired', $redirect ) );
		exit;
	}

	$plan = $stored['plan'] ?? null;
	if ( ! is_array( $plan ) || ! isset( $plan['apply'] ) || ! is_array( $plan['apply'] ) ) {
		wp_safe_redirect( add_query_arg( 'ts_kirki_notice', 'expired', $redirect ) );
		exit;
	}

	// 导入前自动存一份本地快照（白名单 JSON）。
	update_option( TAILWINDSCORE_KIRKI_BACKUP_OPTION, tailwindscore_kirki_build_export_payload(), false );

	tailwindscore_kirki_apply_import( $plan['apply'] );
	delete_transient( TAILWINDSCORE_KIRKI_IMPORT_TRANSIENT_PREFIX . $token );

	$applied = count( $plan['apply'] );
	wp_safe_redirect(
		add_query_arg(
			array(
				'ts_kirki_notice'  => 'imported',
				'ts_kirki_applied' => (string) $applied,
			),
			$redirect
		)
	);
	exit;
}
add_action( 'admin_post_ts_kirki_import_confirm', 'tailwindscore_kirki_import_export_handle_confirm' );

/**
 * 取消预览。
 */
function tailwindscore_kirki_import_export_handle_cancel(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( '权限不足。', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_kirki_import_cancel' );

	$token = isset( $_GET['ts_kirki_preview'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ts_kirki_preview'] ) ) : '';
	if ( $token ) {
		delete_transient( TAILWINDSCORE_KIRKI_IMPORT_TRANSIENT_PREFIX . $token );
	}

	wp_safe_redirect( admin_url( 'themes.php?page=ts-kirki-import-export' ) );
	exit;
}
add_action( 'admin_post_ts_kirki_import_cancel', 'tailwindscore_kirki_import_export_handle_cancel' );

/**
 * 从本地备份恢复。
 */
function tailwindscore_kirki_import_export_handle_restore_backup(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( '权限不足。', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_kirki_restore_backup' );

	$redirect = admin_url( 'themes.php?page=ts-kirki-import-export' );
	$backup   = get_option( TAILWINDSCORE_KIRKI_BACKUP_OPTION, null );
	$valid    = tailwindscore_kirki_validate_import_payload( $backup );

	if ( is_wp_error( $valid ) ) {
		wp_safe_redirect( add_query_arg( 'ts_kirki_notice', 'no_backup', $redirect ) );
		exit;
	}

	$plan = tailwindscore_kirki_plan_import( $backup, true, false );
	tailwindscore_kirki_apply_import( $plan['apply'] );

	wp_safe_redirect(
		add_query_arg(
			array(
				'ts_kirki_notice'  => 'restored',
				'ts_kirki_applied' => (string) count( $plan['apply'] ),
			),
			$redirect
		)
	);
	exit;
}
add_action( 'admin_post_ts_kirki_restore_backup', 'tailwindscore_kirki_import_export_handle_restore_backup' );

/**
 * 手动保存本地快照。
 */
function tailwindscore_kirki_import_export_handle_save_backup(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( '权限不足。', 'tailwindscore' ) );
	}

	check_admin_referer( 'ts_kirki_save_backup' );

	update_option( TAILWINDSCORE_KIRKI_BACKUP_OPTION, tailwindscore_kirki_build_export_payload(), false );

	wp_safe_redirect(
		add_query_arg(
			'ts_kirki_notice',
			'backup_saved',
			admin_url( 'themes.php?page=ts-kirki-import-export' )
		)
	);
	exit;
}
add_action( 'admin_post_ts_kirki_save_backup', 'tailwindscore_kirki_import_export_handle_save_backup' );

/**
 * @param list<string> $keys
 */
function tailwindscore_kirki_import_export_render_key_list( array $keys ): void {
	if ( array() === $keys ) {
		echo '<p><em>' . esc_html__( '（无）', 'tailwindscore' ) . '</em></p>';
		return;
	}
	$labels = tailwindscore_kirki_export_site_bound_key_labels();
	echo '<ul style="columns:2;max-width:56rem;margin:0.5em 0;">';
	foreach ( $keys as $key ) {
		$label = isset( $labels[ $key ] ) ? (string) $labels[ $key ] : '';
		if ( '' !== $label ) {
			echo '<li><code>' . esc_html( $key ) . '</code> — ' . esc_html( $label ) . '</li>';
		} else {
			echo '<li><code>' . esc_html( $key ) . '</code></li>';
		}
	}
	echo '</ul>';
}

/**
 * 后台页面。
 */
function tailwindscore_kirki_import_export_render_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$notice      = isset( $_GET['ts_kirki_notice'] ) ? sanitize_key( (string) wp_unslash( $_GET['ts_kirki_notice'] ) ) : '';
	$preview     = isset( $_GET['ts_kirki_preview'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ts_kirki_preview'] ) ) : '';
	$stored      = $preview ? get_transient( TAILWINDSCORE_KIRKI_IMPORT_TRANSIENT_PREFIX . $preview ) : false;
	$backup      = get_option( TAILWINDSCORE_KIRKI_BACKUP_OPTION, null );
	$has_backup  = is_array( $backup ) && isset( $backup['exported_at'] );
	$allow_count = count( tailwindscore_kirki_exportable_setting_keys() );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( '主题设置导入/导出', 'tailwindscore' ) . '</h1>';
	echo '<p>' . esc_html__( '仅导出已注册的 Kirki theme_mod（白名单），不含菜单位置等其它 theme_mods。格式为 JSON。', 'tailwindscore' ) . '</p>';

	if ( 0 === $allow_count ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( '未检测到 Kirki 字段。请确认已启用 Kirki 插件。', 'tailwindscore' ) . '</p></div>';
	}

	if ( 'imported' === $notice || 'restored' === $notice ) {
		$applied = isset( $_GET['ts_kirki_applied'] ) ? (int) $_GET['ts_kirki_applied'] : 0;
		$msg     = 'restored' === $notice
			? sprintf(
				/* translators: %d: number of keys */
				__( '已从本机备份还原 %d 个设置。', 'tailwindscore' ),
				$applied
			)
			: sprintf(
				/* translators: %d: number of keys */
				__( '已导入 %d 个设置。运送 rate ID、分类 ID、附件 ID 与媒体请人工核验。', 'tailwindscore' ),
				$applied
			);
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		echo '<p><a class="button" href="' . esc_url( admin_url( 'customize.php' ) ) . '">' . esc_html__( '打开 Customizer', 'tailwindscore' ) . '</a></p>';
	} elseif ( 'backup_saved' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '本机快照已保存。', 'tailwindscore' ) . '</p></div>';
	} elseif ( 'no_file' === $notice ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( '请选择 JSON 文件。', 'tailwindscore' ) . '</p></div>';
	} elseif ( 'invalid' === $notice ) {
		$msg = isset( $_GET['ts_kirki_msg'] ) ? rawurldecode( (string) wp_unslash( $_GET['ts_kirki_msg'] ) ) : '';
		echo '<div class="notice notice-error"><p>' . esc_html( $msg ? $msg : __( '无效的导入文件。', 'tailwindscore' ) ) . '</p></div>';
	} elseif ( 'expired' === $notice ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( '预览已过期，请重新上传。', 'tailwindscore' ) . '</p></div>';
	} elseif ( 'no_backup' === $notice ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( '尚无本机备份。', 'tailwindscore' ) . '</p></div>';
	}

	// —— 预览确认 ——
	if ( is_array( $stored ) && (int) ( $stored['user_id'] ?? 0 ) === get_current_user_id() && isset( $stored['plan'] ) ) {
		$plan             = $stored['plan'];
		$force_site_bound = ! empty( $stored['force_site_bound'] );

		echo '<hr><h2>' . esc_html__( '导入预览', 'tailwindscore' ) . '</h2>';
		if ( $force_site_bound ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( '已勾选导入站点专属 ID。请确认本站产品 / 分类 / 运送 rate ID 与来源站一致。', 'tailwindscore' ) . '</p></div>';
		}
		echo '<p>';
		printf(
			/* translators: 1: create count, 2: update count, 3: unchanged, 4: site-bound skipped, 5: unknown skipped */
			esc_html__( '新增 %1$d · 覆盖 %2$d · 未变 %3$d · 跳过站点专属 %4$d · 跳过未知 %5$d', 'tailwindscore' ),
			count( $plan['create'] ?? array() ),
			count( $plan['update'] ?? array() ),
			count( $plan['unchanged'] ?? array() ),
			count( $plan['skip_site_bound'] ?? array() ),
			count( $plan['skip_unknown'] ?? array() )
		);
		echo '</p>';

		echo '<h3>' . esc_html__( '将新增', 'tailwindscore' ) . '</h3>';
		tailwindscore_kirki_import_export_render_key_list( $plan['create'] ?? array() );
		echo '<h3>' . esc_html__( '将覆盖', 'tailwindscore' ) . '</h3>';
		tailwindscore_kirki_import_export_render_key_list( $plan['update'] ?? array() );
		echo '<h3>' . esc_html__( '跳过（站点专属）', 'tailwindscore' ) . '</h3>';
		tailwindscore_kirki_import_export_render_key_list( $plan['skip_site_bound'] ?? array() );

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:0.5em;">';
		echo '<input type="hidden" name="action" value="ts_kirki_import_confirm" />';
		echo '<input type="hidden" name="ts_kirki_preview" value="' . esc_attr( $preview ) . '" />';
		wp_nonce_field( 'ts_kirki_import_confirm' );
		submit_button( __( '确认导入', 'tailwindscore' ), 'primary', 'submit', false );
		echo '</form>';

		$cancel_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'           => 'ts_kirki_import_cancel',
					'ts_kirki_preview' => $preview,
				),
				admin_url( 'admin-post.php' )
			),
			'ts_kirki_import_cancel'
		);
		echo '<a class="button" href="' . esc_url( $cancel_url ) . '">' . esc_html__( '取消', 'tailwindscore' ) . '</a>';
		echo '</div>';
		return;
	}

	// —— 导出 ——
	$full_payload     = tailwindscore_kirki_build_export_payload( true );
	$site_bound_keys  = tailwindscore_kirki_export_site_bound_keys();
	$site_bound_saved = 0;
	foreach ( $full_payload['settings'] as $key => $_value ) {
		if ( in_array( $key, $site_bound_keys, true ) ) {
			++$site_bound_saved;
		}
	}

	echo '<hr><h2>' . esc_html__( '导出', 'tailwindscore' ) . '</h2>';
	printf(
		'<p>' . esc_html__( '当前可导出键数：%d（仅含数据库中已保存的 theme_mod）；其中站点专属 ID：%d。', 'tailwindscore' ) . '</p>',
		count( $full_payload['settings'] ),
		$site_bound_saved
	);
	echo '<p class="description">' . esc_html__( '测试站→正式站且产品 / 分类 / 运送 rate ID 一致时，请勾选「包含站点专属 ID」。跨站迁移（ID 不同）请取消勾选。导入时再决定是否写入这些 ID。', 'tailwindscore' ) . '</p>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="ts_kirki_export" />';
	wp_nonce_field( 'ts_kirki_export' );
	echo '<p><label><input type="checkbox" name="ts_kirki_export_include_site_bound" value="1" checked /> ';
	echo esc_html__( '包含站点专属 ID（运送 rate / 产品分类 / 附件）', 'tailwindscore' );
	echo '</label></p>';
	submit_button( __( '下载 JSON', 'tailwindscore' ), 'secondary', 'submit', false );
	echo '</form>';

	// —— 导入 ——
	echo '<hr><h2>' . esc_html__( '导入', 'tailwindscore' ) . '</h2>';
	echo '<p>' . esc_html__( '默认跳过运送 rate ID、产品分类 ID、附件 ID。媒体 URL 可选择将来源站域名替换为本站。', 'tailwindscore' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
	echo '<input type="hidden" name="action" value="ts_kirki_import_upload" />';
	wp_nonce_field( 'ts_kirki_import_upload' );
	echo '<p><input type="file" name="ts_kirki_import_file" accept="application/json,.json" required /></p>';
	echo '<p><label><input type="checkbox" name="ts_kirki_force_site_bound" value="1" /> ';
	echo esc_html__( '导入站点专属 ID（测试站→正式站、同库同 ID 时勾选）', 'tailwindscore' );
	echo '</label></p>';
	echo '<p class="description">' . esc_html__( 'JSON 若含站点专属键：未勾选则跳过；勾选则写入。', 'tailwindscore' ) . '</p>';
	echo '<p><label><input type="checkbox" name="ts_kirki_replace_urls" value="1" checked /> ';
	echo esc_html__( '替换媒体 URL 中的来源站域名为本站', 'tailwindscore' );
	echo '</label></p>';
	submit_button( __( '上传并预览', 'tailwindscore' ), 'primary', 'submit', false );
	echo '</form>';

	// —— 本机快照 ——
	echo '<hr><h2>' . esc_html__( '本机快照', 'tailwindscore' ) . '</h2>';
	if ( $has_backup ) {
		printf(
			'<p>' . esc_html__( '最近快照：%s', 'tailwindscore' ) . '</p>',
			esc_html( (string) $backup['exported_at'] )
		);
	} else {
		echo '<p>' . esc_html__( '尚无快照。导入前会自动保存一份；亦可手动保存。', 'tailwindscore' ) . '</p>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:0.5em;">';
	echo '<input type="hidden" name="action" value="ts_kirki_save_backup" />';
	wp_nonce_field( 'ts_kirki_save_backup' );
	submit_button( __( '保存本机快照', 'tailwindscore' ), 'secondary', 'submit', false );
	echo '</form>';

	if ( $has_backup ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;" onsubmit="return confirm(\'' . esc_js( __( '确定以本机快照覆盖当前设置？', 'tailwindscore' ) ) . '\');">';
		echo '<input type="hidden" name="action" value="ts_kirki_restore_backup" />';
		wp_nonce_field( 'ts_kirki_restore_backup' );
		submit_button( __( '还原本机快照', 'tailwindscore' ), 'delete', 'submit', false );
		echo '</form>';
	}

	echo '<hr><h2>' . esc_html__( '站点专属键', 'tailwindscore' ) . '</h2>';
	echo '<p class="description">' . esc_html__( '导出可选择是否写入 JSON；导入时未勾选「导入站点专属 ID」则跳过。适用于测试站→正式站且产品 / 分类 / 运送 ID 相同的情况。', 'tailwindscore' ) . '</p>';
	tailwindscore_kirki_import_export_render_key_list( tailwindscore_kirki_export_site_bound_keys() );

	echo '</div>';
}
