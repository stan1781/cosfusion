<?php
declare(strict_types=1);

/**
 * 站点头部栏（公告条）与 Kirki 设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 头部栏默认文案（须与 Kirki 字段默认值一致）。
 */
function tailwindscore_header_bar_default_text(): string {
	return __( 'Free shipping on qualifying orders', 'tailwindscore' );
}

/**
 * 头部栏开关是否开启（未设置 theme_mod 时默认启用）。
 */
function tailwindscore_header_bar_is_enabled(): bool {
	$mods = get_theme_mods();

	if ( ! is_array( $mods ) || ! array_key_exists( 'header_bar_enable', $mods ) ) {
		return true;
	}

	return (bool) tailwindscore_mod( 'header_bar_enable', true );
}

/**
 * 是否渲染顶部头部栏。
 */
function tailwindscore_should_render_header_bar(): bool {
	if ( ! tailwindscore_header_bar_is_enabled() ) {
		return false;
	}

	return '' !== tailwindscore_header_bar_text();
}

/**
 * 头部栏消息文本。
 */
function tailwindscore_header_bar_text(): string {
	return trim(
		(string) tailwindscore_mod( 'header_bar_text', tailwindscore_header_bar_default_text() )
	);
}

/**
 * 可选头部栏链接 URL（未设置时为空）。
 */
function tailwindscore_header_bar_link_url(): string {
	return trim( (string) tailwindscore_mod( 'header_bar_link_url', '' ) );
}

/**
 * 可选头部栏链接标签。
 */
function tailwindscore_header_bar_link_label(): string {
	return trim( (string) tailwindscore_mod( 'header_bar_link_label', '' ) );
}

/**
 * @param string $cfg    Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_header_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_header',
		array(
			'title'       => esc_html__( '页头', 'tailwindscore' ),
			'description' => esc_html__( '顶部公告栏与页头外观。', 'tailwindscore' ),
			'panel'       => $targets['header'],
			'priority'    => 35,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'toggle',
			'settings' => 'header_bar_enable',
			'label'    => esc_html__( '显示顶栏', 'tailwindscore' ),
			'section'  => 'tailwindscore_header',
			'default'  => true,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'textarea',
			'settings'        => 'header_bar_text',
			'label'           => esc_html__( '顶栏文案', 'tailwindscore' ),
			'description'     => esc_html__( '显示在主页头上方的短文案。滚动时消失；主页头保持粘性。', 'tailwindscore' ),
			'section'         => 'tailwindscore_header',
			'default'         => tailwindscore_header_bar_default_text(),
			'active_callback' => array(
				array(
					'setting'  => 'header_bar_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'header_bar_link_url',
			'label'           => esc_html__( '顶栏链接 URL', 'tailwindscore' ),
			'section'         => 'tailwindscore_header',
			'default'         => '',
			'active_callback' => array(
				array(
					'setting'  => 'header_bar_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'            => 'text',
			'settings'        => 'header_bar_link_label',
			'label'           => esc_html__( '顶栏链接文字', 'tailwindscore' ),
			'description'     => esc_html__( '留空则隐藏链接。若设置了 URL 但未填文字，则使用「Learn more」。', 'tailwindscore' ),
			'section'         => 'tailwindscore_header',
			'default'         => '',
			'active_callback' => array(
				array(
					'setting'  => 'header_bar_enable',
					'operator' => '==',
					'value'    => true,
				),
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_header_kirki_register', 10, 2 );
