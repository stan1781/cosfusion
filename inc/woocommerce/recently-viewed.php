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
 * 是否在单品 Related 下方展示。
 */
function tailwindscore_recently_viewed_show_on_product(): bool {
	return (bool) tailwindscore_mod( 'recently_viewed_show_on_product', true );
}

/**
 * 单品页 Recently viewed 展示参数（Kirki → 壳层）。
 *
 * @return array{card: string, carousel: string, columns_mobile: float, columns_desktop: float}
 */
function tailwindscore_recently_viewed_product_display_args(): array {
	$card_raw     = (string) tailwindscore_mod( 'recently_viewed_product_card', 'list' );
	$carousel_mod = tailwindscore_mod( 'recently_viewed_product_carousel', false );
	$carousel_raw = filter_var( $carousel_mod, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';

	$display = function_exists( 'tailwindscore_normalize_products_carousel_display' )
		? tailwindscore_normalize_products_carousel_display( $card_raw, $carousel_raw )
		: array(
			'card'     => 'list' === $card_raw ? 'list' : 'grid',
			'carousel' => 'list' === $card_raw ? '0' : $carousel_raw,
		);

	$columns_mobile = function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' )
		? tailwindscore_sanitize_products_carousel_slide_count(
			tailwindscore_mod( 'recently_viewed_product_columns_mobile', 1 )
		)
		: 1.0;
	$columns_desktop = function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' )
		? tailwindscore_sanitize_products_carousel_slide_count(
			tailwindscore_mod( 'recently_viewed_product_columns_desktop', 3 )
		)
		: 3.0;

	return array(
		'card'            => $display['card'],
		'carousel'        => $display['carousel'],
		'columns_mobile'  => $columns_mobile,
		'columns_desktop' => $columns_desktop,
	);
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
 * @param array{ids?: int[], exclude?: int, limit?: int, title?: string, card?: string, carousel?: string, columns_mobile?: float|int, columns_desktop?: float|int, columns_wide?: float|int|null, gap?: int} $args
 */
function tailwindscore_render_recently_viewed( array $args = array() ): string {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'tailwindscore_render_products_carousel_shell' ) ) {
		return '';
	}

	$ids     = isset( $args['ids'] ) && is_array( $args['ids'] ) ? $args['ids'] : tailwindscore_recently_viewed_ids_from_cookie();
	$exclude = isset( $args['exclude'] ) ? absint( $args['exclude'] ) : 0;
	$limit   = isset( $args['limit'] ) ? absint( $args['limit'] ) : 8;
	$title   = isset( $args['title'] ) ? (string) $args['title'] : tailwindscore_recently_viewed_title();

	$display = function_exists( 'tailwindscore_normalize_products_carousel_display' )
		? tailwindscore_normalize_products_carousel_display( $args['card'] ?? 'grid', $args['carousel'] ?? '1' )
		: array(
			'card'     => 'grid',
			'carousel' => '1',
		);

	// list 默认 1 / 3；grid 轮播默认 2.5 / 4。
	$default_mobile  = 'list' === $display['card'] ? 1.0 : 2.5;
	$default_desktop = 'list' === $display['card'] ? 3.0 : 4.0;
	$columns_mobile  = isset( $args['columns_mobile'] )
		? (float) $args['columns_mobile']
		: $default_mobile;
	$columns_desktop = isset( $args['columns_desktop'] )
		? (float) $args['columns_desktop']
		: $default_desktop;
	$columns_wide    = array_key_exists( 'columns_wide', $args ) ? $args['columns_wide'] : null;
	$gap             = isset( $args['gap'] ) ? absint( $args['gap'] ) : 16;

	$product_ids = tailwindscore_recently_viewed_filter_product_ids( $ids, $exclude, $limit );

	if ( array() === $product_ids ) {
		return '';
	}

	return tailwindscore_render_products_carousel_shell(
		array(
			'product_ids'        => $product_ids,
			'title'              => $title,
			'columns_mobile'     => $columns_mobile,
			'columns_desktop'    => $columns_desktop,
			'columns_wide'       => $columns_wide,
			'gap'                => $gap,
			'card'               => $display['card'],
			'carousel'           => $display['carousel'],
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
 *
 * @param array{exclude?: int, limit?: int, title?: string, card?: string, carousel?: string, columns_mobile?: float|int, columns_desktop?: float|int, columns_wide?: float|int|null, gap?: int} $args
 */
function tailwindscore_render_recently_viewed_host( array $args = array() ): string {
	$exclude = isset( $args['exclude'] ) ? absint( $args['exclude'] ) : 0;
	$limit   = isset( $args['limit'] ) ? absint( $args['limit'] ) : 8;

	$display = function_exists( 'tailwindscore_normalize_products_carousel_display' )
		? tailwindscore_normalize_products_carousel_display( $args['card'] ?? 'grid', $args['carousel'] ?? '1' )
		: array(
			'card'     => 'grid',
			'carousel' => '1',
		);

	$default_mobile  = 'list' === $display['card'] ? 1.0 : 2.5;
	$default_desktop = 'list' === $display['card'] ? 3.0 : 4.0;
	$columns_mobile  = isset( $args['columns_mobile'] )
		? (float) $args['columns_mobile']
		: $default_mobile;
	$columns_desktop = isset( $args['columns_desktop'] )
		? (float) $args['columns_desktop']
		: $default_desktop;
	$columns_wide    = array_key_exists( 'columns_wide', $args ) ? $args['columns_wide'] : null;
	$gap             = isset( $args['gap'] ) ? absint( $args['gap'] ) : 16;

	$render_args = array(
		'exclude'         => $exclude,
		'limit'           => $limit,
		'title'           => isset( $args['title'] ) ? (string) $args['title'] : tailwindscore_recently_viewed_title(),
		'card'            => $display['card'],
		'carousel'        => $display['carousel'],
		'columns_mobile'  => $columns_mobile,
		'columns_desktop' => $columns_desktop,
		'gap'             => $gap,
	);
	if ( null !== $columns_wide && '' !== $columns_wide ) {
		$render_args['columns_wide'] = $columns_wide;
	}

	$html = tailwindscore_render_recently_viewed( $render_args );

	ob_start();
	?>
	<div
		class="recently-viewed-host"
		data-ts-recently-viewed-host
		data-ts-recently-viewed-exclude="<?php echo esc_attr( (string) $exclude ); ?>"
		data-ts-recently-viewed-limit="<?php echo esc_attr( (string) $limit ); ?>"
		data-ts-recently-viewed-card="<?php echo esc_attr( $display['card'] ); ?>"
		data-ts-recently-viewed-carousel="<?php echo esc_attr( $display['carousel'] ); ?>"
		data-ts-recently-viewed-columns-mobile="<?php echo esc_attr( (string) $columns_mobile ); ?>"
		data-ts-recently-viewed-columns-desktop="<?php echo esc_attr( (string) $columns_desktop ); ?>"
		<?php if ( null !== $columns_wide && '' !== $columns_wide ) : ?>
			data-ts-recently-viewed-columns-wide="<?php echo esc_attr( (string) $columns_wide ); ?>"
		<?php endif; ?>
		data-ts-recently-viewed-gap="<?php echo esc_attr( (string) $gap ); ?>"
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
			'limit'           => '8',
			'title'           => '',
			'exclude'         => '0',
			'card'            => 'grid',
			'carousel'        => '1',
			'columns_mobile'  => '',
			'columns_desktop' => '',
			'columns_wide'    => '',
			'gap'             => '16',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_recently_viewed'
	);

	$title = sanitize_text_field( (string) $parsed['title'] );
	if ( '' === $title ) {
		$title = tailwindscore_recently_viewed_title();
	}

	$display = function_exists( 'tailwindscore_normalize_products_carousel_display' )
		? tailwindscore_normalize_products_carousel_display( $parsed['card'], $parsed['carousel'] )
		: array(
			'card'     => 'grid',
			'carousel' => '1',
		);

	$host_args = array(
		'limit'    => max( 1, min( 24, absint( $parsed['limit'] ) ) ),
		'title'    => $title,
		'exclude'  => absint( $parsed['exclude'] ),
		'card'     => $display['card'],
		'carousel' => $display['carousel'],
		'gap'      => function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
			? tailwindscore_sanitize_products_carousel_gap( $parsed['gap'] )
			: 16,
	);

	if ( '' !== trim( (string) $parsed['columns_mobile'] ) && function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' ) ) {
		$host_args['columns_mobile'] = tailwindscore_sanitize_products_carousel_slide_count( $parsed['columns_mobile'] );
	}
	if ( '' !== trim( (string) $parsed['columns_desktop'] ) && function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' ) ) {
		$host_args['columns_desktop'] = tailwindscore_sanitize_products_carousel_slide_count( $parsed['columns_desktop'] );
	}
	if ( '' !== trim( (string) $parsed['columns_wide'] ) && function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' ) ) {
		$host_args['columns_wide'] = tailwindscore_sanitize_products_carousel_slide_count( $parsed['columns_wide'] );
	}

	return tailwindscore_render_recently_viewed_host( $host_args );
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
 * 单品 Related 下方。
 */
function tailwindscore_recently_viewed_render_on_product(): void {
	if ( ! tailwindscore_recently_viewed_show_on_product() || ! is_product() ) {
		return;
	}

	$exclude = (int) get_queried_object_id();
	$display = tailwindscore_recently_viewed_product_display_args();

	echo tailwindscore_render_recently_viewed_host( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array_merge(
			array(
				'exclude' => $exclude,
			),
			$display
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
				'ids'      => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'exclude'  => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'limit'    => array(
					'type'              => 'integer',
					'default'           => 8,
					'sanitize_callback' => 'absint',
				),
				'card'     => array(
					'type'              => 'string',
					'default'           => 'grid',
					'sanitize_callback' => 'sanitize_key',
				),
				'carousel' => array(
					'type'              => 'string',
					'default'           => '1',
					'sanitize_callback' => 'sanitize_key',
				),
				'columns_mobile' => array(
					'type'              => 'number',
					'required'          => false,
					'sanitize_callback' => static function ( $value ) {
						return function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' )
							? tailwindscore_sanitize_products_carousel_slide_count( $value )
							: (float) $value;
					},
				),
				'columns_desktop' => array(
					'type'              => 'number',
					'required'          => false,
					'sanitize_callback' => static function ( $value ) {
						return function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' )
							? tailwindscore_sanitize_products_carousel_slide_count( $value )
							: (float) $value;
					},
				),
				'columns_wide' => array(
					'type'              => 'number',
					'required'          => false,
					'sanitize_callback' => static function ( $value ) {
						return function_exists( 'tailwindscore_sanitize_products_carousel_slide_count' )
							? tailwindscore_sanitize_products_carousel_slide_count( $value )
							: (float) $value;
					},
				),
				'gap' => array(
					'type'              => 'integer',
					'required'          => false,
					'sanitize_callback' => static function ( $value ) {
						return function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
							? tailwindscore_sanitize_products_carousel_gap( $value )
							: absint( $value );
					},
				),
			),
			'callback'            => static function ( WP_REST_Request $request ) {
				$raw = (string) $request->get_param( 'ids' );
				$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

				$display = function_exists( 'tailwindscore_normalize_products_carousel_display' )
					? tailwindscore_normalize_products_carousel_display(
						(string) $request->get_param( 'card' ),
						(string) $request->get_param( 'carousel' )
					)
					: array(
						'card'     => 'grid',
						'carousel' => '1',
					);

				$render_args = array(
					'ids'      => $ids,
					'exclude'  => (int) $request->get_param( 'exclude' ),
					'limit'    => (int) $request->get_param( 'limit' ),
					'card'     => $display['card'],
					'carousel' => $display['carousel'],
				);

				if ( null !== $request->get_param( 'columns_mobile' ) && '' !== $request->get_param( 'columns_mobile' ) ) {
					$render_args['columns_mobile'] = (float) $request->get_param( 'columns_mobile' );
				}
				if ( null !== $request->get_param( 'columns_desktop' ) && '' !== $request->get_param( 'columns_desktop' ) ) {
					$render_args['columns_desktop'] = (float) $request->get_param( 'columns_desktop' );
				}
				if ( null !== $request->get_param( 'columns_wide' ) && '' !== $request->get_param( 'columns_wide' ) ) {
					$render_args['columns_wide'] = (float) $request->get_param( 'columns_wide' );
				}
				if ( null !== $request->get_param( 'gap' ) && '' !== $request->get_param( 'gap' ) ) {
					$render_args['gap'] = (int) $request->get_param( 'gap' );
				}

				$html = tailwindscore_render_recently_viewed( $render_args );

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
			'label'    => esc_html__( '单品页显示（Related 下方）', 'tailwindscore' ),
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

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'custom',
			'settings' => 'recently_viewed_product_layout_heading',
			'section'  => 'tailwindscore_recently_viewed',
			'default'  => '<hr /><p style="margin:0;font-weight:600;">' . esc_html__( '单品页布局', 'tailwindscore' ) . '</p>',
			'priority' => 40,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'select',
			'settings'        => 'recently_viewed_product_card',
			'label'           => esc_html__( '卡片样式', 'tailwindscore' ),
			'description'     => esc_html__( '仅影响单品页挂载；短代码可自行覆盖。', 'tailwindscore' ),
			'section'         => 'tailwindscore_recently_viewed',
			'default'         => 'list',
			'choices'         => array(
				'grid' => esc_html__( 'Grid（竖卡）', 'tailwindscore' ),
				'list' => esc_html__( 'List（横卡）', 'tailwindscore' ),
			),
			'active_callback' => static function (): bool {
				return (bool) get_theme_mod( 'recently_viewed_show_on_product', true );
			},
			'priority'        => 50,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'toggle',
			'settings'        => 'recently_viewed_product_carousel',
			'label'           => esc_html__( '启用轮播', 'tailwindscore' ),
			'description'     => esc_html__( 'List 样式会强制关闭轮播。', 'tailwindscore' ),
			'section'         => 'tailwindscore_recently_viewed',
			'default'         => false,
			'active_callback' => static function (): bool {
				if ( ! (bool) get_theme_mod( 'recently_viewed_show_on_product', true ) ) {
					return false;
				}
				return 'grid' === (string) get_theme_mod( 'recently_viewed_product_card', 'list' );
			},
			'priority'        => 60,
		)
	);

	$column_choices = array(
		'1' => '1',
		'2' => '2',
		'3' => '3',
		'4' => '4',
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'select',
			'settings'        => 'recently_viewed_product_columns_mobile',
			'label'           => esc_html__( '手机列数', 'tailwindscore' ),
			'section'         => 'tailwindscore_recently_viewed',
			'default'         => '1',
			'choices'         => $column_choices,
			'active_callback' => static function (): bool {
				return (bool) get_theme_mod( 'recently_viewed_show_on_product', true );
			},
			'priority'        => 70,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'select',
			'settings'        => 'recently_viewed_product_columns_desktop',
			'label'           => esc_html__( '桌面列数（md+）', 'tailwindscore' ),
			'section'         => 'tailwindscore_recently_viewed',
			'default'         => '3',
			'choices'         => $column_choices,
			'active_callback' => static function (): bool {
				return (bool) get_theme_mod( 'recently_viewed_show_on_product', true );
			},
			'priority'        => 80,
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

add_action( 'woocommerce_after_single_product_summary', 'tailwindscore_recently_viewed_render_on_product', 25 );
