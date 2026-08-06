<?php
declare(strict_types=1);

/**
 * 站点头部栏（公告条）与 Kirki 设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 头部栏默认文案（须与 Kirki repeater 默认值一致）。
 */
function tailwindscore_header_bar_default_text(): string {
	return __( 'Free shipping on qualifying orders', 'tailwindscore' );
}

/**
 * Repeater 默认条目（一条默认文案）。
 *
 * @return array<int, array{text: string, link_url: string, link_label: string}>
 */
function tailwindscore_header_bar_items_default(): array {
	return array(
		array(
			'text'       => tailwindscore_header_bar_default_text(),
			'link_url'   => '',
			'link_label' => '',
		),
	);
}

/**
 * 公告条配色选项。
 *
 * @return array<string, string>
 */
function tailwindscore_header_bar_scheme_choices(): array {
	return array(
		'dark'  => esc_html__( '深色（主色）', 'tailwindscore' ),
		'light' => esc_html__( '浅色', 'tailwindscore' ),
	);
}

/**
 * @param mixed $value Raw theme_mod.
 */
function tailwindscore_header_bar_scheme_sanitize( $value ): string {
	$value = is_string( $value ) ? $value : 'light';

	return array_key_exists( $value, tailwindscore_header_bar_scheme_choices() ) ? $value : 'light';
}

/**
 * 公告条配色预设（默认 light，贴近升级前 muted 外观）。
 */
function tailwindscore_header_bar_scheme(): string {
	return tailwindscore_header_bar_scheme_sanitize( tailwindscore_mod( 'header_bar_scheme', 'light' ) );
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
 * 规范化公告行（跳过空文案）。
 *
 * @param mixed $raw theme_mod 原始值。
 * @return array<int, array{text: string, link_url: string, link_label: string}>
 */
function tailwindscore_header_bar_normalize_items( $raw ): array {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$items = array();

	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$text = trim( (string) ( $row['text'] ?? '' ) );

		if ( '' === $text ) {
			continue;
		}

		$items[] = array(
			'text'       => $text,
			'link_url'   => trim( (string) ( $row['link_url'] ?? '' ) ),
			'link_label' => trim( (string) ( $row['link_label'] ?? '' ) ),
		);
	}

	return $items;
}

/**
 * 过滤后的公告条目（跳过空文案；尚未设置 items 时回退默认一条）。
 *
 * @return array<int, array{text: string, link_url: string, link_label: string}>
 */
function tailwindscore_header_bar_items(): array {
	$mods = get_theme_mods();
	$has_items_key = is_array( $mods ) && array_key_exists( 'header_bar_items', $mods );

	if ( $has_items_key ) {
		return tailwindscore_header_bar_normalize_items( $mods['header_bar_items'] );
	}

	return tailwindscore_header_bar_items_default();
}

/**
 * 是否渲染顶部头部栏。
 */
function tailwindscore_should_render_header_bar(): bool {
	if ( ! tailwindscore_header_bar_is_enabled() ) {
		return false;
	}

	return array() !== tailwindscore_header_bar_items();
}

/**
 * 多条轮播间隔（毫秒）。
 */
function tailwindscore_header_bar_autoplay_delay(): int {
	$delay = (int) tailwindscore_mod( 'header_bar_autoplay_delay', 4000 );

	return max( 2500, min( 8000, $delay ) );
}

/**
 * 输出单条公告消息 markup。
 *
 * @param array{text: string, link_url: string, link_label: string} $item
 */
function tailwindscore_render_header_bar_message( array $item ): void {
	$text       = $item['text'];
	$link_url   = $item['link_url'];
	$link_label = $item['link_label'];
	?>
	<p class="site-header-bar__message">
		<span class="site-header-bar__text"><?php echo esc_html( $text ); ?></span>
		<?php if ( '' !== $link_url ) : ?>
			<?php
			$label = '' !== $link_label
				? $link_label
				: __( 'Learn more', 'tailwindscore' );
			?>
			<a class="site-header-bar__link" href="<?php echo esc_url( $link_url ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * @param string                $cfg     Kirki 配置 ID。
 * @param array<string, string> $targets 按区域索引的 Customizer 面板 / 区块 ID。
 */
function tailwindscore_header_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_section(
		'tailwindscore_header',
		array(
			'title'       => esc_html__( '顶部公告栏', 'tailwindscore' ),
			'description' => esc_html__( '顶部公告栏；单条静态显示，多条垂直轮播。滚动时消失；主页头保持粘性。', 'tailwindscore' ),
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
			'priority' => 10,
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'              => 'radio-buttonset',
			'settings'          => 'header_bar_scheme',
			'label'             => esc_html__( '配色', 'tailwindscore' ),
			'section'           => 'tailwindscore_header',
			'default'           => 'light',
			'priority'          => 20,
			'choices'           => tailwindscore_header_bar_scheme_choices(),
			'sanitize_callback' => 'tailwindscore_header_bar_scheme_sanitize',
			'active_callback'   => array(
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
			'type'            => 'slider',
			'settings'        => 'header_bar_autoplay_delay',
			'label'           => esc_html__( '轮播间隔（毫秒）', 'tailwindscore' ),
			'description'     => esc_html__( '仅多条讯息时生效。', 'tailwindscore' ),
			'section'         => 'tailwindscore_header',
			'default'         => 4000,
			'priority'        => 30,
			'choices'         => array(
				'min'  => 2500,
				'max'  => 8000,
				'step' => 100,
			),
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
			'type'         => 'repeater',
			'settings'     => 'header_bar_items',
			'label'        => esc_html__( '公告讯息', 'tailwindscore' ),
			'description'  => esc_html__( '每行一则；空白文案的行前台会略过。', 'tailwindscore' ),
			'section'      => 'tailwindscore_header',
			'priority'     => 40,
			'row_label'    => array(
				'type'  => 'field',
				'value' => esc_html__( '讯息', 'tailwindscore' ),
				'field' => 'text',
			),
			'button_label' => esc_html__( '新增讯息', 'tailwindscore' ),
			'default'      => tailwindscore_header_bar_items_default(),
			'fields'       => array(
				'text'       => array(
					'type'    => 'text',
					'label'   => esc_html__( '文案', 'tailwindscore' ),
					'default' => '',
				),
				'link_url'   => array(
					'type'    => 'text',
					'label'   => esc_html__( '链接 URL', 'tailwindscore' ),
					'default' => '',
				),
				'link_label' => array(
					'type'    => 'text',
					'label'   => esc_html__( '链接文字', 'tailwindscore' ),
					'default' => '',
				),
			),
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
