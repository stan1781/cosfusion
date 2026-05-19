<?php
declare(strict_types=1);

if ( has_nav_menu( 'primary' ) ) {
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => 'nav',
			'container_id'   => 'ts-primary-nav',
			'container_class'=> 'ts-nav',
			'menu_class'     => 'ts-nav__list',
			'fallback_cb'    => false,
		)
	);
	return;
}
?>
<nav id="ts-primary-nav" class="ts-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'tailwindscore' ); ?>">
	<ul class="ts-nav__list">
		<?php wp_list_pages( array( 'title_li' => '' ) ); ?>
	</ul>
</nav>
