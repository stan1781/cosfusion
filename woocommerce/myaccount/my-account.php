<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( tailwindscore_is_guest_account_page() ) :
	do_action( 'woocommerce_account_content' );
	return;
endif;
?>
<div class="ts-wc-account">
	<nav class="ts-card ts-wc-account__nav">
		<?php do_action( 'woocommerce_account_navigation' ); ?>
	</nav>
	<section class="ts-card ts-wc-account__content">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</section>
</div>
