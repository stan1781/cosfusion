<?php
declare(strict_types=1);

$ts_site_name               = tailwindscore_site_name();
$ts_use_footer_info_sidebar = is_active_sidebar( 'footer-info' );
$ts_footer_info_widget_count = 0;

if ( $ts_use_footer_info_sidebar ) {
	$ts_sidebars_widgets         = wp_get_sidebars_widgets();
	$ts_footer_info_widgets      = $ts_sidebars_widgets['footer-info'] ?? array();
	$ts_footer_info_widget_count = count( $ts_footer_info_widgets );
}

$ts_footer_info_classes  = 'ts-site-footer__info';
$ts_footer_info_classes .= ( ! $ts_use_footer_info_sidebar || $ts_footer_info_widget_count <= 1 )
	? ' ts-site-footer__info--centered'
	: ' ts-site-footer__info--split';
?>
<div class="<?php echo esc_attr( $ts_footer_info_classes ); ?>">
	<?php if ( $ts_use_footer_info_sidebar ) : ?>
		<?php dynamic_sidebar( 'footer-info' ); ?>
	<?php else : ?>
		<section class="widget ts-site-footer__widget ts-stack">
			<p>
				<?php
				printf(
					/* translators: 1: year, 2: site name */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'tailwindscore' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( $ts_site_name )
				);
				?>
			</p>
		</section>
	<?php endif; ?>
</div>
