<?php
declare(strict_types=1);

if ( ! tailwindscore_should_render_footer_nav() ) {
	return;
}

$ts_footer_nav_classes = 'site-footer__nav ' . tailwindscore_footer_nav_alignment_class();
?>
<div class="<?php echo esc_attr( $ts_footer_nav_classes ); ?>">
	<nav class="site-footer__nav-inner" aria-label="<?php esc_attr_e( 'Footer Menu', 'tailwindscore' ); ?>">
		<?php wp_nav_menu( tailwindscore_footer_nav_menu_args() ); ?>
	</nav>
</div>
