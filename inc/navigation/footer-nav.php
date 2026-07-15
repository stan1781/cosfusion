<?php
declare(strict_types=1);

/**
 * 页脚导航、支付图标与 Kirki 设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 允许的页脚导航对齐值。
 *
 * @return array<string, string>
 */
function tailwindscore_footer_nav_alignment_choices(): array {
	return array(
		'left'   => esc_html__( 'Left', 'tailwindscore' ),
		'center' => esc_html__( 'Center', 'tailwindscore' ),
		'right'  => esc_html__( 'Right', 'tailwindscore' ),
	);
}

/**
 * 消毒页脚导航对齐 theme_mod。
 *
 * @param mixed $value 原始值。
 */
function tailwindscore_footer_nav_alignment_sanitize( $value ): string {
	$value = is_string( $value ) ? $value : 'center';

	return array_key_exists( $value, tailwindscore_footer_nav_alignment_choices() ) ? $value : 'center';
}

/**
 * 页脚导航水平对齐（左、中、右）。
 */
function tailwindscore_footer_nav_alignment(): string {
	return tailwindscore_footer_nav_alignment_sanitize(
		tailwindscore_mod( 'footer_nav_alignment', 'center' )
	);
}

/**
 * 页脚导航对齐的 BEM 修饰 class。
 */
function tailwindscore_footer_nav_alignment_class(): string {
	return 'site-footer__nav--align-' . tailwindscore_footer_nav_alignment();
}

/**
 * 是否渲染页脚导航行。
 */
function tailwindscore_should_render_footer_nav(): bool {
	return has_nav_menu( 'footer' );
}

/**
 * 构建页脚位置的 wp_nav_menu() 参数。
 *
 * @return array<string, mixed>
 */
function tailwindscore_footer_nav_menu_args(): array {
	$menu_args = array(
		'theme_location' => 'footer',
		'container'      => false,
		'menu_class'     => 'site-footer__nav-list',
		'depth'          => 1,
		'fallback_cb'    => false,
	);

	/**
	 * @param array<string, mixed> $menu_args wp_nav_menu 参数。
	 */
	return apply_filters( 'tailwindscore_footer_nav_menu_args', $menu_args );
}

/**
 * 将 theme_mod 图片值解析为有效 attachment ID。
 *
 * @param mixed $image 来自 theme_mod 的原始值。
 */
function tailwindscore_footer_payment_image_attachment_id( $image ): int {
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
 * Customizer 中的页脚支付图片 attachment ID。
 */
function tailwindscore_footer_payment_image_id(): int {
	return tailwindscore_footer_payment_image_attachment_id(
		tailwindscore_mod( 'footer_payment_image', '' )
	);
}

/**
 * 是否已配置页脚支付图片。
 */
function tailwindscore_footer_has_payment_image(): bool {
	return tailwindscore_footer_payment_image_id() > 0;
}

/**
 * @param string               $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_footer_nav_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		$targets['footer'],
		array(
			'title'       => esc_html__( 'Footer', 'tailwindscore' ),
			'description' => esc_html__( 'Footer navigation and layout.', 'tailwindscore' ),
			'priority'    => 20,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'custom',
			'settings' => 'footer_nav_menu_notice',
			'section'  => $targets['footer'],
			'default'  => '<p class="description">' . esc_html__( 'Assign a menu under Appearance → Menus to the “Footer Menu” location. Links render as a single horizontal row above the copyright area.', 'tailwindscore' ) . '</p>',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'radio-buttonset',
			'settings'          => 'footer_nav_alignment',
			'label'             => esc_html__( 'Footer menu alignment', 'tailwindscore' ),
			'section'           => $targets['footer'],
			'default'           => 'center',
			'choices'           => tailwindscore_footer_nav_alignment_choices(),
			'sanitize_callback' => 'tailwindscore_footer_nav_alignment_sanitize',
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'footer_payment_image',
			'label'       => esc_html__( 'Payment icons', 'tailwindscore' ),
			'description' => esc_html__( 'Upload a single composite image of accepted payment methods, shown beside the copyright line.', 'tailwindscore' ),
			'section'     => $targets['footer'],
			'priority'    => 20,
			'default'     => '',
			'choices'     => array(
				'save_as' => 'id',
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_footer_nav_kirki_register', 10, 2 );
