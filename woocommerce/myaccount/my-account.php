<?php
declare(strict_types=1);

/**
 * 我的账户页面
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( tailwindscore_is_guest_account_page() ) :
	do_action( 'woocommerce_account_content' );
	return;
endif;
?>
<div class="wc-account">
	<nav class="wc-account__nav wc-account-nav">
		<?php do_action( 'woocommerce_account_navigation' ); ?>
	</nav>
	<section class="wc-account__content">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</section>
</div>
