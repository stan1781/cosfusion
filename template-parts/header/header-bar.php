<?php
declare(strict_types=1);

if ( ! tailwindscore_should_render_header_bar() ) {
	return;
}

$ts_bar_text  = tailwindscore_header_bar_text();
$ts_bar_url   = tailwindscore_header_bar_link_url();
$ts_bar_label = tailwindscore_header_bar_link_label();
?>
<div class="site-header-bar" role="region" aria-label="<?php esc_attr_e( 'Announcement', 'tailwindscore' ); ?>">
	<div class="container site-header-bar__inner">
		<p class="site-header-bar__message">
			<span class="site-header-bar__text"><?php echo esc_html( $ts_bar_text ); ?></span>
			<?php if ( '' !== $ts_bar_url ) : ?>
				<?php
				$ts_link_label = '' !== $ts_bar_label
					? $ts_bar_label
					: __( 'Learn more', 'tailwindscore' );
				?>
				<a class="site-header-bar__link" href="<?php echo esc_url( $ts_bar_url ); ?>">
					<?php echo esc_html( $ts_link_label ); ?>
				</a>
			<?php endif; ?>
		</p>
	</div>
</div>
