<?php
declare(strict_types=1);

/**
 * Guest My Account page shell — uses auth document without site header/footer.
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
