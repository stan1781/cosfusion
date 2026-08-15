<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Kirki config ID。
 */
function tailwindscore_kirki_config_id(): string {
	return 'tailwindscore';
}

/**
 * Kirki panel ID（旧版，向后兼容）。
 */
function tailwindscore_kirki_panel_id(): string {
	return 'tailwindscore';
}

/**
 * Kirki panel ID：Header。
 */
function tailwindscore_kirki_panel_header(): string {
	return 'tailwindscore_header';
}

/**
 * Customizer 区块 ID：Footer。
 */
function tailwindscore_kirki_panel_footer(): string {
	return 'tailwindscore_footer';
}

/**
 * Customizer 区块 ID：我的账户（根级，不挂 WooCommerce panel）。
 */
function tailwindscore_kirki_section_account(): string {
	return 'tailwindscore_account_auth';
}

/**
 * Kirki panel ID：首页。
 */
function tailwindscore_kirki_panel_home(): string {
	return 'tailwindscore_home';
}

/**
 * Kirki section ID：首页 Hero Banner。
 */
function tailwindscore_kirki_section_home_hero(): string {
	return 'tailwindscore_home_hero';
}

/**
 * Kirki panel ID：商店/归档。
 */
function tailwindscore_kirki_panel_shop(): string {
	return 'tailwindscore_shop';
}

/**
 * Kirki panel ID：商品详情页。
 */
function tailwindscore_kirki_panel_product(): string {
	return 'tailwindscore_product';
}

/**
 * Kirki panel ID：设计（排版等；颜色令牌仍以 SCSS 为真源）。
 */
function tailwindscore_kirki_panel_design(): string {
	return 'tailwindscore_design';
}

/**
 * Customizer 根级区块 ID：购物车（无二级 panel）。
 */
function tailwindscore_kirki_section_cart(): string {
	return 'tailwindscore_cart_settings';
}

/**
 * Customizer 根级区块 ID：结账（无二级 panel）。
 */
function tailwindscore_kirki_section_checkout(): string {
	return 'tailwindscore_checkout_settings';
}

/**
 * Customizer 根级菜单 priority（数字越小越靠前）。
 *
 * 1 站点身份 → 2 设计 → 3 页头 → 4 页脚 → 5 首页 →
 * 6 商店/归档 → 7 商品详情页 → 8 购物车 → 9 结账页面 →
 * 10 我的账户 → 11 404 页面。
 *
 * @return array<string, int>
 */
function tailwindscore_kirki_root_priorities(): array {
	return array(
		'title_tagline' => 1,
		'design'        => 10,
		'header'        => 20,
		'footer'        => 30,
		'home'          => 40,
		'shop'          => 50,
		'product'       => 60,
		'cart'          => 70,
		'checkout'      => 80,
		'account'       => 90,
		'not_found'     => 100,
	);
}

/**
 * 读取根级菜单 priority。
 */
function tailwindscore_kirki_root_priority( string $key, int $fallback = 160 ): int {
	$priorities = tailwindscore_kirki_root_priorities();

	return $priorities[ $key ] ?? $fallback;
}

/**
 * Customizer 目标 ID：主题 panel / 区块，以及原生 WooCommerce panel（兼容）。
 *
 * @return array<string, string>
 */
function tailwindscore_kirki_customizer_targets(): array {
	return array(
		'header'      => tailwindscore_kirki_panel_header(),
		'footer'      => tailwindscore_kirki_panel_footer(),
		'home'        => tailwindscore_kirki_panel_home(),
		'home_hero'   => tailwindscore_kirki_section_home_hero(),
		'account'     => tailwindscore_kirki_section_account(),
		'design'      => tailwindscore_kirki_panel_design(),
		'shop'        => tailwindscore_kirki_panel_shop(),
		'product'     => tailwindscore_kirki_panel_product(),
		'cart'        => tailwindscore_kirki_section_cart(),
		'checkout'    => tailwindscore_kirki_section_checkout(),
		'pwa'         => 'tailwindscore_pwa',
		// 原生 WooCommerce panel；主题 section 不再挂载于此。
		'woocommerce' => 'woocommerce',
	);
}

/**
 * 注册带信息性自定义字段的占位区块。
 *
 * Customizer 会隐藏无控件的区块，自定义字段使区块保持可见。
 *
 * @param string      $cfg         Kirki 配置 ID。
 * @param string      $section_id  区块 ID。
 * @param string|null $panel_id    父 panel ID，顶级区块为 null。
 * @param string      $title       区块标题。
 * @param string      $description 占位说明文案。
 * @param int         $priority    区块优先级。
 */
