<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 内容区块 — HTML / CSS 规范化（AI 生成内容常见 <style> 包裹）。
 */
final class Tailwindscore_Content_Block_Content {

	/**
	 * 去掉 CSS 字段中的 <style> 标签，保留纯 CSS 规则。
	 */
	public static function normalize_css( string $css ): string {
		$css = trim( $css );
		if ( '' === $css ) {
			return '';
		}

		if ( ! preg_match( '/<style\b/i', $css ) ) {
			return $css;
		}

		$parts = array();
		if ( preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/is', $css, $matches ) ) {
			foreach ( $matches[1] as $chunk ) {
				$chunk = trim( (string) $chunk );
				if ( '' !== $chunk ) {
					$parts[] = $chunk;
				}
			}
		}

		if ( ! empty( $parts ) ) {
			return implode( "\n\n", $parts );
		}

		return wp_strip_all_tags( $css );
	}

	/**
	 * 从 HTML 中提取 <style> 块，返回剥离后的 HTML 与合并的 CSS。
	 *
	 * 先遮蔽 HTML 注释，避免注释中的「&lt;style&gt;」字面量被误匹配并吞掉正文。
	 *
	 * @return array{0: string, 1: string}
	 */
	public static function extract_styles_from_html( string $html ): array {
		$extracted = array();
		$comments  = array();

		$masked = preg_replace_callback(
			'/<!--.*?-->/s',
			static function ( array $matches ) use ( &$comments ): string {
				$index      = count( $comments );
				$comments[] = $matches[0];

				return '<!--TS_STYLE_EXTRACT_COMMENT_' . $index . '-->';
			},
			$html
		);

		if ( ! is_string( $masked ) ) {
			$masked = $html;
		}

		$cleaned = preg_replace_callback(
			'/<style\b[^>]*>(.*?)<\/style>/is',
			static function ( array $matches ) use ( &$extracted ): string {
				$inner = trim( (string) ( $matches[1] ?? '' ) );
				if ( '' !== $inner ) {
					$extracted[] = $inner;
				}

				return '';
			},
			$masked
		);

		if ( ! is_string( $cleaned ) ) {
			$cleaned = $masked;
		}

		if ( ! empty( $comments ) ) {
			$cleaned = preg_replace_callback(
				'/<!--TS_STYLE_EXTRACT_COMMENT_(\d+)-->/',
				static function ( array $matches ) use ( $comments ): string {
					$index = (int) $matches[1];

					return $comments[ $index ] ?? '';
				},
				$cleaned
			);
		}

		return array(
			is_string( $cleaned ) ? trim( $cleaned ) : trim( $html ),
			implode( "\n\n", $extracted ),
		);
	}

	/**
	 * 合并两段 CSS（去重空行）。
	 */
	public static function merge_css( string $primary, string $secondary ): string {
		$parts = array();

		foreach ( array( $primary, $secondary ) as $chunk ) {
			$chunk = self::normalize_css( trim( $chunk ) );
			if ( '' !== $chunk ) {
				$parts[] = $chunk;
			}
		}

		return implode( "\n\n", $parts );
	}
}
