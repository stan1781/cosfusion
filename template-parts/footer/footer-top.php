<?php
declare(strict_types=1);

if ( ! is_active_sidebar( 'footer-top' ) ) {
	return;
}
?>
<div class="ts-site-footer__top">
	<?php dynamic_sidebar( 'footer-top' ); ?>
</div>
