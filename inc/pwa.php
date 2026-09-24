<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * PWA Level 1：可安装壳层 + 仅缓存主题 dist 静态资源。
 */

const TAILWINDSCORE_PWA_REWRITE_VERSION = '6';
const TAILWINDSCORE_PWA_MANIFEST_OPTION = 'tailwindscore_pwa_manifest_sig';

/**
 * 主题 style.css Version: 行。
 */
function tailwindscore_pwa_theme_version(): string {
	$style = get_template_directory() . '/style.css';
	if ( ! is_readable( $style ) ) {
		return '0';
	}

	$contents = (string) file_get_contents( $style );
	if ( 1 === preg_match( '/^Version:\s*(.+)$/m', $contents, $matches ) ) {
		return trim( (string) $matches[1] );
	}

	return '0';
}

/**
 * 本机开发主机（允许非 HTTPS 注册 SW）。
 */
function tailwindscore_pwa_is_local_host(): bool {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! is_string( $host ) || '' === $host ) {
		return false;
	}

	$host = strtolower( $host );

	return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true )
		|| str_ends_with( $host, '.local' )
		|| str_ends_with( $host, '.test' );
}

/**
 * 是否启用前台 PWA（manifest 链接 + SW 注册）。
 */
function tailwindscore_pwa_is_enabled(): bool {
	if ( is_admin() ) {
		return false;
	}

	if ( ! (bool) tailwindscore_mod( 'pwa_enabled', false ) ) {
		return false;
	}

	if ( function_exists( 'tailwindscore_is_vite_dev_enabled' ) && tailwindscore_is_vite_dev_enabled() ) {
		return false;
	}

	$secure = is_ssl() || tailwindscore_pwa_is_local_host();

	/**
	 * 过滤是否启用 PWA Level 1。
	 *
	 * @param bool $enabled 默认：Kirki 开关开、非 Vite dev、HTTPS 或本机。
	 */
	return (bool) apply_filters( 'tailwindscore/pwa/enabled', $secure );
}

/**
 * SW 注册 URL：用 query var（首页主查询非 404 → 稳定 200）。
 * 本站主机上 `/ts-sw.js` rewrite 常带 WP 404 状态码，浏览器会拒绝注册。
 */
function tailwindscore_pwa_sw_url(): string {
	return esc_url_raw( home_url( '/?tailwindscore_pwa_asset=sw' ) );
}

/**
 * @return array{basedir: string, baseurl: string}|null
 */
function tailwindscore_pwa_upload_paths(): ?array {
	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) {
		return null;
	}

	return array(
		'basedir' => trailingslashit( (string) $upload['basedir'] ) . 'ts-pwa',
		'baseurl' => trailingslashit( (string) $upload['baseurl'] ) . 'ts-pwa',
	);
}

/**
 * 将 manifest 写入 uploads/ts-pwa/manifest.json（主题目录可能不可写）。
 */
