<?php
declare(strict_types=1);

if ( ! tailwindscore_has_sidebar() ) {
	return;
}
?>
<aside class="sidebar content-sidebar">
	<div class="sidebar__content stack">
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	</div>
</aside>
