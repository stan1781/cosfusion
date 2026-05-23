<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( tailwindscore_is_guest_account_page() ) :
	do_action( 'woocommerce_account_content' );
	return;
endif;
?>
<div class="ts-wc-account">
	<nav class="ts-wc-account__nav ts-wc-account-nav">
		<?php do_action( 'woocommerce_account_navigation' ); ?>
	</nav>
	<section class="ts-wc-account__content ts-stack">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</section>
</div>
