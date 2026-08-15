<?php
/**
 * 第三方追踪脚本延迟：对接 WP Enhanced Tools「脚本管理」Footer/Head/Body。
 *
 * Hotjar 经插件 `Script_Manager`（option `wc_enhanced_tools_scripts`）直出 HTML，
 * 不经 `wp_enqueue_script`，须滤 `wp_enhanced_tools_script_output`。
 * 不默认延迟 GTM / gtag（pageview / 转化会丢）。
 */
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 字符串是否含 Hotjar 引导代码。
 */
function tailwindscore_html_mentions_hotjar( string $html ): bool {
	return false !== stripos( $html, 'hotjar' )
		|| false !== stripos( $html, 'static.hotjar.com' );
}

/**
 * 将含 Hotjar 的 <script> 改为 idle / 交互后再执行，降低 TBT。
 *
 * 非 Hotjar 脚本原样返回；已标记 `data-ts-hotjar-delay` 的不重复处理。
 */
function tailwindscore_delay_hotjar_in_html( string $html ): string {
	if ( '' === trim( $html ) || ! tailwindscore_html_mentions_hotjar( $html ) ) {
		return $html;
	}

	if ( str_contains( $html, 'data-ts-hotjar-delay' ) ) {
		return $html;
	}

	$loader_needed = false;

	$rewritten = (string) preg_replace_callback(
		'/<script\b([^>]*)>(.*?)<\/script>/is',
		static function ( array $matches ) use ( &$loader_needed ): string {
			$attrs = $matches[1];
			$body  = $matches[2];
			$full  = $matches[0];

			if ( ! tailwindscore_html_mentions_hotjar( $full ) ) {
				return $full;
			}

			$loader_needed = true;

			if ( preg_match( '/\bsrc\s*=\s*([\'"])(.*?)\1/i', $attrs, $src_match ) ) {
				$src = $src_match[2];

				return sprintf(
					'<script type="application/json" data-ts-hotjar-delay-src>%s</script>',
					wp_json_encode( $src )
				);
			}

			return '<script type="text/plain" data-ts-hotjar-delay>' . $body . '</script>';
		},
		$html
	);

	if ( ! $loader_needed || ! is_string( $rewritten ) ) {
		return $html;
	}

	$loader = <<<'JS'
<script data-ts-hotjar-loader>
(function () {
	if (window.__tsHotjarLoaderBound) return;
	window.__tsHotjarLoaderBound = true;
	var started = false;
	function inject() {
		if (started) return;
		started = true;
		document.querySelectorAll("script[data-ts-hotjar-delay]").forEach(function (el) {
			var s = document.createElement("script");
			s.text = el.textContent || "";
			el.parentNode && el.parentNode.replaceChild(s, el);
		});
		document.querySelectorAll("script[data-ts-hotjar-delay-src]").forEach(function (el) {
			var src = "";
			try { src = JSON.parse(el.textContent || "\"\""); } catch (e) { src = ""; }
			if (!src) return;
			var s = document.createElement("script");
			s.src = src;
			s.async = true;
			s.defer = true;
			el.parentNode && el.parentNode.replaceChild(s, el);
		});
	}
	function arm() {
		if ("requestIdleCallback" in window) {
			window.requestIdleCallback(inject, { timeout: 4000 });
		} else {
			setTimeout(inject, 3000);
		}
	}
	["pointerdown", "keydown", "scroll", "touchstart"].forEach(function (type) {
		window.addEventListener(type, arm, { passive: true, once: true });
	});
	setTimeout(arm, 4000);
})();
</script>
JS;

	return $rewritten . $loader;
}

/**
 * WP Enhanced Tools 脚本管理器输出：延迟 Hotjar。
 *
 * @param string $html     原始 HTML。
 * @param string $location head|body|foot。
 */
function tailwindscore_enhanced_tools_delay_hotjar( string $html, string $location = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( is_admin() ) {
		return $html;
	}

	return tailwindscore_delay_hotjar_in_html( $html );
}
add_filter( 'wp_enhanced_tools_script_output', 'tailwindscore_enhanced_tools_delay_hotjar', 10, 2 );