function tailwindscore_pwa_ensure_static_manifest(): bool {
	$paths = tailwindscore_pwa_upload_paths();
	if ( null === $paths ) {
		return false;
	}

	$payload = tailwindscore_pwa_manifest_payload();
	$json    = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	if ( ! is_string( $json ) || '' === $json ) {
		return false;
	}

	$json .= "\n";
	$sig   = md5( $json );
	$path  = $paths['basedir'] . '/manifest.json';

	if ( get_option( TAILWINDSCORE_PWA_MANIFEST_OPTION ) === $sig && is_readable( $path ) ) {
		return true;
	}

	if ( ! wp_mkdir_p( $paths['basedir'] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- small generated public JSON.
	if ( false === file_put_contents( $path, $json ) ) {
		return false;
	}

	update_option( TAILWINDSCORE_PWA_MANIFEST_OPTION, $sig, true );

	return true;
}

function tailwindscore_pwa_manifest_url(): string {
	if ( tailwindscore_pwa_ensure_static_manifest() ) {
		$paths = tailwindscore_pwa_upload_paths();
		if ( null !== $paths ) {
			return esc_url_raw( $paths['baseurl'] . '/manifest.json' );
		}
	}

	// 回退：REST（部分环境 uploads 不可写时）。
	return esc_url_raw( rest_url( 'tailwindscore/v1/pwa/manifest' ) );
}

function tailwindscore_pwa_refresh_static_manifest(): void {
	tailwindscore_pwa_maybe_generate_icons_from_kirki();
	delete_option( TAILWINDSCORE_PWA_MANIFEST_OPTION );
	tailwindscore_pwa_ensure_static_manifest();
}
add_action( 'customize_save_after', 'tailwindscore_pwa_refresh_static_manifest' );
add_action( 'after_switch_theme', 'tailwindscore_pwa_refresh_static_manifest' );

/**
 * Service Worker scope（站点 home path，含子目录安装）。
 */
function tailwindscore_pwa_scope_path(): string {
	$path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );

	if ( ! is_string( $path ) || '' === $path ) {
		return '/';
	}

	return trailingslashit( $path );
}

/**
 * 主题 dist 的 URL path 前缀（含尾部 slash），供 SW 匹配。
 */
function tailwindscore_pwa_dist_path_prefix(): string {
	$path = wp_parse_url( get_template_directory_uri() . '/assets/dist/', PHP_URL_PATH );

	return is_string( $path ) ? $path : '/wp-content/themes/tailwindscore/assets/dist/';
}

/**
 * Vite 构建后的 SW 文件绝对路径；缺失时返回空字串。
 */
function tailwindscore_pwa_built_sw_path(): string {
	$manifest = function_exists( 'tailwindscore_manifest' ) ? tailwindscore_manifest() : array();
	$key      = 'src/ts/pwa/sw.ts';

	if ( empty( $manifest[ $key ]['file'] ) || ! is_string( $manifest[ $key ]['file'] ) ) {
		return '';
	}

	$path = get_template_directory() . '/assets/dist/' . ltrim( $manifest[ $key ]['file'], '/' );

	return is_readable( $path ) ? $path : '';
}

/**
 * @return list<string> 站点绝对 URL（precache）。
 */
function tailwindscore_pwa_precache_urls(): array {
	$file = get_template_directory() . '/assets/dist/pwa-precache.json';
	if ( ! is_readable( $file ) ) {
		return array();
	}

	$data = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $data ) ) {
		return array();
	}

	$urls = array();
	foreach ( $data as $relative ) {
		if ( ! is_string( $relative ) || '' === $relative ) {
			continue;
		}
		$urls[] = esc_url_raw( tailwindscore_dist_url( $relative ) );
	}

	return array_values( array_unique( array_filter( $urls ) ) );
}

/**
 * 主题内占位图标目录（本地 `npm run pwa:icons`；产线主题目录通常不可写，不作生成目标）。
 */
function tailwindscore_pwa_theme_icons_dir(): string {
	return trailingslashit( get_template_directory() ) . 'assets/pwa';
}

/**
 * 正式图标写入目录：优先 uploads/ts-pwa（与 manifest 同目录，可写）。
 */
function tailwindscore_pwa_icons_output_dir(): string {
	$paths = tailwindscore_pwa_upload_paths();
	if ( null !== $paths ) {
		return $paths['basedir'];
	}

	return tailwindscore_pwa_theme_icons_dir();
}

/**
 * @return array{192: string, 512: string, maskable: string, apple: string}
 */
function tailwindscore_pwa_icon_filenames(): array {
	return array(
		'192'      => 'icon-192.png',
		'512'      => 'icon-512.png',
		'maskable' => 'icon-maskable-512.png',
		'apple'    => 'icon-192.png',
	);
}

/**
 * 将 Kirki 图片 URL / 附件解析为可读本地路径。
 */
function tailwindscore_pwa_resolve_image_path( string $source ): string {
	$source = trim( $source );
	if ( '' === $source ) {
		return '';
	}

	if ( is_readable( $source ) ) {
		return $source;
	}

	$attachment_id = attachment_url_to_postid( $source );
	if ( $attachment_id > 0 ) {
		$file = get_attached_file( $attachment_id );
		if ( is_string( $file ) && is_readable( $file ) ) {
			return $file;
		}
	}

	$upload = wp_upload_dir();
	if ( empty( $upload['error'] ) ) {
		$baseurl = trailingslashit( (string) $upload['baseurl'] );
		$basedir = trailingslashit( (string) $upload['basedir'] );
		foreach ( array( $baseurl, set_url_scheme( $baseurl, 'http' ), set_url_scheme( $baseurl, 'https' ) ) as $prefix ) {
			if ( str_starts_with( $source, $prefix ) ) {
				$relative = ltrim( substr( $source, strlen( $prefix ) ), '/' );
				$path     = $basedir . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
				if ( is_readable( $path ) ) {
					return $path;
				}
			}
		}
	}

	return '';
}

