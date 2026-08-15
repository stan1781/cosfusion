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
$layout          = isset( $args['layout'] ) ? tailwindscore_sanitize_hero_layout( $args['layout'] ) : 'full';
$columns_mobile  = isset( $args['columns_mobile'] ) ? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_mobile'] ) : 1.0;
$columns_desktop = isset( $args['columns_desktop'] ) ? tailwindscore_sanitize_hero_carousel_slide_count( $args['columns_desktop'] ) : 1.0;
$gap             = isset( $args['gap'] )
	? ( function_exists( 'tailwindscore_sanitize_products_carousel_gap' )
		? tailwindscore_sanitize_products_carousel_gap( $args['gap'] )
		: max( 0, min( 48, (int) $args['gap'] ) ) )
	: 0;
$section_class = isset( $args['section_class'] ) ? trim( (string) $args['section_class'] ) : '';
$section_class = trim( 'hero-carousel hero-carousel--' . $layout . ' ' . $section_class );

$slide_count = count( $slides );
$hero_sizes  = '(max-width: 47.999rem) 100vw, min(100vw, 1200px)';
$is_peek     = 'peek' === $layout;
?>
<section
	class="<?php echo esc_attr( $section_class ); ?>"
	data-ts-hero-carousel
	data-ts-layout="<?php echo esc_attr( $layout ); ?>"
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
				$image           = (string) ( $slide['image'] ?? '' );
				$image_id        = isset( $slide['image_id'] ) ? (int) $slide['image_id'] : 0;
				$image_mobile    = (string) ( $slide['image_mobile'] ?? '' );
				$image_mobile_id = isset( $slide['image_mobile_id'] ) ? (int) $slide['image_mobile_id'] : 0;
				$title           = (string) ( $slide['title'] ?? '' );
				$link_url        = (string) ( $slide['link_url'] ?? '' );
				$attachment_alt  = $image_id > 0
					? sanitize_text_field( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) )
					: '';
				$accessible_name = '' !== $title
					? $title
					: ( '' !== $attachment_alt ? $attachment_alt : __( 'Promotional banner', 'tailwindscore' ) );
				$has_link        = '' !== $link_url;
				$is_first        = 0 === (int) $index;

				$img_attrs = array(
					'class'    => 'hero-carousel__img',
					// 有链接时名称放在 <a aria-label>，图用空 alt 避免读两遍。
					'alt'      => $has_link ? '' : $accessible_name,
					'decoding' => 'async',
					'sizes'    => $hero_sizes,
					'loading'  => $is_first ? 'eager' : 'lazy',
				);
				if ( $is_first ) {
					$img_attrs['fetchpriority'] = 'high';
				}
				?>
				<div class="swiper-slide hero-carousel__slide">
					<?php if ( $has_link ) : ?>
						<a
							class="hero-carousel__link"
							href="<?php echo esc_url( $link_url ); ?>"
							aria-label="<?php echo esc_attr( $accessible_name ); ?>"
						>
					<?php endif; ?>

					<figure
						class="hero-carousel__media"
						<?php
						if ( $is_peek ) {
							$media_style = tailwindscore_hero_media_aspect_ratio_style( $image_id, $image_mobile_id );
							if ( '' !== $media_style ) {
								echo ' style="' . esc_attr( $media_style ) . '"';
							}
						}
						?>
					>
						<?php if ( $image_mobile_id > 0 || '' !== $image_mobile ) : ?>
							<?php
							$mobile_pair = tailwindscore_hero_attachment_srcset_pair( $image_mobile_id, $image_mobile );
							?>
							<picture>
								<?php if ( '' !== $mobile_pair['srcset'] || '' !== $mobile_pair['src'] ) : ?>
									<source
										media="(width < 48rem)"
										srcset="<?php echo esc_attr( '' !== $mobile_pair['srcset'] ? $mobile_pair['srcset'] : $mobile_pair['src'] ); ?>"
										sizes="100vw"
									>
								<?php endif; ?>
								<?php echo tailwindscore_hero_render_responsive_image( $image_id, $image, $img_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper. ?>
							</picture>
						<?php else : ?>
							<?php echo tailwindscore_hero_render_responsive_image( $image_id, $image, $img_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper. ?>
						<?php endif; ?>

						<?php if ( '' !== $title && ! $has_link ) : ?>
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
