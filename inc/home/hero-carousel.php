<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 将 Kirki / 附件字段解析为附件 ID。
 *
 * @param mixed $value Attachment ID、URL 或 Kirki image 数组。
 */
function tailwindscore_hero_resolve_image_id( $value ): int {
	if ( is_array( $value ) ) {
		if ( isset( $value['id'] ) ) {
			return tailwindscore_hero_resolve_image_id( $value['id'] );
		}
		if ( isset( $value['url'] ) ) {
			return tailwindscore_hero_resolve_image_id( $value['url'] );
		}

		return 0;
	}

	if ( is_numeric( $value ) ) {
		$id = (int) $value;

		return $id > 0 ? $id : 0;
	}

	$url = esc_url_raw( trim( (string) $value ) );
	if ( '' === $url ) {
		return 0;
	}

	return (int) attachment_url_to_postid( $url );
}

/**
 * 将 Kirki / 附件字段解析为图片 URL。
 *
 * @param mixed $value Attachment ID、URL 或 Kirki image 数组。
 */
function tailwindscore_hero_resolve_image_url( $value ): string {
	$id = tailwindscore_hero_resolve_image_id( $value );
	if ( $id > 0 ) {
		$url = wp_get_attachment_image_url( $id, 'full' );

		return is_string( $url ) ? $url : '';
	}

	if ( is_array( $value ) ) {
		if ( isset( $value['url'] ) ) {
			return tailwindscore_hero_resolve_image_url( $value['url'] );
		}

		return '';
	}

	if ( is_numeric( $value ) ) {
		return '';
	}

	$url = esc_url_raw( trim( (string) $value ) );

	return '' !== $url ? $url : '';
}

/**
 * Hero 响应式 img：有附件 ID 时走 wp_get_attachment_image（srcset / sizes）。
 *
 * @param array<string, string|bool> $attrs img 属性（class / alt / loading / fetchpriority / decoding / sizes）。
 */
function tailwindscore_hero_render_responsive_image( int $attachment_id, string $fallback_url, array $attrs ): string {
	$class         = isset( $attrs['class'] ) ? (string) $attrs['class'] : '';
	$alt           = isset( $attrs['alt'] ) ? (string) $attrs['alt'] : '';
	$loading       = isset( $attrs['loading'] ) ? (string) $attrs['loading'] : 'lazy';
	$decoding      = isset( $attrs['decoding'] ) ? (string) $attrs['decoding'] : 'async';
	$fetchpriority = isset( $attrs['fetchpriority'] ) ? (string) $attrs['fetchpriority'] : '';
	$sizes         = isset( $attrs['sizes'] ) ? (string) $attrs['sizes'] : '(max-width: 47.999rem) 100vw, min(100vw, 1200px)';

	if ( $attachment_id > 0 ) {
		$img_attrs = array(
			'class'    => $class,
			'alt'      => $alt,
			'loading'  => $loading,
			'decoding' => $decoding,
			'sizes'    => $sizes,
		);
		if ( '' !== $fetchpriority ) {
			$img_attrs['fetchpriority'] = $fetchpriority;
		}

		$html = wp_get_attachment_image( $attachment_id, 'large', false, $img_attrs );

		return is_string( $html ) ? $html : '';
	}

	if ( '' === $fallback_url ) {
		return '';
	}

	$priority_attr = '' !== $fetchpriority ? ' fetchpriority="' . esc_attr( $fetchpriority ) . '"' : '';

	return sprintf(
		'<img class="%1$s" src="%2$s" alt="%3$s" loading="%4$s" decoding="%5$s" sizes="%6$s"%7$s>',
		esc_attr( $class ),
		esc_url( $fallback_url ),
		esc_attr( $alt ),
		esc_attr( $loading ),
		esc_attr( $decoding ),
		esc_attr( $sizes ),
		$priority_attr
	);
}

/**
 * 附件 srcset（优先）或单 URL，供 <source> / preload。
 *
 * @return array{src: string, srcset: string}
 */
function tailwindscore_hero_attachment_srcset_pair( int $attachment_id, string $fallback_url = '' ): array {
	$src    = '';
	$srcset = '';

	if ( $attachment_id > 0 ) {
		$src    = (string) ( wp_get_attachment_image_url( $attachment_id, 'large' ) ?: '' );
		$srcset = (string) ( wp_get_attachment_image_srcset( $attachment_id, 'large' ) ?: '' );
		if ( '' === $srcset && '' !== $src ) {
			$srcset = $src;
		}
	}

	if ( '' === $src && '' !== $fallback_url ) {
		$src    = $fallback_url;
		$srcset = $fallback_url;
	}

	return array(
		'src'    => $src,
		'srcset' => $srcset,
	);
}

/**
 * 首页 / 含短代码页：在 <head> 预加载首张 Hero（桌面 + 手机分流），缩短 LCP 发现延迟。
 */
