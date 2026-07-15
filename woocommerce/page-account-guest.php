<?php
declare(strict_types=1);

/**
 * 访客我的账户页面外壳 — 使用认证文档，不含站点 header/footer。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/account-auth/document',
	null,
	array(
		'content_callback' => static function (): void {
			while ( have_posts() ) {
				the_post();
				the_content();
			}
		},
	)
);
