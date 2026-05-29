<?php
declare(strict_types=1);

/**
 * Checkout coupon form
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) {
	return;
}

tailwindscore_render_coupon_form(
	array(
		'context' => 'checkout',
	)
);
