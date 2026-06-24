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
$ts_footer_info_classes .= ( 0 === $ts_footer_info_widget_count )
	? ' ts-site-footer__info--centered'
	: ' ts-site-footer__info--split';

$ts_footer_payment_image_id = tailwindscore_footer_payment_image_id();
$ts_has_payment_image       = $ts_footer_payment_image_id > 0;

$ts_copyright_classes = 'widget ts-site-footer__widget ts-site-footer__copyright';
if ( $ts_has_payment_image ) {
	$ts_copyright_classes .= ' ts-site-footer__copyright--has-icons';
} else {
	$ts_copyright_classes .= ' ts-stack';
}
?>
<div class="<?php echo esc_attr( $ts_footer_info_classes ); ?>">
	<section class="<?php echo esc_attr( $ts_copyright_classes ); ?>">
		<p class="ts-site-footer__copyright-text">
			<?php
			printf(
				/* translators: 1: year, 2: site name */
				esc_html__( 'Copyright %1$s %2$s. All rights reserved.', 'tailwindscore' ),
				esc_html( gmdate( 'Y' ) ),
				esc_html( $ts_site_name )
			);
			?>
		</p>
		<?php if ( $ts_has_payment_image ) : ?>
			<div class="ts-site-footer__payment-image">
				<?php
				echo wp_get_attachment_image(
					$ts_footer_payment_image_id,
					'full',
					false,
					array(
						'class'    => 'ts-site-footer__payment-image-img',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		<?php endif; ?>
	</section>
	<?php if ( $ts_use_footer_info_sidebar ) : ?>
		<?php dynamic_sidebar( 'footer-info' ); ?>
	<?php endif; ?>
</div>