function tailwindscore_hero_preload_lcp_in_head(): void {
	if ( is_admin() ) {
		return;
	}

	$should_preload = is_front_page() || is_home();

	if ( ! $should_preload && is_singular() ) {
		$post = get_post();
		if ( $post instanceof WP_Post
			&& ( has_shortcode( (string) $post->post_content, 'ts_home_hero' )
				|| false !== stripos( (string) $post->post_content, '[ts_home_hero' ) ) ) {
			$should_preload = true;
		}
	}

	if ( ! $should_preload ) {
		return;
	}

	$config = tailwindscore_get_home_hero_config();
	if ( empty( $config['enable'] ) || empty( $config['slides'][0] ) || ! is_array( $config['slides'][0] ) ) {
		return;
	}

	$slide           = $config['slides'][0];
	$image_id        = isset( $slide['image_id'] ) ? (int) $slide['image_id'] : 0;
	$image_mobile_id = isset( $slide['image_mobile_id'] ) ? (int) $slide['image_mobile_id'] : 0;
	$image           = isset( $slide['image'] ) ? (string) $slide['image'] : '';
	$image_mobile    = isset( $slide['image_mobile'] ) ? (string) $slide['image_mobile'] : '';

	$desktop_sizes = '(max-width: 47.999rem) 100vw, min(100vw, 1200px)';
	$mobile_sizes  = '100vw';

	$mobile = tailwindscore_hero_attachment_srcset_pair( $image_mobile_id, $image_mobile );
	if ( '' !== $mobile['src'] ) {
		printf(
			'<link rel="preload" as="image" href="%1$s" imagesrcset="%2$s" imagesizes="%3$s" media="(width < 48rem)" fetchpriority="high" />' . "\n",
			esc_url( $mobile['src'] ),
			esc_attr( '' !== $mobile['srcset'] ? $mobile['srcset'] : $mobile['src'] ),
			esc_attr( $mobile_sizes )
		);
	}

	$desktop = tailwindscore_hero_attachment_srcset_pair( $image_id, $image );
	if ( '' !== $desktop['src'] ) {
		$media = '' !== $mobile['src'] ? '(width >= 48rem)' : '';
		if ( '' !== $media ) {
			printf(
				'<link rel="preload" as="image" href="%1$s" imagesrcset="%2$s" imagesizes="%3$s" media="%4$s" fetchpriority="high" />' . "\n",
				esc_url( $desktop['src'] ),
				esc_attr( '' !== $desktop['srcset'] ? $desktop['srcset'] : $desktop['src'] ),
				esc_attr( $desktop_sizes ),
				esc_attr( $media )
			);
		} else {
			printf(
				'<link rel="preload" as="image" href="%1$s" imagesrcset="%2$s" imagesizes="%3$s" fetchpriority="high" />' . "\n",
				esc_url( $desktop['src'] ),
				esc_attr( '' !== $desktop['srcset'] ? $desktop['srcset'] : $desktop['src'] ),
				esc_attr( $desktop_sizes )
			);
		}
	}
}
add_action( 'wp_head', 'tailwindscore_hero_preload_lcp_in_head', 2 );

/**
 * 禁止 WP 给 Hero 图注入 sizes="auto, …"（会干扰 LCP 候选）。
 *
 * @param bool   $add   是否添加 auto sizes。
 * @param string $image 完整 img HTML（WP 6.7+ 第二参；旧版可能缺失）。
 */
function tailwindscore_hero_disable_auto_sizes( bool $add, $image = '' ): bool {
	if ( ! $add ) {
		return false;
	}

	if ( is_string( $image ) && str_contains( $image, 'hero-carousel__img' ) ) {
		return false;
	}

	return $add;
}
add_filter( 'wp_img_tag_add_auto_sizes', 'tailwindscore_hero_disable_auto_sizes', 10, 2 );

/**
 * 附件宽高比 CSS（如 "1200 / 504"），供 aspect-ratio 占位、降低 CLS。
 */
function tailwindscore_hero_attachment_aspect_ratio_css( int $attachment_id ): string {
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( ! is_array( $meta ) ) {
		return '';
	}

	$width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	$height = isset( $meta['height'] ) ? (int) $meta['height'] : 0;
	if ( $width < 1 || $height < 1 ) {
		return '';
	}

	return $width . ' / ' . $height;
}

/**
 * 输出 figure 上的 --ts-hero-ar / --ts-hero-ar-mobile（仅 peek 使用）。
 */
