<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$background_url = tailwindscore_wc_archive_hero_background_url();
$hero_classes   = 'wc-archive-hero';

if ( '' === $background_url ) {
	$hero_classes .= ' wc-archive-hero--no-media';
}

$style_attr = '' !== $background_url
	? sprintf( ' style="--ts-wc-archive-hero-bg: url(%s);"', esc_url( $background_url ) )
	: '';
?>
<section class="<?php echo esc_attr( $hero_classes ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wc-archive-hero__overlay" aria-hidden="true"></div>
	<div class="container wc-archive-hero__inner">
		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
			<h1 class="wc-archive-hero__title heading"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>
		<?php do_action( 'woocommerce_archive_description' ); ?>
	</div>
</section>
