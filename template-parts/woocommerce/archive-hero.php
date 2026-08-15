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
		<?php do_action( 'woocommerce_archive_description' ); ?>
	</div>
</section>
