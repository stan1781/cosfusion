<?php
declare(strict_types=1);

if ( ! tailwindscore_has_sidebar() ) {
	return;
}
?>
<aside class="ts-sidebar ts-content-sidebar">
	<div class="ts-sidebar__content ts-stack">
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	</div>
</aside>
