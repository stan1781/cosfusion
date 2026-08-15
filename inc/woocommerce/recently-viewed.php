<?php
declare(strict_types=1);

/**
 * Recently viewed：localStorage + cookie 同步；短代码 / Cart 空态 / PDP 挂载。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

const TAILWINDSCORE_RECENTLY_VIEWED_COOKIE = 'ts_recently_viewed';
const TAILWINDSCORE_RECENTLY_VIEWED_MAX    = 12;

/**
 * 是否在空购物车展示。
 */
function tailwindscore_recently_viewed_show_on_cart(): bool {
	return (bool) tailwindscore_mod( 'recently_viewed_show_on_cart', true );
}

/**
 * 是否在单品 Related 上方展示。
 */
function tailwindscore_recently_viewed_show_on_product(): bool {
	return (bool) tailwindscore_mod( 'recently_viewed_show_on_product', true );
}

/**
 * 区块标题。
 */
function tailwindscore_recently_viewed_title(): string {
	$title = trim( (string) tailwindscore_mod( 'recently_viewed_title', '' ) );

	if ( '' !== $title ) {
		return $title;
	}

	return __( 'Recently viewed', 'tailwindscore' );
}

/**
 * @return int[] 父商品 ID，最近在前。
 */
function tailwindscore_recently_viewed_ids_from_cookie(): array {
	if ( empty( $_COOKIE[ TAILWINDSCORE_RECENTLY_VIEWED_COOKIE ] ) ) {
		return array();
	}

	$raw = sanitize_text_field( wp_unslash( (string) $_COOKIE[ TAILWINDSCORE_RECENTLY_VIEWED_COOKIE ] ) );
	$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

	return array_values( array_unique( $ids ) );
}

/**
 * 过滤为可展示的父商品，保持传入顺序。
 *
 * @param int[] $ids
 * @param int   $exclude 排除的商品 ID（通常为当前 PDP）。
 * @param int   $limit
 * @return int[]
 */
function tailwindscore_recently_viewed_filter_product_ids( array $ids, int $exclude = 0, int $limit = 8 ): array {
	$limit = max( 1, min( 24, $limit ) );
	$out   = array();

	foreach ( $ids as $id ) {
		$id = absint( $id );

		if ( $id < 1 || $id === $exclude ) {
			continue;
		}

		$product = wc_get_product( $id );

		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			continue;
		}

		if ( $product->is_type( 'variation' ) ) {
			$parent = (int) $product->get_parent_id();
			if ( $parent < 1 || $parent === $exclude ) {
				continue;
			}
			$id      = $parent;
			$product = wc_get_product( $id );
			if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
				continue;
			}
		}

		if ( in_array( $id, $out, true ) ) {
			continue;
		}

		$out[] = $id;

		if ( count( $out ) >= $limit ) {
			break;
		}
	}

	return $out;
}

/**
 * 渲染 recently viewed 轮播 HTML；无商品时返回空字符串。
 *
 * @param array{ids?: int[], exclude?: int, limit?: int, title?: string} $args
 */
function tailwindscore_render_recently_viewed( array $args = array() ): string {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'tailwindscore_render_products_carousel_shell' ) ) {
		return '';
	}

	$ids     = isset( $args['ids'] ) && is_array( $args['ids'] ) ? $args['ids'] : tailwindscore_recently_viewed_ids_from_cookie();
	$exclude = isset( $args['exclude'] ) ? absint( $args['exclude'] ) : 0;
	$limit   = isset( $args['limit'] ) ? absint( $args['limit'] ) : 8;
	$title   = isset( $args['title'] ) ? (string) $args['title'] : tailwindscore_recently_viewed_title();

	$product_ids = tailwindscore_recently_viewed_filter_product_ids( $ids, $exclude, $limit );

	if ( array() === $product_ids ) {
		return '';
	}

	return tailwindscore_render_products_carousel_shell(
		array(
			'product_ids'        => $product_ids,
			'title'              => $title,
			'columns_mobile'     => 2.5,
			'columns_desktop'    => 4.0,
			'gap'                => 16,
			'section_class'      => 'product-carousel--recently-viewed',
			'section_id'         => 'ts-recently-viewed-title',
			'section_data_attrs' => array(
				'data-ts-recently-viewed' => '',
			),
		)
	);
}

/**
 * 空购物车 / 客户端水合用的挂载点（始终输出，由 TS 或 SSR 填充）。
 */
