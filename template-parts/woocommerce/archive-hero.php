<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$attachment_id = function_exists( 'tailwindscore_wc_archive_hero_attachment_id' )
	? tailwindscore_wc_archive_hero_attachment_id()
	: 0;
$has_media     = $attachment_id > 0;
$hero_classes  = 'wc-archive-hero';

if ( ! $has_media ) {
	$hero_classes .= ' wc-archive-hero--no-media';
}

$img_html = '';
if ( $has_media && function_exists( 'tailwindscore_hero_render_responsive_image' ) ) {
	$img_html = tailwindscore_hero_render_responsive_image(
		$attachment_id,
		'',
		array(
			'class'         => 'wc-archive-hero__img',
			'alt'           => '',
			'loading'       => 'eager',
			'decoding'      => 'async',
			'fetchpriority' => 'high',
			'sizes'         => '100vw',
		)
	);
}

ob_start();
do_action( 'woocommerce_archive_description' );
$archive_description = trim( (string) ob_get_clean() );
?>
<section class="<?php echo esc_attr( $hero_classes ); ?>">
	<?php if ( '' !== $img_html ) : ?>
		<div class="wc-archive-hero__media" aria-hidden="true">
			<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper. ?>
		</div>
	<?php endif; ?>
	<div class="wc-archive-hero__overlay" aria-hidden="true"></div>
	<div class="container wc-archive-hero__inner">
		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
			<h1 class="wc-archive-hero__title heading"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>
		<?php if ( '' !== $archive_description ) : ?>
			<div class="wc-archive-hero__desc is-clamped" data-ts-archive-desc>
				<?php echo $archive_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce archive description markup. ?>
				<button
					type="button"
					class="wc-archive-hero__desc-toggle"
					hidden
					aria-expanded="false"
					data-label-more="<?php esc_attr_e( 'More', 'tailwindscore' ); ?>"
					data-label-less="<?php esc_attr_e( 'Less', 'tailwindscore' ); ?>"
				><?php esc_html_e( 'More', 'tailwindscore' ); ?></button>
			</div>
		<?php endif; ?>
	</div>
</section>
