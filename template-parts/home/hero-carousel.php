<?php
/**
 * Hero Swiper 壳层。
 *
 * @var array<string, mixed> $args Template arguments from get_template_part().
 */
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$slides = isset( $args['slides'] ) && is_array( $args['slides'] ) ? $args['slides'] : array();
$slides = tailwindscore_parse_hero_slides( $slides );

if ( array() === $slides ) {
	return;
}

$autoplay        = ! empty( $args['autoplay'] );
$autoplay_delay  = isset( $args['autoplay_delay'] ) ? tailwindscore_sanitize_hero_autoplay_delay( $args['autoplay_delay'] ) : 5000;
$columns_mobile  = isset( $args['columns_mobile'] ) ? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_mobile'] ) : 1.0;
$columns_desktop = isset( $args['columns_desktop'] ) ? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_desktop'] ) : 1.0;
$gap             = isset( $args['gap'] )
	? ( function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
		? tailwindscore_sanitize_products_carousel_gap( $args['gap'] )
		: max( 0, min( 48, (int) $args['gap'] ) ) )
	: 0;
$section_class = isset( $args['section_class'] ) ? trim( (string) $args['section_class'] ) : '';
$section_class = trim( 'hero-carousel ' . $section_class );

$slide_count = count( $slides );
?>
<section
	class="<?php echo esc_attr( $section_class ); ?>"
	data-ts-hero-carousel
	data-ts-columns-mobile="<?php echo esc_attr( (string) $columns_mobile ); ?>"
	data-ts-columns-desktop="<?php echo esc_attr( (string) $columns_desktop ); ?>"
	data-ts-gap="<?php echo esc_attr( (string) $gap ); ?>"
	data-ts-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
	data-ts-autoplay-delay="<?php echo esc_attr( (string) $autoplay_delay ); ?>"
	data-ts-slide-count="<?php echo esc_attr( (string) $slide_count ); ?>"
>
	<div class="swiper hero-carousel__swiper">
		<div class="swiper-wrapper">
			<?php foreach ( $slides as $index => $slide ) : ?>
				<?php
				$image        = (string) ( $slide['image'] ?? '' );
				$image_mobile = (string) ( $slide['image_mobile'] ?? '' );
				$title        = (string) ( $slide['title'] ?? '' );
				$link_url     = (string) ( $slide['link_url'] ?? '' );
				$link_target  = (string) ( $slide['link_target'] ?? '_self' );
				$alt          = (string) ( $slide['alt'] ?? '' );
				if ( '' === $alt && '' !== $title ) {
					$alt = $title;
				}
				$has_link = '' !== $link_url;
				?>
				<div class="swiper-slide hero-carousel__slide">
					<?php if ( $has_link ) : ?>
						<a
							class="hero-carousel__link"
							href="<?php echo esc_url( $link_url ); ?>"
							<?php echo '_blank' === $link_target ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
						>
					<?php endif; ?>

					<figure class="hero-carousel__media">
						<?php if ( '' !== $image_mobile ) : ?>
							<picture>
								<source media="(width < 48rem)" srcset="<?php echo esc_url( $image_mobile ); ?>">
								<img
									class="hero-carousel__img"
									src="<?php echo esc_url( $image ); ?>"
									alt="<?php echo esc_attr( $alt ); ?>"
									<?php echo 0 === (int) $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
									decoding="async"
								>
							</picture>
						<?php else : ?>
							<img
								class="hero-carousel__img"
								src="<?php echo esc_url( $image ); ?>"
								alt="<?php echo esc_attr( $alt ); ?>"
								<?php echo 0 === (int) $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
								decoding="async"
							>
						<?php endif; ?>

						<?php if ( '' !== $title ) : ?>
							<figcaption class="hero-carousel__caption screen-reader-text">
								<?php echo esc_html( $title ); ?>
							</figcaption>
						<?php endif; ?>
					</figure>

					<?php if ( $has_link ) : ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="hero-carousel__pagination swiper-pagination" data-ts-pagination></div>
	</div>
	<div class="hero-carousel__nav-group" data-ts-hero-carousel-nav <?php echo $slide_count <= 1 ? 'hidden' : ''; ?>>
		<button
			type="button"
			class="hero-carousel__nav hero-carousel__nav--prev"
			data-ts-hero-carousel-prev
			aria-label="<?php esc_attr_e( 'Previous slide', 'tailwindscore' ); ?>"
		>
			<span class="hero-carousel__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
		</button>
		<button
			type="button"
			class="hero-carousel__nav hero-carousel__nav--next"
			data-ts-hero-carousel-next
			aria-label="<?php esc_attr_e( 'Next slide', 'tailwindscore' ); ?>"
		>
			<span class="hero-carousel__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
		</button>
	</div>
</section>