/**
 * 中心裁剪并保存为正方形 PNG。
 */
function tailwindscore_pwa_save_square_png( string $source_path, string $dest_path, int $size ): bool {
	$editor = wp_get_image_editor( $source_path );
	if ( is_wp_error( $editor ) ) {
		return false;
	}

	$editor->resize( $size, $size, true );
	$result = $editor->save( $dest_path, 'image/png' );

	return ! is_wp_error( $result ) && is_readable( $dest_path );
}

/**
 * maskable：留约 10% 安全边，底色为品牌主色。
 */
function tailwindscore_pwa_save_maskable_png( string $source_path, string $dest_path, int $size ): bool {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return tailwindscore_pwa_save_square_png( $source_path, $dest_path, $size );
	}

	$blob = file_get_contents( $source_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $blob ) {
		return false;
	}

	$src = @imagecreatefromstring( $blob );
	if ( false === $src ) {
		return false;
	}

	$defaults = array( 'color_primary' => '#181818' );
	$hex      = sanitize_hex_color( (string) tailwindscore_mod( 'color_primary', $defaults['color_primary'] ?? '#181818' ) ) ?: '#181818';
	$r        = hexdec( substr( $hex, 1, 2 ) );
	$g        = hexdec( substr( $hex, 3, 2 ) );
	$b        = hexdec( substr( $hex, 5, 2 ) );

	$canvas = imagecreatetruecolor( $size, $size );
	if ( false === $canvas ) {
		imagedestroy( $src );
		return false;
	}

	$bg = imagecolorallocate( $canvas, $r, $g, $b );
	imagefilledrectangle( $canvas, 0, 0, $size, $size, $bg );

	$inset = (int) round( $size * 0.1 );
	$inner = max( 1, $size - ( 2 * $inset ) );
	$sw    = imagesx( $src );
	$sh    = imagesy( $src );
	$side  = min( $sw, $sh );
	$sx    = (int) max( 0, ( $sw - $side ) / 2 );
	$sy    = (int) max( 0, ( $sh - $side ) / 2 );

	imagecopyresampled( $canvas, $src, $inset, $inset, $sx, $sy, $inner, $inner, $side, $side );
	imagedestroy( $src );

	$dir = dirname( $dest_path );
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		imagedestroy( $canvas );
		return false;
	}

	$ok = imagepng( $canvas, $dest_path, 6 );
	imagedestroy( $canvas );

	return false !== $ok && is_readable( $dest_path );
}

/**
 * 根据 Kirki `pwa_icon` 生成正式图标到 uploads/ts-pwa（主题目录产线常不可写）。
 */
function tailwindscore_pwa_maybe_generate_icons_from_kirki(): bool {
	$source = (string) tailwindscore_mod( 'pwa_icon', '' );
	if ( '' === $source ) {
		return false;
	}

	$path = tailwindscore_pwa_resolve_image_path( $source );
	if ( '' === $path ) {
		return false;
	}

	$dir = tailwindscore_pwa_icons_output_dir();
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return false;
	}

	if ( ! is_writable( $dir ) ) {
		return false;
	}

	$ok_192  = tailwindscore_pwa_save_square_png( $path, $dir . '/icon-192.png', 192 );
	$ok_512  = tailwindscore_pwa_save_square_png( $path, $dir . '/icon-512.png', 512 );
	$ok_mask = tailwindscore_pwa_save_maskable_png( $path, $dir . '/icon-maskable-512.png', 512 );

	return $ok_192 && $ok_512 && $ok_mask;
}

/**
 * PWA 图标 URL：优先 uploads/ts-pwa（Kirki 生成），回退主题 assets/pwa 占位。
 *
 * @return array{192: string, 512: string, maskable: string, apple: string}
 */
