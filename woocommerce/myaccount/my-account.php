<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="ts-wc-account">
	<nav class="ts-card ts-wc-account__nav">
		<?php do_action( 'woocommerce_account_navigation' ); ?>
	</nav>
	<section class="ts-card ts-wc-account__content">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</section>
</div>
