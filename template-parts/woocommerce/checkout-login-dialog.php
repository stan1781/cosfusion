<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/header/account-login-dialog',
	null,
	array(
		'redirect' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' ),
	)
);