function tailwindscore_pwa_icon_urls(): array {
	$files      = tailwindscore_pwa_icon_filenames();
	$upload     = tailwindscore_pwa_upload_paths();
	$theme_dir  = tailwindscore_pwa_theme_icons_dir();
	$theme_base = trailingslashit( get_template_directory_uri() ) . 'assets/pwa/';
	$urls       = array();

	foreach ( $files as $key => $file ) {
		$url   = $theme_base . $file;
		$mtime = null;

		if ( null !== $upload ) {
			$full = $upload['basedir'] . '/' . $file;
			if ( is_readable( $full ) ) {
				$url   = $upload['baseurl'] . '/' . $file;
				$mtime = filemtime( $full );
			}
		}

		if ( null === $mtime ) {
			$full = $theme_dir . '/' . $file;
			if ( is_readable( $full ) ) {
				$mtime = filemtime( $full );
			}
		}

		// 带 filemtime 防缓存，确保更换图示后浏览器拉到新文件。
		if ( is_int( $mtime ) && $mtime > 0 ) {
			$url = add_query_arg( 'v', (string) $mtime, $url );
		}

		$urls[ $key ] = $url;
	}

	return $urls;
}

/**
 * @return array<string, mixed>
 */
function tailwindscore_pwa_manifest_payload(): array {
	$defaults = array( 'color_primary' => '#181818', 'color_surface' => '#ffffff' );

	$primary = sanitize_hex_color( (string) tailwindscore_mod( 'color_primary', $defaults['color_primary'] ?? '#181818' ) );
	$surface = sanitize_hex_color( (string) tailwindscore_mod( 'color_surface', $defaults['color_surface'] ?? '#ffffff' ) );
	if ( ! $primary ) {
		$primary = '#181818';
	}
	if ( ! $surface ) {
		$surface = '#ffffff';
	}

	$name = wp_strip_all_tags( get_bloginfo( 'name', 'display' ) );
	if ( '' === $name ) {
		$name = 'TailwindScore';
	}

	// short_name：尽量不截断单词；过长时用站点名首段或固定短名。
	$short = $name;
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $short ) > 12 ) {
		$parts = preg_split( '/\s+/u', $short ) ?: array( $short );
		$short = (string) ( $parts[0] ?? 'Star' );
		if ( mb_strlen( $short ) > 12 ) {
			$short = mb_substr( $short, 0, 12 );
		}
	} elseif ( strlen( $short ) > 12 ) {
		$parts = preg_split( '/\s+/', $short ) ?: array( $short );
		$short = (string) ( $parts[0] ?? 'Star' );
		if ( strlen( $short ) > 12 ) {
			$short = substr( $short, 0, 12 );
		}
	}

	$icons       = tailwindscore_pwa_icon_urls();
	$scope_path  = tailwindscore_pwa_scope_path();
	$start_path  = $scope_path;

	return array(
		'id'               => $scope_path,
		'name'             => $name,
		'short_name'       => $short,
		'description'      => wp_strip_all_tags( get_bloginfo( 'description', 'display' ) ),
		// 使用 path（非绝对 URL），兼容性更好。
		'start_url'        => $start_path,
		'scope'            => $scope_path,
		'display'          => 'standalone',
		'orientation'      => 'any',
		'lang'             => 'en',
		'dir'              => 'ltr',
		'background_color' => $surface,
		'theme_color'      => $primary,
		'icons'            => array(
			array(
				'src'     => $icons['192'],
				'sizes'   => '192x192',
				'type'    => 'image/png',
				'purpose' => 'any',
			),
			array(
				'src'     => $icons['512'],
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'any',
			),
			array(
				'src'     => $icons['maskable'],
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'maskable',
			),
		),
	);
}

/**
 * @return array{cacheVersion: string, distPathPrefix: string, precache: list<string>, extensions: list<string>}
 */
function tailwindscore_pwa_sw_bootstrap_config(): array {
	return array(
		'cacheVersion'   => tailwindscore_pwa_theme_version(),
		'distPathPrefix' => tailwindscore_pwa_dist_path_prefix(),
		'precache'       => tailwindscore_pwa_precache_urls(),
		'extensions'     => array( '.js', '.css', '.woff2', '.woff', '.ttf' ),
	);
}

