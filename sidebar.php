<?php
declare(strict_types=1);

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside class="ts-sidebar">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>