function tailwindscore_render_recently_viewed_host( array $args = array() ): string {
	$exclude = isset( $args['exclude'] ) ? absint( $args['exclude'] ) : 0;
	$limit   = isset( $args['limit'] ) ? absint( $args['limit'] ) : 8;
	$html    = tailwindscore_render_recently_viewed(
		array(
			'exclude' => $exclude,
			'limit'   => $limit,
			'title'   => isset( $args['title'] ) ? (string) $args['title'] : tailwindscore_recently_viewed_title(),
		)
	);

	ob_start();
	?>
	<div
		class="recently-viewed-host"
		data-ts-recently-viewed-host
		data-ts-recently-viewed-exclude="<?php echo esc_attr( (string) $exclude ); ?>"
		data-ts-recently-viewed-limit="<?php echo esc_attr( (string) $limit ); ?>"
		<?php echo '' === $html ? ' hidden' : ''; ?>
	><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- carousel HTML from theme renderer. ?></div>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string, string>|string $atts
 */
function tailwindscore_recently_viewed_shortcode( $atts ): string {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	$parsed = shortcode_atts(
		array(
			'limit'   => '8',
			'title'   => '',
			'exclude' => '0',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_recently_viewed'
	);

	$title = sanitize_text_field( (string) $parsed['title'] );
	if ( '' === $title ) {
		$title = tailwindscore_recently_viewed_title();
	}

	return tailwindscore_render_recently_viewed_host(
		array(
			'limit'   => max( 1, min( 24, absint( $parsed['limit'] ) ) ),
			'title'   => $title,
			'exclude' => absint( $parsed['exclude'] ),
		)
	);
}
add_shortcode( 'ts_recently_viewed', 'tailwindscore_recently_viewed_shortcode' );

/**
 * 购物车空态下方。
 */
function tailwindscore_recently_viewed_render_on_cart(): void {
	if ( ! tailwindscore_recently_viewed_show_on_cart() ) {
		return;
	}

	echo tailwindscore_render_recently_viewed_host(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * 单品 Related 上方。
 */
function tailwindscore_recently_viewed_render_on_product(): void {
	if ( ! tailwindscore_recently_viewed_show_on_product() || ! is_product() ) {
		return;
	}

	$exclude = (int) get_queried_object_id();

	echo tailwindscore_render_recently_viewed_host( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'exclude' => $exclude,
		)
	);
}

/**
 * REST：按 ID 返回轮播 HTML（供客户端水合）。
 */
function tailwindscore_recently_viewed_register_rest(): void {
	register_rest_route(
		'tailwindscore/v1',
		'/recently-viewed',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'args'                => array(
				'ids'     => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'exclude' => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'limit'   => array(
					'type'              => 'integer',
					'default'           => 8,
					'sanitize_callback' => 'absint',
				),
			),
			'callback'            => static function ( WP_REST_Request $request ) {
				$raw = (string) $request->get_param( 'ids' );
				$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

				$html = tailwindscore_render_recently_viewed(
					array(
						'ids'     => $ids,
						'exclude' => (int) $request->get_param( 'exclude' ),
						'limit'   => (int) $request->get_param( 'limit' ),
					)
				);

				return rest_ensure_response(
					array(
						'html' => $html,
					)
				);
			},
		)
	);
}
add_action( 'rest_api_init', 'tailwindscore_recently_viewed_register_rest' );

/**
 * Kirki：购物车 / 商品详情开关。
 *
 * @param string                $cfg
 * @param array<string, string> $targets
 */
function tailwindscore_recently_viewed_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_recently_viewed',
		array(
			'title'       => esc_html__( 'Recently viewed', 'tailwindscore' ),
			'description' => esc_html__( '浏览过的商品推荐（localStorage）。', 'tailwindscore' ),
			'panel'       => $targets['product'] ?? 'tailwindscore_product',
			'priority'    => 68,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'recently_viewed_show_on_cart',
			'label'    => esc_html__( '空购物车显示', 'tailwindscore' ),
			'section'  => 'tailwindscore_recently_viewed',
			'default'  => true,
			'priority' => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'recently_viewed_show_on_product',
			'label'    => esc_html__( '单品页显示（Related 上方）', 'tailwindscore' ),
			'section'  => 'tailwindscore_recently_viewed',
			'default'  => true,
			'priority' => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'recently_viewed_title',
			'label'    => esc_html__( '标题（空则用 Recently viewed）', 'tailwindscore' ),
			'section'  => 'tailwindscore_recently_viewed',
			'default'  => '',
			'priority' => 30,
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_recently_viewed_kirki_register', 10, 2 );

/**
 * 前台运行时配置。
 *
 * @param array<string, mixed> $parts
 * @return array<string, mixed>
 */
function tailwindscore_recently_viewed_runtime_config( array $parts ): array {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $parts;
	}

	$parts['recentlyViewed'] = array(
		'restUrl'       => esc_url_raw( rest_url( 'tailwindscore/v1/recently-viewed' ) ),
		'storageKey'    => 'ts_recently_viewed',
		'cookieName'    => TAILWINDSCORE_RECENTLY_VIEWED_COOKIE,
		'maxIds'        => TAILWINDSCORE_RECENTLY_VIEWED_MAX,
		'showOnCart'    => tailwindscore_recently_viewed_show_on_cart(),
		'showOnProduct' => tailwindscore_recently_viewed_show_on_product(),
		'productId'     => is_product() ? (int) get_queried_object_id() : 0,
	);

	return $parts;
}
add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_recently_viewed_runtime_config' );

add_action( 'woocommerce_after_single_product_summary', 'tailwindscore_recently_viewed_render_on_product', 18 );