function tailwindscore_pwa_add_rewrite_rules(): void {
	add_rewrite_rule( '^ts-sw\.js$', 'index.php?tailwindscore_pwa_asset=sw', 'top' );
	add_rewrite_rule( '^ts-manifest\.webmanifest$', 'index.php?tailwindscore_pwa_asset=manifest', 'top' );
}
add_action( 'init', 'tailwindscore_pwa_add_rewrite_rules', 5 );

/**
 * @param string[] $vars Query vars.
 * @return string[]
 */
function tailwindscore_pwa_query_vars( array $vars ): array {
	$vars[] = 'tailwindscore_pwa_asset';

	return $vars;
}
add_filter( 'query_vars', 'tailwindscore_pwa_query_vars' );

/**
 * 主题启用或 rewrite 版本变更时 flush。
 */
function tailwindscore_pwa_maybe_flush_rewrites(): void {
	if ( get_option( 'tailwindscore_pwa_rewrite_version' ) === TAILWINDSCORE_PWA_REWRITE_VERSION ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'tailwindscore_pwa_rewrite_version', TAILWINDSCORE_PWA_REWRITE_VERSION, true );
}
add_action( 'init', 'tailwindscore_pwa_maybe_flush_rewrites', 99 );
add_action( 'after_switch_theme', 'tailwindscore_pwa_maybe_flush_rewrites' );

/**
 * 当前请求的 PWA 资产 slug（sw|manifest），无则空字串。
 */
