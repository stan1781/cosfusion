<?php
/**
 * 分栏 Hero：桌面左文右图，移动端纵向堆叠。
 *
 * @var array<string, mixed> $args
 */
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$eyebrow   = isset( $args['eyebrow'] ) ? trim( (string) $args['eyebrow'] ) : '';
$title     = isset( $args['title'] ) ? trim( (string) $args['title'] ) : '';
$text      = isset( $args['text'] ) ? trim( (string) $args['text'] ) : '';
$cta_label = isset( $args['cta_label'] ) ? trim( (string) $args['cta_label'] ) : '';
$cta_url   = isset( $args['cta_url'] ) ? trim( (string) $args['cta_url'] ) : '';
$image_id  = isset( $args['image_id'] ) ? (int) $args['image_id'] : 0;
$image_url = isset( $args['image_url'] ) ? trim( (string) $args['image_url'] ) : '';

if ( '' === $title ) {
	return;
}

if ( '' === $cta_url && function_exists( 'wc_get_page_permalink' ) ) {
	$shop = wc_get_page_permalink( 'shop' );
	$cta_url = is_string( $shop ) ? $shop : '';
}
if ( '' === $cta_url ) {
	$cta_url = home_url( '/' );
}

$has_image = ( $image_id > 0 ) || ( '' !== $image_url );
$img_html  = '';

if ( $has_image && function_exists( 'tailwindscore_hero_render_responsive_image' ) ) {
	$img_html = tailwindscore_hero_render_responsive_image(
		$image_id,
		$image_url,
		array(
			'class'         => 'home-split-hero__img',
			'alt'           => $title,
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
			'sizes'         => '(max-width: 63.999rem) 100vw, min(50vw, 36rem)',
		)
	);
} elseif ( $has_image && '' !== $image_url ) {
	$img_html = sprintf(
		'<img class="home-split-hero__img" src="%1$s" alt="%2$s" loading="eager" fetchpriority="high" decoding="async">',
		esc_url( $image_url ),
		esc_attr( $title )
	);
}
?>
<section class="home-split-hero" data-ts-home-split-hero>
	<div class="container home-split-hero__inner">
		<div class="home-split-hero__content">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="home-split-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="home-split-hero__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( '' !== $text ) : ?>
				<p class="home-split-hero__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $cta_label ) : ?>
				<p class="home-split-hero__actions">
					<a class="btn btn-primary home-split-hero__cta" href="<?php echo esc_url( $cta_url ); ?>">
						<?php echo esc_html( $cta_label ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $img_html ) : ?>
			<div class="home-split-hero__media">
				<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php else : ?>
			<div class="home-split-hero__media home-split-hero__media--placeholder" aria-hidden="true"></div>
		<?php endif; ?>
	</div>
</section>
