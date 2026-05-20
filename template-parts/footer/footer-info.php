<?php
declare(strict_types=1);

if ( ! is_active_sidebar( 'footer-info' ) ) {
	return;
}

$ts_sidebars_widgets     = wp_get_sidebars_widgets();
$ts_footer_info_widgets  = $ts_sidebars_widgets['footer-info'] ?? array();
$ts_footer_info_classes  = 'ts-site-footer__info';
$ts_footer_info_classes .= count( $ts_footer_info_widgets ) <= 1
	? ' ts-site-footer__info--centered'
	: ' ts-site-footer__info--split';
?>
<div class="<?php echo esc_attr( $ts_footer_info_classes ); ?>">
	<?php dynamic_sidebar( 'footer-info' ); ?>
</div>
