<?php
declare(strict_types=1);

$args = wp_parse_args(
	$args ?? array(),
	array(
		'nav_id'    => 'ts-primary-nav',
		'nav_class' => 'ts-nav',
	)
);

if ( has_nav_menu( 'primary' ) ) {
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => 'nav',
			'container_id'   => $args['nav_id'],
			'container_class'=> $args['nav_class'],
			'menu_class'     => 'ts-nav__list',
			'fallback_cb'    => false,
		)
	);
	return;
}
?>
<nav id="<?php echo esc_attr( $args['nav_id'] ); ?>" class="<?php echo esc_attr( $args['nav_class'] ); ?>" aria-label="<?php esc_attr_e( 'Primary navigation', 'tailwindscore' ); ?>">
	<ul class="ts-nav__list">
		<?php wp_list_pages( array( 'title_li' => '' ) ); ?>
	</ul>
</nav>