function tailwindscore_hero_media_aspect_ratio_style( int $image_id, int $image_mobile_id = 0 ): string {
	$parts = array();
	$ar    = tailwindscore_hero_attachment_aspect_ratio_css( $image_id );
	if ( '' !== $ar ) {
		$parts[] = '--ts-hero-ar:' . $ar;
	}

	$ar_mobile = tailwindscore_hero_attachment_aspect_ratio_css( $image_mobile_id );
	if ( '' !== $ar_mobile ) {
		$parts[] = '--ts-hero-ar-mobile:' . $ar_mobile;
	}

	return array() === $parts ? '' : implode( ';', $parts ) . ';';
}

/**
 * Hero slidesPerView：1–4，0.5 步进。
 *
 * @param mixed $value Raw value.
 */
function tailwindscore_sanitize_hero_carousel_slide_count( $value ): float {
	$number = is_numeric( $value ) ? (float) $value : 1.0;
	$number = max( 1.0, min( 4.0, $number ) );

	return round( $number * 2.0 ) / 2.0;
}

/**
 * @param mixed $value Raw delay ms.
 */
function tailwindscore_sanitize_hero_autoplay_delay( $value ): int {
	$delay = is_numeric( $value ) ? (int) $value : 5000;

	return max( 2000, min( 10000, $delay ) );
}

/**
 * Hero 布局：仅 full / peek；centered 回退为 full。
 *
 * @param mixed $value Raw layout.
 */
function tailwindscore_sanitize_hero_layout( $value ): string {
	$layout = sanitize_key( (string) $value );

	if ( 'peek' === $layout ) {
		return 'peek';
	}

	return 'full';
}

/**
 * 解析 layout：已存 theme_mod 优先；未存且 columns>1 时兼容为 peek。
 */
function tailwindscore_resolve_home_hero_layout( float $columns_mobile, float $columns_desktop ): string {
	$stored = get_theme_mod( 'home_hero_layout', null );

	if ( null !== $stored && '' !== (string) $stored ) {
		return tailwindscore_sanitize_hero_layout( $stored );
	}

	if ( $columns_mobile > 1.0 || $columns_desktop > 1.0 ) {
		return 'peek';
	}

	return 'full';
}

/**
 * @param mixed $raw Repeater rows.
 * @return list<array{image: string, image_id: int, image_mobile: string, image_mobile_id: int, title: string, link_url: string}>
 */
function tailwindscore_parse_hero_slides( $raw ): array {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$slides = array();

	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$image_raw = $row['image'] ?? '';
		$image_id  = isset( $row['image_id'] ) ? absint( $row['image_id'] ) : tailwindscore_hero_resolve_image_id( $image_raw );
		$image     = $image_id > 0
			? (string) ( wp_get_attachment_image_url( $image_id, 'full' ) ?: '' )
			: tailwindscore_hero_resolve_image_url( $image_raw );

		if ( '' === $image ) {
			continue;
		}

		$mobile_raw = $row['image_mobile'] ?? '';
		$mobile_id  = isset( $row['image_mobile_id'] ) ? absint( $row['image_mobile_id'] ) : tailwindscore_hero_resolve_image_id( $mobile_raw );
		$mobile_url = $mobile_id > 0
			? (string) ( wp_get_attachment_image_url( $mobile_id, 'full' ) ?: '' )
			: tailwindscore_hero_resolve_image_url( $mobile_raw );

		$slides[] = array(
			'image'           => $image,
			'image_id'        => $image_id,
			'image_mobile'    => $mobile_url,
			'image_mobile_id' => $mobile_id,
			'title'           => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'link_url'        => esc_url_raw( trim( (string) ( $row['link_url'] ?? '' ) ) ),
		);
	}

	return $slides;
}

/**
 * @return array{
 *   enable: bool,
 *   autoplay: bool,
 *   autoplay_delay: int,
 *   layout: string,
 *   columns_mobile: float,
 *   columns_desktop: float,
 *   gap: int,
 *   slides: list<array{image: string, image_id: int, image_mobile: string, image_mobile_id: int, title: string, link_url: string}>
 * }
 */
function tailwindscore_get_home_hero_config(): array {
	$enable = (bool) tailwindscore_mod( 'home_hero_enable', true );
	$slides = $enable ? tailwindscore_parse_hero_slides( tailwindscore_mod( 'home_hero_slides', array() ) ) : array();

	$gap = function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
		? tailwindscore_sanitize_products_carousel_gap( tailwindscore_mod( 'home_hero_gap', 0 ) )
		: max( 0, min( 48, (int) tailwindscore_mod( 'home_hero_gap', 0 ) ) );

	$columns_mobile  = tailwindscore_sanitize_hero_carousel_slide_count( tailwindscore_mod( 'home_hero_columns_mobile', '1' ) );
	$columns_desktop = tailwindscore_sanitize_hero_carousel_slide_count( tailwindscore_mod( 'home_hero_columns_desktop', '1' ) );

	return array(
		'enable'          => $enable,
		'autoplay'        => (bool) tailwindscore_mod( 'home_hero_autoplay', true ),
		'autoplay_delay'  => tailwindscore_sanitize_hero_autoplay_delay( tailwindscore_mod( 'home_hero_autoplay_delay', 5000 ) ),
		'layout'          => tailwindscore_resolve_home_hero_layout( $columns_mobile, $columns_desktop ),
		'columns_mobile'  => $columns_mobile,
		'columns_desktop' => $columns_desktop,
		'gap'             => $gap,
		'slides'          => $slides,
	);
}

