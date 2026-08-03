<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 新建 Page 默认使用全宽模板（自定义 HTML / Content Block 常见需求）。
 */
function tailwindscore_filter_default_page_template( string $template ): string {
	return 'page-templates/full-width.php';
}
add_filter( 'default_page_template', 'tailwindscore_filter_default_page_template' );

/**
 * 当前 Page 是否使用全宽模板。
 */
function tailwindscore_is_full_width_page(): bool {
	return is_singular( 'page' ) && 'page-templates/full-width.php' === get_page_template_slug();
}

/**
 * 是否输出页面级标题带（首页、全宽页、Woo 流程页不输出）。
 */
function tailwindscore_show_page_title(): bool {
	if ( is_front_page() ) {
		return false;
	}

	if ( tailwindscore_is_full_width_page() ) {
		return false;
	}

	return (bool) apply_filters( 'tailwindscore/show_page_title', true );
}
