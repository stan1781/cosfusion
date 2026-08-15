<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 内容区块 — [block id="slug|post_id"] 短代码（Flatsome 兼容）。
 */
final class Tailwindscore_Content_Block_Shortcode {

	public static function init(): void {
		add_shortcode( 'block', array( self::class, 'render' ) );

		// wpautop(10) + shortcode_unautop(10) 之后、do_shortcode(11) 之前。
		add_filter( 'the_content', array( self::class, 'unwrap_block_shortcodes' ), 10.5 );
		// 短代码展开后清掉仍残留的空 <p>/<br>（常见于正文首尾空行）。
		add_filter( 'the_content', array( self::class, 'strip_block_autop_artifacts' ), 12 );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'block'
		);

		return Tailwindscore_Content_Block_Renderer::render( (string) $atts['id'] );
	}

	/**
	 * wpautop 会把相邻 [block] 收进同一个 <p>，并用 <br> 分隔。
	 * 展开后变成 <p><div>…</div><style>…</style><br><div>…</div></p>，
	 * 浏览器修正非法嵌套后留下空的 <p>/<br>，造成多余间距。
	 */
	public static function unwrap_block_shortcodes( string $content ): string {
		$replaced = preg_replace_callback(
			'%<p>((?:\s*\[block\b[^\]]*\]\s*(?:<br\s*/?>)?)+\s*)</p>%i',
			static function ( array $matches ): string {
				$inner = preg_replace( '%<br\s*/?>%i', "\n", $matches[1] );

				return trim( is_string( $inner ) ? $inner : $matches[1] );
			},
			$content
		);

		return is_string( $replaced ) ? $replaced : $content;
	}

	/**
	 * 去掉紧贴 .content-block / 其 </style> 的空段落与断行。
	 */
	public static function strip_block_autop_artifacts( string $content ): string {
		$patterns = array(
			// 区块前的空 <p> / <br>
			'%(?:<p>(?:\s|&nbsp;)*</p>\s*|<br\s*/?>\s*)+(?=<div class="content-block\b)%i',
			// </style> 后的空 <p> / <br>（本模块把 CSS 紧跟在区块 div 后输出）
			'%(</style>)\s*(?:<br\s*/?>\s*|<p>(?:\s|&nbsp;)*</p>\s*)+%i',
			// 无 CSS 时：上一区块 </div> 与下一 .content-block 之间的空 <p> / <br>
			'%(</div>)\s*(?:<br\s*/?>\s*|<p>(?:\s|&nbsp;)*</p>\s*)+(?=<div class="content-block\b)%i',
		);

		$replacements = array( '', '$1', '$1' );
		$cleaned      = preg_replace( $patterns, $replacements, $content );

		return is_string( $cleaned ) ? $cleaned : $content;
	}
}
