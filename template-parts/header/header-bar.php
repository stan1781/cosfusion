<?php
declare(strict_types=1);

if ( ! function_exists( 'tailwindscore_should_render_header_bar' ) || ! tailwindscore_should_render_header_bar() ) {
	return;
}

$ts_items   = tailwindscore_header_bar_items();
$ts_scheme  = tailwindscore_header_bar_scheme();
$ts_count   = count( $ts_items );
$ts_delay   = tailwindscore_header_bar_autoplay_delay();
$ts_classes = 'site-header-bar site-header-bar--' . $ts_scheme;
?>
<div
	class="<?php echo esc_attr( $ts_classes ); ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Announcement', 'tailwindscore' ); ?>"
	data-ts-header-bar
	<?php if ( $ts_count > 1 ) : ?>
		data-ts-autoplay-delay="<?php echo esc_attr( (string) $ts_delay ); ?>"
	<?php endif; ?>
>
	<div class="container site-header-bar__inner">
		<?php if ( 1 === $ts_count ) : ?>
			<?php tailwindscore_render_header_bar_message( $ts_items[0] ); ?>
		<?php else : ?>
			<div class="swiper site-header-bar__swiper">
				<div class="swiper-wrapper">
					<?php foreach ( $ts_items as $ts_item ) : ?>
						<div class="swiper-slide site-header-bar__slide">
							<?php tailwindscore_render_header_bar_message( $ts_item ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
