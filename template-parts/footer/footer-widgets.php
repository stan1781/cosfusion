<?php
declare(strict_types=1);

$ts_footer_sidebars = array( 'footer-1', 'footer-2', 'footer-3', 'footer-4' );
$ts_has_footer_widgets = false;

foreach ( $ts_footer_sidebars as $ts_footer_sidebar ) {
	if ( is_active_sidebar( $ts_footer_sidebar ) ) {
		$ts_has_footer_widgets = true;
		break;
	}
}

if ( ! $ts_has_footer_widgets ) {
	return;
}
?>
<div class="ts-site-footer__content">
	<?php foreach ( $ts_footer_sidebars as $ts_footer_sidebar ) : ?>
		<div class="ts-site-footer__column">
			<?php dynamic_sidebar( $ts_footer_sidebar ); ?>
		</div>
	<?php endforeach; ?>
</div>