function tailwindscore_kirki_register_section_placeholder(
	string $cfg,
	string $section_id,
	?string $panel_id,
	string $title,
	string $description,
	int $priority = 10
): void {
	$section_args = array(
		'title'       => $title,
		'description' => $description,
		'priority'    => $priority,
	);

	if ( null !== $panel_id ) {
		$section_args['panel'] = $panel_id;
	}

	Kirki::add_section(
		$section_id,
		$section_args
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'     => 'custom',
			'settings' => $section_id . '_notice',
			'section'  => $section_id,
			'default'  => '<p class="description">' . esc_html( $description ) . '</p>',
		)
	);
}

/**
 * 读取 theme_mod 及默认值（不回退旧 option）。
 *
 * @param string $key     设置名。
 * @param mixed  $default 未设置时的默认值。
 * @return mixed
 */
function tailwindscore_mod( string $key, $default = null ) {
	$value = get_theme_mod( $key, $default );
	return ( null === $value || '' === $value ) && null !== $default ? $default : $value;
}

/**
 * 将父主题 theme_mods 合并到当前子主题 mods（子主题值优先）。
 *
 * WordPress 将 Customizer / Kirki 数据存于 theme_mods_{stylesheet}。子主题
 * 激活时该 option 与父主题分离。pre_option 过滤器
 * 收到 false（非 DB 值）；须显式加载子主题 mods，并临时
 * 移除过滤器以避免递归。
 *
 * @param mixed $pre 先前 pre_option 过滤器的值，或 false。
 * @return mixed
 */
function tailwindscore_filter_child_theme_mods( $pre ) {
	if ( false !== $pre ) {
		return $pre;
	}

	$stylesheet = get_stylesheet();
	$template   = get_template();

	if ( $stylesheet === $template ) {
		return $pre;
	}

	$filter = 'pre_option_theme_mods_' . $stylesheet;
	remove_filter( $filter, 'tailwindscore_filter_child_theme_mods' );

	$child_mods  = get_option( "theme_mods_{$stylesheet}", array() );
	$parent_mods = get_option( "theme_mods_{$template}", array() );

	add_filter( $filter, 'tailwindscore_filter_child_theme_mods' );

	$parent_mods = is_array( $parent_mods ) ? $parent_mods : array();
	$child_mods  = is_array( $child_mods ) ? $child_mods : array();

	return array_replace( $parent_mods, $child_mods );
}

/**
 * 本主题作为父主题使用时注册 theme_mod 继承。
 */
function tailwindscore_register_child_theme_mods_inherit(): void {
	if ( get_stylesheet() === get_template() ) {
		return;
	}

	add_filter(
		'pre_option_theme_mods_' . get_stylesheet(),
		'tailwindscore_filter_child_theme_mods'
	);
}
add_action( 'after_setup_theme', 'tailwindscore_register_child_theme_mods_inherit', 1 );

/**
 * 轮播可见 slide 数选项（1–6，步长 0.5）。
 *
 * @return array<string, string>
 */
function tailwindscore_kirki_slide_count_choices(): array {
	$choices = array();

	for ( $step = 2; $step <= 12; $step++ ) {
		$value            = (string) ( $step / 2 );
		$choices[ $value ] = $value;
	}

	return $choices;
}

/**
 * 为 Kirki 响应式 select 字段消毒单个 slide 数值。
 *
 * @param mixed $value 原始值。
 */
function tailwindscore_kirki_sanitize_slide_count_choice( $value ): string {
	$number = is_numeric( $value ) ? (float) $value : 1.0;
	$number = max( 1.0, min( 6.0, $number ) );

	return (string) ( round( $number * 2.0 ) / 2.0 );
}

/**
 * Customizer 中默认折叠 Kirki repeater 行。
 *
 * Kirki 无对应 PHP 选项；addRow() 渲染后总会展开行。
 */
function tailwindscore_kirki_enqueue_repeater_collapsed_script(): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$path = get_template_directory() . '/inc/config/kirki/repeater-collapsed.js';

	wp_enqueue_script(
		'tailwindscore-kirki-repeater-collapsed',
		get_template_directory_uri() . '/inc/config/kirki/repeater-collapsed.js',
		array( 'customize-controls' ),
		file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0',
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'tailwindscore_kirki_enqueue_repeater_collapsed_script' );