/**
 * @return list<array{image: string, image_id: int, image_mobile: string, image_mobile_id: int, title: string, link_url: string}>
 */
function tailwindscore_get_home_hero_slides(): array {
	$config = tailwindscore_get_home_hero_config();

	return $config['slides'];
}

/**
 * 共用 Hero 轮播输出（首页短代码 / 后续分类 term 可复用）。
 *
 * @param list<array{image?: string, image_id?: int, image_mobile?: string, image_mobile_id?: int, title?: string, link_url?: string}> $slides
 * @param array{
 *   autoplay?: bool,
 *   autoplay_delay?: int,
 *   layout?: string,
 *   columns_mobile?: float|int|string,
 *   columns_desktop?: float|int|string,
 *   gap?: int|string,
 *   section_class?: string
 * } $args
 */
function tailwindscore_render_hero_carousel( array $slides, array $args = array() ): void {
	$slides = tailwindscore_parse_hero_slides( $slides );

	if ( array() === $slides ) {
		return;
	}

	$defaults = tailwindscore_get_home_hero_config();

	$autoplay       = array_key_exists( 'autoplay', $args ) ? (bool) $args['autoplay'] : $defaults['autoplay'];
	$autoplay_delay = array_key_exists( 'autoplay_delay', $args )
		? tailwindscore_sanitize_hero_autoplay_delay( $args['autoplay_delay'] )
		: $defaults['autoplay_delay'];
	$layout = array_key_exists( 'layout', $args )
		? tailwindscore_sanitize_hero_layout( $args['layout'] )
		: $defaults['layout'];
	$columns_mobile = array_key_exists( 'columns_mobile', $args )
		? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_mobile'] )
		: $defaults['columns_mobile'];
	$columns_desktop = array_key_exists( 'columns_desktop', $args )
		? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_desktop'] )
		: $defaults['columns_desktop'];

	if ( array_key_exists( 'gap', $args ) ) {
		$gap = function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
			? tailwindscore_sanitize_products_carousel_gap( $args['gap'] )
			: max( 0, min( 48, (int) $args['gap'] ) );
	} else {
		$gap = $defaults['gap'];
	}

	get_template_part(
		'template-parts/home/hero-carousel',
		null,
		array(
			'slides'          => $slides,
			'autoplay'        => $autoplay,
			'autoplay_delay'  => $autoplay_delay,
			'layout'          => $layout,
			'columns_mobile'  => $columns_mobile,
			'columns_desktop' => $columns_desktop,
			'gap'             => $gap,
			'section_class'   => isset( $args['section_class'] ) ? trim( (string) $args['section_class'] ) : '',
		)
	);
}

/**
 * @param array<string, string>|string $atts Shortcode attributes.
 */
function tailwindscore_home_hero_shortcode( $atts ): string {
	$config = tailwindscore_get_home_hero_config();

	if ( ! $config['enable'] || array() === $config['slides'] ) {
		return '';
	}

	$parsed = shortcode_atts(
		array(
			'layout'          => '',
			'columns_mobile'  => '',
			'columns_desktop' => '',
			'gap'             => '',
			'autoplay'        => '',
			'autoplay_delay'  => '',
		),
		is_array( $atts ) ? $atts : array(),
		'ts_home_hero'
	);

	$args = array();

	if ( '' !== (string) $parsed['layout'] ) {
		$args['layout'] = $parsed['layout'];
	}
	if ( '' !== (string) $parsed['columns_mobile'] ) {
		$args['columns_mobile'] = $parsed['columns_mobile'];
	}
	if ( '' !== (string) $parsed['columns_desktop'] ) {
		$args['columns_desktop'] = $parsed['columns_desktop'];
	}
	if ( '' !== (string) $parsed['gap'] ) {
		$args['gap'] = $parsed['gap'];
	}
	if ( '' !== (string) $parsed['autoplay'] ) {
		$args['autoplay'] = in_array(
			strtolower( (string) $parsed['autoplay'] ),
			array( '1', 'true', 'yes', 'on' ),
			true
		);
	}
	if ( '' !== (string) $parsed['autoplay_delay'] ) {
		$args['autoplay_delay'] = $parsed['autoplay_delay'];
	}

	ob_start();
	tailwindscore_render_hero_carousel( $config['slides'], $args );

	return (string) ob_get_clean();
}
add_shortcode( 'ts_home_hero', 'tailwindscore_home_hero_shortcode' );
