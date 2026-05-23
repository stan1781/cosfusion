<?php
declare(strict_types=1);

/**
 * Register page template (slug: register).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/account-auth/document',
	null,
	array(
		'content_callback' => static function (): void {
			echo do_shortcode( '[ts_registration_form]' );
		},
	)
);
