<?php
declare(strict_types=1);

/**
 * 内联 SVG 图标映射与输出。
 *
 * 样式见 src/scss/components/_icons.scss（.icon）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, string>
 */
function _tailwindscore_icon_map(): array {
	return array(
		'home'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>',
		'sparkles' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3l1.2 4.2L17.5 8.5 13.2 9.7 12 14l-1.2-4.3L6.5 8.5l4.3-1.3z"></path><path d="M5 14l.8 2.8L8.5 17.5 5.8 18.3 5 21l-.8-2.7L1.5 17.5l2.7-.7z"></path><path d="M19 14l.8 2.8 2.7.7-2.7.8L19 21l-.8-2.7-2.7-.8 2.7-.7z"></path></svg>',
		'search' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><path d="M20 20L16.65 16.65"></path></svg>',
		'user'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>',
		'cart'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.5"></circle><circle cx="18" cy="20" r="1.5"></circle><path d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h9.8a1 1 0 0 0 1-.8L21 7H7"></path></svg>',
		'menu'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h16"></path></svg>',
		'close'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12"></path><path d="M18 6l-12 12"></path></svg>',
		'plus'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
		'minus'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"></path></svg>',
		'trash'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 12h10l1-12"></path><path d="M9 7V5h6v2"></path></svg>',
		'pencil'        => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>',
		'chevron-down'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6"></path></svg>',
		'chevron-left'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 6l-6 6 6 6"></path></svg>',
		'chevron-right' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 6l6 6-6 6"></path></svg>',
		'package'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3 3 7.5v9L12 21l9-4.5v-9L12 3z"></path><path d="M12 12 21 7.5"></path><path d="M12 12v9"></path><path d="M12 12 3 7.5"></path></svg>',
		'map-pin'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>',
		'truck'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 7h11v8H3z"></path><path d="M14 10h4l3 3v2h-7v-5z"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg>',
		'ruler'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" stroke-width="1.25"><circle cx="8" cy="15" r="5"></circle><circle cx="8" cy="15" r="1.5"></circle><path d="M13 15h8"></path><path d="M13 13h8"></path><path d="M13 17h8"></path><path d="M15.5 13v4"></path><path d="M18 13v4"></path><path d="M20.5 13v4"></path></svg>',
		'lock'          => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" stroke-width="1.25"><rect x="5.5" y="11" width="13" height="9" rx="1"></rect><path d="M8.5 11V8.5a3.5 3.5 0 0 1 7 0V11"></path></svg>',
		'box-return'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" stroke-width="1.25"><path d="M4 8l8-4 8 4v8l-8 4-8-4V8z"></path><path d="M12 4v16"></path><path d="M4 8l8 4 8-4"></path><path d="M9 14.5 7 16.5l2 2"></path><path d="M7 16.5h5"></path></svg>',
		'circle-check'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M8 12l2.5 2.5L16 9"></path></svg>',
		'gift'          => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25"></path><path d="M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21"></path><path d="M3.375 11.25h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"></path></svg>',
		'clipboard'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5h6a2 2 0 0 1 2 2v1H7V7a2 2 0 0 1 2-2z"></path><path d="M7 8h10v11H7z"></path></svg>',
		'log-out'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"></path><path d="M15 12H3"></path><path d="M6 9l-3 3 3 3"></path></svg>',
		'tag'           => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.252 2.252 0 0 0 3.182 0l6.704-6.704a2.252 2.252 0 0 0 0-3.182z"></path><circle cx="7.5" cy="7.5" r="1.5"></circle></svg>',
		'sliders'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 21v-7"></path><path d="M4 10V3"></path><path d="M12 21v-9"></path><path d="M12 8V3"></path><path d="M20 21v-5"></path><path d="M20 12V3"></path><path d="M1 14h6"></path><path d="M9 8h6"></path><path d="M17 16h6"></path></svg>',
		'heart'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.5 12.572 12 20l-7.5-7.428A5 5 0 1 1 12 6.006a5 5 0 1 1 7.5 6.566"></path></svg>',
		'eye'           => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>',
	);
}

/**
 * 按名称返回 SVG 图标 markup，可选向 <svg> 元素注入额外 CSS class。
 *
 * 所有图标共享基础属性（viewBox、aria-hidden、focusable）。
 * 传入 $class 添加修饰符 — 例如 tailwindscore_icon( 'chevron-down', 'nav__chevron' )。
 */
function tailwindscore_icon( string $name, string $class = '' ): string {
	$svg = _tailwindscore_icon_map()[ $name ] ?? '';

	if ( '' === $svg || '' === $class ) {
		return $svg;
	}

	// 将额外 class 注入 opening <svg ...> 标签。
	return preg_replace(
		'/^<svg /',
		'<svg class="' . esc_attr( $class ) . '" ',
		$svg,
		1
	) ?? $svg;
}

/**
 * 渲染全站关闭 / dismiss 控件（图标 + 屏幕阅读器标签）。
 *
 * @param string               $label 无障碍标签。
 * @param array<string, mixed> $args  可选 class 与额外属性。
 * @return void
 */
function tailwindscore_render_close_button( string $label, array $args = array() ): void {
	$classes = array( 'btn-close' );

	if ( ! empty( $args['class'] ) && is_string( $args['class'] ) ) {
		$classes[] = $args['class'];
	}

	$attributes = array(
		'type'       => 'button',
		'class'      => implode( ' ', $classes ),
		'aria-label' => $label,
	);

	if ( ! empty( $args['attrs'] ) && is_array( $args['attrs'] ) ) {
		foreach ( $args['attrs'] as $key => $value ) {
			if ( is_string( $key ) && ( is_string( $value ) || is_numeric( $value ) ) ) {
				$attributes[ $key ] = (string) $value;
			}
		}
	}

	echo '<button';
	foreach ( $attributes as $key => $value ) {
		printf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}
	echo '>';
	echo '<span class="icon" aria-hidden="true">';
	echo tailwindscore_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</span>';
	echo '<span class="screen-reader-text">';
	echo esc_html( $label );
	echo '</span>';
	echo '</button>';
}
