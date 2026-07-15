<?php
declare(strict_types=1);

/**
 * 首页 Kirki 设置与辅助函数。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 默认精选商品 shortcode（WooCommerce）。
 */
function tailwindscore_homepage_default_products_shortcode(): string {
	return '[products limit="8" columns="4" orderby="date"]';
}

/**
 * 默认首页商品区块标题。
 */
function tailwindscore_homepage_default_products_title(): string {
	return __( 'Featured products', 'tailwindscore' );
}

/**
 * 默认 Shop by Occasion 区块标题。
 */
function tailwindscore_homepage_default_occasion_title(): string {
	return __( 'Shop by occasion', 'tailwindscore' );
}

/**
 * 默认 Trending Collections 区块标题。
 */
function tailwindscore_homepage_default_trending_title(): string {
	return __( 'Trending collections', 'tailwindscore' );
}

/**
 * 默认 Shop by Occasion 网格项。
 *
 * @return array<int, array<string, string>>
 */
function tailwindscore_homepage_default_occasion_items(): array {
	return array(
		array(
			'image'      => '',
			'title'      => __( 'Party Club', 'tailwindscore' ),
			'subtitle'   => __( '200+ styles', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
		array(
			'image'      => '',
			'title'      => __( 'Prom', 'tailwindscore' ),
			'subtitle'   => __( '200+ styles', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
		array(
			'image'      => '',
			'title'      => __( 'Work', 'tailwindscore' ),
			'subtitle'   => __( '250+ styles', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
		array(
			'image'      => '',
			'title'      => __( 'Homecoming', 'tailwindscore' ),
			'subtitle'   => __( '150+ styles', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
	);
}

/**
 * 默认 Trending Collections 网格项。
 *
 * @return array<int, array<string, string>>
 */
function tailwindscore_homepage_default_trending_items(): array {
	return array(
		array(
			'image'      => '',
			'title'      => __( 'Maxi Dresses', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
		array(
			'image'      => '',
			'title'      => __( 'Midi Dresses', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
		array(
			'image'      => '',
			'title'      => __( 'Bodycon Dresses', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
		array(
			'image'      => '',
			'title'      => __( 'Summer Dresses', 'tailwindscore' ),
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		),
	);
}

/**
 * 允许的 hero CTA 按钮样式。
 *
 * @return list<string>
 */
function tailwindscore_homepage_hero_link_styles(): array {
	return array( 'solid', 'outline' );
}

/**
 * 允许的 hero 文字颜色模式。
 *
 * @return list<string>
 */
function tailwindscore_homepage_hero_text_colors(): array {
	return array( 'light', 'dark' );
}

/**
 * 消毒 hero slide CTA 按钮样式。
 */
function tailwindscore_homepage_sanitize_hero_link_style( $value ): string {
	$style = is_string( $value ) ? sanitize_key( $value ) : 'solid';

	return in_array( $style, tailwindscore_homepage_hero_link_styles(), true ) ? $style : 'solid';
}

/**
 * 消毒 hero slide 文字颜色模式。
 */
function tailwindscore_homepage_sanitize_hero_text_color( $value ): string {
	$color = is_string( $value ) ? sanitize_key( $value ) : 'light';

	return in_array( $color, tailwindscore_homepage_hero_text_colors(), true ) ? $color : 'light';
}

/**
 * 将 hero 图片值（attachment ID 或旧版 URL）解析为 attachment ID。
 *
 * @param mixed $image 来自 theme_mod 的原始值。
 */
function tailwindscore_homepage_hero_resolve_attachment_id( $image ): int {
	if ( is_numeric( $image ) ) {
		$attachment_id = absint( $image );

		return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
	}

	$url = is_string( $image ) ? trim( $image ) : '';

	if ( '' === $url ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( $url );

	return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
}

/**
 * 响应式 hero slide 图片 markup（媒体库图片时使用 srcset）。
 *
 * @param array<string, mixed> $slide       规范化 slide 数据。
 * @param int                  $slide_index 从 0 开始的 slide 索引。
 */
function tailwindscore_homepage_hero_slide_image_markup( array $slide, int $slide_index, string $alt ): string {
	$attrs = array(
		'class'    => 'home-hero__image',
		'alt'      => $alt,
		'decoding' => 'async',
		'loading'  => 0 === $slide_index ? 'eager' : 'lazy',
		'sizes'    => '100vw',
	);

	if ( 0 === $slide_index ) {
		$attrs['fetchpriority'] = 'high';
	}

	$image_id = isset( $slide['image_id'] ) ? absint( $slide['image_id'] ) : 0;

	if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
		return (string) wp_get_attachment_image( $image_id, 'full', false, $attrs );
	}

	$src = isset( $slide['image'] ) ? trim( (string) $slide['image'] ) : '';

	if ( '' === $src ) {
		return '';
	}

	$html = sprintf( '<img src="%s"', esc_url( $src ) );

	foreach ( $attrs as $name => $value ) {
		$html .= sprintf( ' %s="%s"', esc_attr( (string) $name ), esc_attr( (string) $value ) );
	}

	$html .= '>';

	return $html;
}

/**
 * 来自 theme_mod 的规范化 hero slides（跳过无图片行）。
 *
 * @return array<int, array{image: string, image_id: int, title: string, subtitle: string, description: string, link_url: string, link_label: string, link_style: string, text_color: string}>
 */
function tailwindscore_homepage_hero_slides(): array {
	$slides = tailwindscore_mod( 'homepage_hero_slides', array() );

	if ( ! is_array( $slides ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $slides as $slide ) {
		if ( ! is_array( $slide ) ) {
			continue;
		}

		$image_raw = $slide['image'] ?? '';
		$image_id  = tailwindscore_homepage_hero_resolve_attachment_id( $image_raw );
		$image_url = '';

		if ( $image_id > 0 ) {
			$image_url = (string) wp_get_attachment_image_url( $image_id, 'full' );
		}

		if ( '' === $image_url && is_string( $image_raw ) ) {
			$image_url = trim( $image_raw );
		}

		if ( '' === $image_url ) {
			continue;
		}

		$normalized[] = array(
			'image'       => $image_url,
			'image_id'    => $image_id,
			'title'       => isset( $slide['title'] ) ? trim( (string) $slide['title'] ) : '',
			'subtitle'    => isset( $slide['subtitle'] ) ? trim( (string) $slide['subtitle'] ) : '',
			'description' => isset( $slide['description'] ) ? trim( (string) $slide['description'] ) : '',
			'link_url'    => isset( $slide['link_url'] ) ? trim( (string) $slide['link_url'] ) : '',
			'link_label'  => isset( $slide['link_label'] ) ? trim( (string) $slide['link_label'] ) : '',
			'link_style'  => tailwindscore_homepage_sanitize_hero_link_style( $slide['link_style'] ?? 'solid' ),
			'text_color'  => tailwindscore_homepage_sanitize_hero_text_color( $slide['text_color'] ?? 'light' ),
		);
	}

	return $normalized;
}

/**
 * 首页网格卡片的 WooCommerce 占位图 URL。
 */
function tailwindscore_homepage_grid_placeholder_image_url(): string {
	if ( function_exists( 'wc_placeholder_img_src' ) ) {
		return trim( (string) wc_placeholder_img_src( 'woocommerce_single' ) );
	}

	if ( defined( 'WC_PLUGIN_FILE' ) ) {
		return (string) plugins_url( 'assets/images/placeholder.webp', WC_PLUGIN_FILE );
	}

	return '';
}

/**
 * 从 theme_mod 规范化首页网格卡片行。
 *
 * 无自定义图片的行在可用时使用 WooCommerce 占位图。
 *
 * @param mixed                $items   原始 repeater 值。
 * @param array<string, mixed> $schema  从每行复制的字段 key。
 * @return array<int, array<string, mixed>>
 */
function tailwindscore_homepage_normalize_grid_items( $items, array $schema ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$placeholder_url = tailwindscore_homepage_grid_placeholder_image_url();
	$normalized      = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$image_raw       = $item['image'] ?? '';
		$image_id        = tailwindscore_homepage_hero_resolve_attachment_id( $image_raw );
		$image_url       = '';
		$is_placeholder  = false;

		if ( $image_id > 0 ) {
			$image_url = (string) wp_get_attachment_image_url( $image_id, 'full' );
		}

		if ( '' === $image_url && is_string( $image_raw ) ) {
			$image_url = trim( $image_raw );
		}

		if ( '' === $image_url && '' !== $placeholder_url ) {
			$image_url      = $placeholder_url;
			$is_placeholder = true;
		}

		if ( '' === $image_url ) {
			continue;
		}

		$row = array(
			'image'          => $image_url,
			'image_id'       => $image_id,
			'is_placeholder' => $is_placeholder,
		);

		foreach ( $schema as $key => $default ) {
			$row[ $key ] = isset( $item[ $key ] ) ? trim( (string) $item[ $key ] ) : $default;
		}

		$normalized[] = $row;
	}

	return $normalized;
}

/**
 * 响应式网格卡片图片 markup。
 *
 * @param array<string, mixed> $item      规范化卡片数据。
 * @param string               $class     图片 class 属性。
 * @param string               $alt       Alt 文本。
 * @param string               $sizes     srcset 图片的 sizes 属性。
 */
function tailwindscore_homepage_grid_card_image_markup( array $item, string $class, string $alt, string $sizes = '25vw' ): string {
	$classes = $class;

	if ( ! empty( $item['is_placeholder'] ) ) {
		$classes .= ' woocommerce-placeholder';
	}

	$attrs = array(
		'class'    => trim( $classes ),
		'alt'      => $alt,
		'decoding' => 'async',
		'loading'  => 'lazy',
		'sizes'    => $sizes,
	);

	$image_id = isset( $item['image_id'] ) ? absint( $item['image_id'] ) : 0;

	if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
		return (string) wp_get_attachment_image( $image_id, 'full', false, $attrs );
	}

	$src = isset( $item['image'] ) ? trim( (string) $item['image'] ) : '';

	if ( '' === $src ) {
		return '';
	}

	$html = sprintf( '<img src="%s"', esc_url( $src ) );

	foreach ( $attrs as $name => $value ) {
		$html .= sprintf( ' %s="%s"', esc_attr( (string) $name ), esc_attr( (string) $value ) );
	}

	$html .= '>';

	return $html;
}

/**
 * 按项数为首页卡片网格输出内联 CSS 自定义属性。
 */
function tailwindscore_homepage_grid_style_attr( int $count ): string {
	$count = max( 1, $count );

	return sprintf( '--ts-home-grid-columns:%d', $count );
}

/**
 * 首页网格卡片图片的响应式 sizes 属性。
 */
function tailwindscore_homepage_grid_image_sizes( int $count ): string {
	$count         = max( 1, $count );
	$desktop_width = (int) ceil( 100 / $count );

	return sprintf( '(max-width: 767px) 50vw, %dvw', $desktop_width );
}

/**
 * 将 Trending Collection 标题拆为两行显示（最后一词在第二行）。
 *
 * @return array{line1: string, line2: string}
 */
function tailwindscore_homepage_split_trending_title( string $title ): array {
	$title = trim( $title );

	if ( '' === $title ) {
		return array(
			'line1' => '',
			'line2' => '',
		);
	}

	$position = strrpos( $title, ' ' );

	if ( false === $position ) {
		return array(
			'line1' => $title,
			'line2' => '',
		);
	}

	return array(
		'line1' => substr( $title, 0, $position ),
		'line2' => substr( $title, $position + 1 ),
	);
}

/**
 * Shop by Occasion 区块标题。
 */
function tailwindscore_homepage_occasion_title(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_occasion_title',
			tailwindscore_homepage_default_occasion_title()
		)
	);
}

/**
 * Shop by Occasion 网格项。
 *
 * @return array<int, array{image: string, image_id: int, title: string, subtitle: string, link_url: string, link_label: string}>
 */
function tailwindscore_homepage_occasion_items(): array {
	return tailwindscore_homepage_normalize_grid_items(
		tailwindscore_mod( 'homepage_occasion_items', tailwindscore_homepage_default_occasion_items() ),
		array(
			'title'      => '',
			'subtitle'   => '',
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		)
	);
}

/**
 * 是否渲染 Shop by Occasion 区块。
 */
function tailwindscore_homepage_should_render_occasion(): bool {
	return ! empty( tailwindscore_homepage_occasion_items() );
}

/**
 * Trending Collections 区块标题。
 */
function tailwindscore_homepage_trending_title(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_trending_title',
			tailwindscore_homepage_default_trending_title()
		)
	);
}

/**
 * Trending Collections 网格项。
 *
 * @return array<int, array{image: string, image_id: int, title: string, link_url: string, link_label: string}>
 */
function tailwindscore_homepage_trending_items(): array {
	return tailwindscore_homepage_normalize_grid_items(
		tailwindscore_mod( 'homepage_trending_items', tailwindscore_homepage_default_trending_items() ),
		array(
			'title'      => '',
			'link_url'   => '',
			'link_label' => __( 'Shop now', 'tailwindscore' ),
		)
	);
}

/**
 * 是否渲染 Trending Collections 区块。
 */
function tailwindscore_homepage_should_render_trending(): bool {
	return ! empty( tailwindscore_homepage_trending_items() );
}

/**
 * 默认 Trust Bar 项。
 *
 * @return array<int, array{icon: string, title: string, description: string}>
 */
function tailwindscore_homepage_default_trust_items(): array {
	return array(
		array(
			'icon'        => 'truck',
			'title'       => __( 'Free shipping', 'tailwindscore' ),
			'description' => __( 'On orders over $39', 'tailwindscore' ),
		),
		array(
			'icon'        => 'ruler',
			'title'       => __( 'Custom sizing', 'tailwindscore' ),
			'description' => __( 'Made to fit you perfectly', 'tailwindscore' ),
		),
		array(
			'icon'        => 'lock',
			'title'       => __( 'Secure checkout', 'tailwindscore' ),
			'description' => __( '100% secure & trusted', 'tailwindscore' ),
		),
		array(
			'icon'        => 'box-return',
			'title'       => __( 'Easy returns', 'tailwindscore' ),
			'description' => __( 'Hassle-free returns', 'tailwindscore' ),
		),
	);
}

/**
 * Customizer 与规范化用的 Trust Bar 图标选项。
 *
 * @return array<string, string>
 */
function tailwindscore_homepage_trust_icon_choices(): array {
	return array(
		'truck'      => esc_html__( 'Shipping truck', 'tailwindscore' ),
		'ruler'      => esc_html__( 'Measuring tape', 'tailwindscore' ),
		'lock'       => esc_html__( 'Padlock', 'tailwindscore' ),
		'box-return' => esc_html__( 'Return box', 'tailwindscore' ),
	);
}

/**
 * Customizer 中是否启用 Trust Bar 区块。
 */
function tailwindscore_homepage_trust_enabled(): bool {
	return (bool) tailwindscore_mod( 'homepage_trust_enable', true );
}

/**
 * 从 theme_mod 规范化 Trust Bar repeater 行。
 *
 * @param mixed $items 原始 repeater 值。
 * @return array<int, array{icon: string, title: string, description: string}>
 */
function tailwindscore_homepage_normalize_trust_items( $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$icon_choices = tailwindscore_homepage_trust_icon_choices();
	$normalized   = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$title = isset( $item['title'] ) ? trim( sanitize_text_field( (string) $item['title'] ) ) : '';

		if ( '' === $title ) {
			continue;
		}

		$icon = isset( $item['icon'] ) ? sanitize_key( (string) $item['icon'] ) : 'truck';

		if ( ! array_key_exists( $icon, $icon_choices ) ) {
			$icon = 'truck';
		}

		$normalized[] = array(
			'icon'        => $icon,
			'title'       => $title,
			'description' => isset( $item['description'] ) ? trim( sanitize_text_field( (string) $item['description'] ) ) : '',
		);
	}

	return $normalized;
}

/**
 * 来自 Customizer 的 Trust Bar 项。
 *
 * @return array<int, array{icon: string, title: string, description: string}>
 */
function tailwindscore_homepage_trust_items(): array {
	return tailwindscore_homepage_normalize_trust_items(
		tailwindscore_mod( 'homepage_trust_items', tailwindscore_homepage_default_trust_items() )
	);
}

/**
 * 是否渲染 Trust Bar 区块。
 */
function tailwindscore_homepage_should_render_trust(): bool {
	if ( ! tailwindscore_homepage_trust_enabled() ) {
		return false;
	}

	return ! empty( tailwindscore_homepage_trust_items() );
}

/**
 * 是否启用首页商品区块。
 */
function tailwindscore_homepage_products_enabled(): bool {
	return (bool) tailwindscore_mod( 'homepage_products_enable', true );
}

/**
 * 首页商品区块标题。
 */
function tailwindscore_homepage_products_title(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_products_title',
			tailwindscore_homepage_default_products_title()
		)
	);
}

/**
 * 首页商品区块的 shortcode 字符串。
 */
function tailwindscore_homepage_products_shortcode(): string {
	return trim(
		(string) tailwindscore_mod(
			'homepage_products_shortcode',
			tailwindscore_homepage_default_products_shortcode()
		)
	);
}

/**
 * 是否渲染首页商品区块。
 */
function tailwindscore_homepage_should_render_products(): bool {
	if ( ! tailwindscore_homepage_products_enabled() ) {
		return false;
	}

	return '' !== tailwindscore_homepage_products_shortcode();
}

/**
 * 将 slide 数值在 1–6 范围内对齐到 0.5 步长。
 */
function tailwindscore_homepage_sanitize_slide_count( $value ): float {
	$number = is_numeric( $value ) ? (float) $value : 1.0;
	$number = max( 1.0, min( 6.0, $number ) );

	return round( $number * 2.0 ) / 2.0;
}

/**
 * 商品轮播间距（像素，0–48）。
 */
function tailwindscore_homepage_sanitize_slide_gap( $value ): int {
	return max( 0, min( 48, absint( $value ) ) );
}

/**
 * hero 横幅是否自动播放（仅 2+ slides 时生效）。
 */
function tailwindscore_homepage_hero_autoplay(): bool {
	return (bool) tailwindscore_mod( 'homepage_hero_autoplay', false );
}

/**
 * 首页商品轮播默认响应式可见 slide 数。
 *
 * @return array{mobile: float, tablet: float, desktop: float}
 */
function tailwindscore_homepage_default_products_slide_counts(): array {
	return array(
		'mobile'  => 2.5,
		'tablet'  => 4.0,
		'desktop' => 6.0,
	);
}

/**
 * 读取 Kirki 响应式 slide 数（mobile / tablet / desktop），含旧版按断点 mod。
 *
 * @return array{mobile: float, tablet: float, desktop: float}
 */
function tailwindscore_homepage_products_slide_counts(): array {
	static $counts = null;

	if ( null !== $counts ) {
		return $counts;
	}

	$defaults = tailwindscore_homepage_default_products_slide_counts();
	$stored   = tailwindscore_mod( 'homepage_products_slides', null );

	if ( is_array( $stored ) && ( isset( $stored['mobile'] ) || isset( $stored['tablet'] ) || isset( $stored['desktop'] ) ) ) {
		$counts = array(
			'mobile'  => tailwindscore_homepage_sanitize_slide_count( $stored['mobile'] ?? $defaults['mobile'] ),
			'tablet'  => tailwindscore_homepage_sanitize_slide_count( $stored['tablet'] ?? $defaults['tablet'] ),
			'desktop' => tailwindscore_homepage_sanitize_slide_count( $stored['desktop'] ?? $defaults['desktop'] ),
		);

		return $counts;
	}

	$tablet_fallback = tailwindscore_mod( 'homepage_products_slides_desktop', $defaults['tablet'] );

	$counts = array(
		'mobile'  => tailwindscore_homepage_sanitize_slide_count(
			tailwindscore_mod( 'homepage_products_slides_mobile', $defaults['mobile'] )
		),
		'tablet'  => tailwindscore_homepage_sanitize_slide_count( $tablet_fallback ),
		'desktop' => tailwindscore_homepage_sanitize_slide_count(
			tailwindscore_mod( 'homepage_products_slides_wide', $tablet_fallback )
		),
	);

	return $counts;
}

/**
 * md 断点以下视口可见商品 slide 数（支持 2.5 peek）。
 */
function tailwindscore_homepage_products_slides_mobile(): float {
	return tailwindscore_homepage_products_slide_counts()['mobile'];
}

/**
 * md 至 xl 以下（768px–1279px）可见商品 slide 数。
 */
function tailwindscore_homepage_products_slides_desktop(): float {
	return tailwindscore_homepage_products_slide_counts()['tablet'];
}

/**
 * xl 及以上（1280px+）可见商品 slide 数。
 */
function tailwindscore_homepage_products_slides_wide(): float {
	return tailwindscore_homepage_products_slide_counts()['desktop'];
}

/**
 * 商品 slide 间距（像素）。
 */
function tailwindscore_homepage_products_slide_gap(): int {
	return tailwindscore_homepage_sanitize_slide_gap(
		tailwindscore_mod( 'homepage_products_slide_gap', 24 )
	);
}

/**
 * 首页 Swiper 运行时配置。
 *
 * @return array<string, mixed>
 */
function tailwindscore_homepage_swiper_config(): array {
	return array(
		'hero'     => array(
			'autoplay'      => tailwindscore_homepage_hero_autoplay(),
			'autoplayDelay' => 5000,
		),
		'products' => array(
			'slidesMobile'   => tailwindscore_homepage_products_slides_mobile(),
			'slidesDesktop'  => tailwindscore_homepage_products_slides_desktop(),
			'slidesWide'     => tailwindscore_homepage_products_slides_wide(),
			'spaceBetween'   => tailwindscore_homepage_products_slide_gap(),
			'breakpoint'     => 768,
			'breakpointWide' => 1280,
		),
	);
}

/**
 * @param array<string, mixed> $parts 运行时配置片段。
 * @return array<string, mixed>
 */
function tailwindscore_homepage_runtime_config_parts( array $parts ): array {
	if ( ! is_front_page() ) {
		return $parts;
	}

	$parts['home'] = tailwindscore_homepage_swiper_config();

	return $parts;
}
add_filter( 'tailwindscore_runtime_config_parts', 'tailwindscore_homepage_runtime_config_parts' );

/**
 * @param string               $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_homepage_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_homepage_display',
		array(
			'title'       => esc_html__( 'Homepage display', 'tailwindscore' ),
			'description' => esc_html__( 'Hero banner slides, category grids, and product blocks for the front page.', 'tailwindscore' ),
			'priority'    => 125,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'repeater',
			'settings'    => 'homepage_hero_slides',
			'label'       => esc_html__( 'Hero slides', 'tailwindscore' ),
			'description' => esc_html__( 'Add one or more banner slides. Rows without an image are ignored on the front page.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 10,
			'row_label'   => array(
				'type'  => 'field',
				'value' => esc_html__( 'Slide', 'tailwindscore' ),
				'field' => 'title',
			),
			'default'     => array(),
			'fields'      => array(
				'image'      => array(
					'type'    => 'image',
					'label'   => esc_html__( 'Image', 'tailwindscore' ),
					'default' => '',
					'choices' => array(
						'save_as' => 'id',
					),
				),
				'subtitle'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Eyebrow', 'tailwindscore' ),
					'default' => '',
				),
				'title'      => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Headline', 'tailwindscore' ),
					'default' => '',
				),
				'description' => array(
					'type'    => 'textarea',
					'label'   => esc_html__( 'Description', 'tailwindscore' ),
					'default' => '',
				),
				'text_color' => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Text color', 'tailwindscore' ),
					'default' => 'light',
					'choices' => array(
						'light' => esc_html__( 'Light', 'tailwindscore' ),
						'dark'  => esc_html__( 'Dark', 'tailwindscore' ),
					),
				),
				'link_url'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Link URL', 'tailwindscore' ),
					'default' => '',
				),
				'link_label' => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Button label', 'tailwindscore' ),
					'default' => '',
				),
				'link_style' => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Button style', 'tailwindscore' ),
					'default' => 'solid',
					'choices' => array(
						'solid'   => esc_html__( 'Solid', 'tailwindscore' ),
						'outline' => esc_html__( 'Outline', 'tailwindscore' ),
					),
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'homepage_hero_autoplay',
			'label'       => esc_html__( 'Hero autoplay', 'tailwindscore' ),
			'description' => esc_html__( 'Rotate hero slides automatically when there are two or more banners.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 15,
			'default'     => false,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'homepage_occasion_title',
			'label'    => esc_html__( 'Shop by Occasion title', 'tailwindscore' ),
			'section'  => 'tailwindscore_homepage_display',
			'priority' => 16,
			'default'  => tailwindscore_homepage_default_occasion_title(),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'repeater',
			'settings'    => 'homepage_occasion_items',
			'label'       => esc_html__( 'Shop by Occasion items', 'tailwindscore' ),
			'description' => esc_html__( 'Add cards below the hero. Cards without a custom image use the WooCommerce placeholder.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 17,
			'row_label'   => array(
				'type'  => 'field',
				'value' => esc_html__( 'Occasion', 'tailwindscore' ),
				'field' => 'title',
			),
			'default'     => tailwindscore_homepage_default_occasion_items(),
			'fields'      => array(
				'image'      => array(
					'type'    => 'image',
					'label'   => esc_html__( 'Image', 'tailwindscore' ),
					'default' => '',
					'choices' => array(
						'save_as' => 'id',
					),
				),
				'title'      => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Title', 'tailwindscore' ),
					'default' => '',
				),
				'subtitle'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Subtitle', 'tailwindscore' ),
					'default' => '',
				),
				'link_url'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Link URL', 'tailwindscore' ),
					'default' => '',
				),
				'link_label' => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Button label', 'tailwindscore' ),
					'default' => __( 'Shop now', 'tailwindscore' ),
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'text',
			'settings' => 'homepage_trending_title',
			'label'    => esc_html__( 'Trending Collections title', 'tailwindscore' ),
			'section'  => 'tailwindscore_homepage_display',
			'priority' => 18,
			'default'  => tailwindscore_homepage_default_trending_title(),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'repeater',
			'settings'    => 'homepage_trending_items',
			'label'       => esc_html__( 'Trending Collections items', 'tailwindscore' ),
			'description' => esc_html__( 'Add cards below Shop by Occasion. Cards without a custom image use the WooCommerce placeholder.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 19,
			'row_label'   => array(
				'type'  => 'field',
				'value' => esc_html__( 'Collection', 'tailwindscore' ),
				'field' => 'title',
			),
			'default'     => tailwindscore_homepage_default_trending_items(),
			'fields'      => array(
				'image'      => array(
					'type'    => 'image',
					'label'   => esc_html__( 'Image', 'tailwindscore' ),
					'default' => '',
					'choices' => array(
						'save_as' => 'id',
					),
				),
				'title'      => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Title', 'tailwindscore' ),
					'default' => '',
				),
				'link_url'   => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Link URL', 'tailwindscore' ),
					'default' => '',
				),
				'link_label' => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Link label', 'tailwindscore' ),
					'default' => __( 'Shop now', 'tailwindscore' ),
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'homepage_products_enable',
			'label'    => esc_html__( 'Show products block', 'tailwindscore' ),
			'section'  => 'tailwindscore_homepage_display',
			'priority' => 20,
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'homepage_products_title',
			'label'           => esc_html__( 'Products block title', 'tailwindscore' ),
			'section'         => 'tailwindscore_homepage_display',
			'priority'        => 30,
			'default'         => tailwindscore_homepage_default_products_title(),
			'active_callback' => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'homepage_products_shortcode',
			'label'           => esc_html__( 'Products shortcode', 'tailwindscore' ),
			'description'     => esc_html__( 'WooCommerce product shortcode, e.g. [products limit="8" columns="4"].', 'tailwindscore' ),
			'section'         => 'tailwindscore_homepage_display',
			'priority'        => 40,
			'default'         => tailwindscore_homepage_default_products_shortcode(),
			'active_callback' => array(
				array(
					'setting'  => 'homepage_products_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	$products_active = array(
		array(
			'setting'  => 'homepage_products_enable',
			'operator' => '==',
			'value'    => true,
		),
	);

	$responsive_slides = array(
		'type'              => 'select',
		'section'           => 'tailwindscore_homepage_display',
		'choices'           => tailwindscore_kirki_slide_count_choices(),
		'responsive'        => true,
		'sanitize_callback' => 'tailwindscore_kirki_sanitize_slide_count_choice',
		'active_callback'   => $products_active,
	);

	Kirki::add_field(
		$cfg,
		array_merge(
			array(
				'settings'    => 'homepage_products_slides',
				'label'       => esc_html__( 'Products visible', 'tailwindscore' ),
				'description' => esc_html__( 'Slides per view — mobile / tablet / desktop. Use 2.5 on mobile to peek the next card.', 'tailwindscore' ),
				'default'     => array(
					'mobile'  => '2.5',
					'tablet'  => '4',
					'desktop' => '6',
				),
				'priority'    => 50,
			),
			$responsive_slides
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'slider',
			'settings'          => 'homepage_products_slide_gap',
			'label'             => esc_html__( 'Product slide gap', 'tailwindscore' ),
			'description'       => esc_html__( 'Space between product cards in pixels.', 'tailwindscore' ),
			'section'           => 'tailwindscore_homepage_display',
			'priority'          => 51,
			'default'           => 24,
			'choices'           => array(
				'min'  => 0,
				'max'  => 48,
				'step' => 4,
			),
			'sanitize_callback' => 'tailwindscore_homepage_sanitize_slide_gap',
			'active_callback'   => $products_active,
		)
	);

	$shop_by_color_active = array(
		array(
			'setting'  => 'homepage_shop_by_color_enable',
			'operator' => '==',
			'value'    => true,
		),
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'homepage_shop_by_color_enable',
			'label'       => esc_html__( 'Show Shop by Color block', 'tailwindscore' ),
			'description' => esc_html__( 'Display color swatches from the WooCommerce color attribute below the products block.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 55,
			'default'     => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'homepage_shop_by_color_title',
			'label'           => esc_html__( 'Shop by Color title', 'tailwindscore' ),
			'section'         => 'tailwindscore_homepage_display',
			'priority'        => 56,
			'default'         => tailwindscore_homepage_default_shop_by_color_title(),
			'active_callback' => $shop_by_color_active,
		)
	);

	$trust_active = array(
		array(
			'setting'  => 'homepage_trust_enable',
			'operator' => '==',
			'value'    => true,
		),
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'toggle',
			'settings'    => 'homepage_trust_enable',
			'label'       => esc_html__( 'Show trust bar', 'tailwindscore' ),
			'description' => esc_html__( 'Display value proposition items below Shop by Color.', 'tailwindscore' ),
			'section'     => 'tailwindscore_homepage_display',
			'priority'    => 57,
			'default'     => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'repeater',
			'settings'        => 'homepage_trust_items',
			'label'           => esc_html__( 'Trust bar items', 'tailwindscore' ),
			'description'     => esc_html__( 'Add value proposition items. Rows without a title are ignored on the front page.', 'tailwindscore' ),
			'section'         => 'tailwindscore_homepage_display',
			'priority'        => 58,
			'row_label'       => array(
				'type'  => 'field',
				'value' => esc_html__( 'Item', 'tailwindscore' ),
				'field' => 'title',
			),
			'default'         => tailwindscore_homepage_default_trust_items(),
			'active_callback' => $trust_active,
			'fields'          => array(
				'icon'        => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Icon', 'tailwindscore' ),
					'default' => 'truck',
					'choices' => tailwindscore_homepage_trust_icon_choices(),
				),
				'title'       => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Title', 'tailwindscore' ),
					'default' => '',
				),
				'description' => array(
					'type'    => 'text',
					'label'   => esc_html__( 'Description', 'tailwindscore' ),
					'default' => '',
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_homepage_kirki_register', 10, 2 );
