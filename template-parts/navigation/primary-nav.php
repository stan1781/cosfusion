<?php
declare(strict_types=1);

$args = wp_parse_args(
	$args ?? array(),
	array(
		'nav_id'    => 'primary-nav',
		'nav_class' => 'nav',
	)
);

if ( has_nav_menu( 'primary' ) ) {
	wp_nav_menu( tailwindscore_primary_nav_menu_args( $args ) );
	return;
}
?>
<nav id="<?php echo esc_attr( $args['nav_id'] ); ?>" class="<?php echo esc_attr( $args['nav_class'] ); ?>" aria-label="<?php esc_attr_e( 'Primary navigation', 'tailwindscore' ); ?>">
	<ul class="nav__list">
		<?php wp_list_pages( array( 'title_li' => '' ) ); ?>
	</ul>
</nav>
