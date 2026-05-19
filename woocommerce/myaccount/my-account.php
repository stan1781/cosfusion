<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="ts-grid" style="grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr); align-items: start;">
	<nav class="ts-card">
		<?php do_action( 'woocommerce_account_navigation' ); ?>
	</nav>
	<section class="ts-card">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</section>
</div>

