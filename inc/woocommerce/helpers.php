<?php
declare(strict_types=1);

function tailwindscore_is_woo_page(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