function tailwindscore_pwa_request_asset(): string {
	$asset = get_query_var( 'tailwindscore_pwa_asset' );
	if ( ( ! is_string( $asset ) || '' === $asset ) && isset( $_GET['tailwindscore_pwa_asset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public asset endpoint.
		$asset = sanitize_key( wp_unslash( (string) $_GET['tailwindscore_pwa_asset'] ) );
	}

	return ( is_string( $asset ) && '' !== $asset ) ? $asset : '';
}

/**
 * 在 WP 标 404 之前拦截，避免 `/ts-sw.js` 带着 404 状态输出脚本。
 *
 * @param bool     $preempt Whether to short-circuit default 404 handling.
 * @param WP_Query $query   Query object.
 * @return bool
 */
function tailwindscore_pwa_pre_handle_404( $preempt, $query ) {
	unset( $query );

	return '' !== tailwindscore_pwa_request_asset() ? true : $preempt;
}
add_filter( 'pre_handle_404', 'tailwindscore_pwa_pre_handle_404', 10, 2 );

function tailwindscore_pwa_serve_asset(): void {
	$asset = tailwindscore_pwa_request_asset();
	if ( '' === $asset ) {
		return;
	}

	if ( 'manifest' === $asset ) {
		tailwindscore_pwa_output_manifest();
	}

	if ( 'sw' === $asset ) {
		tailwindscore_pwa_output_sw();
	}
}
add_action( 'template_redirect', 'tailwindscore_pwa_serve_asset', 0 );

/**
 * rewrite 端点无对应帖子时 WP 会标 is_404；必须显式 200，否则浏览器拒绝注册 SW。
 */
function tailwindscore_pwa_send_ok_status(): void {
	global $wp_query;

	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}

	status_header( 200 );
	if ( function_exists( 'http_response_code' ) ) {
		http_response_code( 200 );
	}
}

/**
 * 输出 Web App Manifest JSON 并终止请求。
 */
function tailwindscore_pwa_output_manifest(): void {
	tailwindscore_pwa_send_ok_status();
	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo wp_json_encode( tailwindscore_pwa_manifest_payload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

/**
 * 输出 Service Worker JS 并终止请求。
 */
function tailwindscore_pwa_output_sw(): void {
	$sw_path = tailwindscore_pwa_built_sw_path();
	if ( '' === $sw_path ) {
		status_header( 404 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'Service worker build missing. Run npm run build.';
		exit;
	}

	$body = (string) file_get_contents( $sw_path );
	$boot = 'self.__TAILWINDSCORE_PWA__ = ' . wp_json_encode( tailwindscore_pwa_sw_bootstrap_config(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";\n";

	tailwindscore_pwa_send_ok_status();
	nocache_headers();
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: ' . tailwindscore_pwa_scope_path() );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );
	header( 'X-Robots-Tag: noindex' );
	echo $boot . $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS asset.
	exit;
}

/**
 * REST：`GET /wp-json/tailwindscore/v1/pwa/manifest|sw`
 */
function tailwindscore_pwa_register_rest_routes(): void {
	register_rest_route(
		'sc/v1',
		'/pwa/manifest',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => static function (): WP_REST_Response {
				return new WP_REST_Response( array( 'ok' => true ), 200 );
			},
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'sc/v1',
		'/pwa/sw',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => static function (): WP_REST_Response {
				return new WP_REST_Response( array( 'ok' => true ), 200 );
			},
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'tailwindscore_pwa_register_rest_routes' );

/**
 * REST 默认会 JSON 包装；在此直接输出 manifest / SW 原文。
 *
 * @param bool             $served  Whether the request has already been served.
 * @param WP_HTTP_Response $result  Result to send to the client.
 * @param WP_REST_Request  $request Request used to generate the response.
 * @param WP_REST_Server   $server  Server instance.
 */
function tailwindscore_pwa_rest_pre_serve_request( $served, $result, $request, $server ) {
	unset( $result, $server );

	if ( ! $request instanceof WP_REST_Request ) {
		return $served;
	}

	$route = $request->get_route();

	if ( '/tailwindscore/v1/pwa/manifest' === $route ) {
		tailwindscore_pwa_output_manifest();
	}

	if ( '/tailwindscore/v1/pwa/sw' === $route ) {
		tailwindscore_pwa_output_sw();
	}

	return $served;
}
add_filter( 'rest_pre_serve_request', 'tailwindscore_pwa_rest_pre_serve_request', 10, 4 );

function tailwindscore_pwa_wp_head(): void {
	if ( ! tailwindscore_pwa_is_enabled() ) {
		return;
	}

	$payload = tailwindscore_pwa_manifest_payload();
	$theme   = isset( $payload['theme_color'] ) ? (string) $payload['theme_color'] : '#181818';
	$icons   = tailwindscore_pwa_icon_urls();

		printf(
			'<link rel="manifest" href="%s">' . "\n",
			esc_url( tailwindscore_pwa_manifest_url() )
		);
	printf(
		'<meta name="theme-color" content="%s">' . "\n",
		esc_attr( $theme )
	);
	printf(
		'<link rel="apple-touch-icon" href="%s">' . "\n",
		esc_url( $icons['apple'] )
	);
}
add_action( 'wp_head', 'tailwindscore_pwa_wp_head', 4 );

/**
 * Kirki：是否显示 pwa-install 安装提示条。
 */
function tailwindscore_pwa_install_prompt_is_enabled(): bool {
	return (bool) apply_filters( 'tailwindscore/pwa/install_prompt_enabled', (bool) tailwindscore_mod( 'pwa_install_prompt_enable', false ) );
}

/**
 * @param array<string, mixed> $parts Runtime config parts.
 * @return array<string, mixed>
 */
function tailwindscore_pwa_runtime_config_parts( array $parts ): array {
	$parts['tsPwa'] = array(
		'enabled'             => tailwindscore_pwa_is_enabled() && '' !== tailwindscore_pwa_built_sw_path(),
		'installPromptEnable' => tailwindscore_pwa_install_prompt_is_enabled(),
		'swUrl'               => tailwindscore_pwa_sw_url(),
		'scope'               => tailwindscore_pwa_scope_path(),
		'i18n'                => array(
			'installTitle'   => (string) tailwindscore_mod( 'pwa_install_title', __( 'Add to Home Screen', 'tailwindscore' ) ),
			'installBody'    => (string) tailwindscore_mod( 'pwa_install_body', __( 'Install for quick access like an app.', 'tailwindscore' ) ),
			'installAction'  => (string) tailwindscore_mod( 'pwa_install_action', __( 'Install', 'tailwindscore' ) ),
			'installDismiss' => (string) tailwindscore_mod( 'pwa_install_dismiss', __( 'Not now', 'tailwindscore' ) ),
			'iosHint'        => (string) tailwindscore_mod( 'pwa_install_ios_hint', __( 'In Safari, tap Share, then Add to Home Screen.', 'tailwindscore' ) ),
		),
	);

	return $parts;
}
add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_pwa_runtime_config_parts', 20 );
