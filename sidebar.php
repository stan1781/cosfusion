<?php
declare(strict_types=1);

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside class="ts-sidebar ts-content-sidebar">
	<div class="ts-sidebar__inner ts-stack">
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	</div>
</aside>
