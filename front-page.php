<?php
declare(strict_types=1);

/**
 * Front page template (Kirki-driven homepage).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ts_hero_slides     = tailwindscore_homepage_hero_slides();
$ts_products_title  = tailwindscore_homepage_products_title();
$ts_products_output = tailwindscore_homepage_should_render_products()
	? do_shortcode( tailwindscore_homepage_products_shortcode() )
	: '';
?>
<main id="primary" class="site-main ts-main ts-home">
	<?php if ( ! empty( $ts_hero_slides ) ) : ?>
		<section class="ts-home-hero" aria-label="<?php esc_attr_e( 'Hero', 'tailwindscore' ); ?>">
			<div class="swiper ts-home-hero__swiper" data-ts-home-hero>
				<div class="swiper-wrapper">
					<?php foreach ( $ts_hero_slides as $ts_slide_index => $ts_slide ) : ?>
						<?php
						$ts_slide_alt = '' !== $ts_slide['title']
							? $ts_slide['title']
							: __( 'Homepage banner', 'tailwindscore' );
						$ts_link_url   = $ts_slide['link_url'];
						$ts_link_label = '' !== $ts_slide['link_label']
							? $ts_slide['link_label']
							: __( 'Shop now', 'tailwindscore' );
						?>
						<div class="swiper-slide">
							<article class="ts-home-hero__slide ts-home-hero__slide--text-<?php echo esc_attr( $ts_slide['text_color'] ); ?>">
								<div class="ts-home-hero__media">
									<?php
									echo tailwindscore_homepage_hero_slide_image_markup( $ts_slide, $ts_slide_index, $ts_slide_alt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper.
									?>
								</div>
								<?php
								$ts_has_hero_copy = '' !== $ts_slide['subtitle']
									|| '' !== $ts_slide['title']
									|| '' !== $ts_slide['description']
									|| '' !== $ts_link_url;
								?>
								<?php if ( $ts_has_hero_copy ) : ?>
									<div class="ts-container ts-home-hero__content">
										<div class="ts-home-hero__copy">
											<?php if ( '' !== $ts_slide['subtitle'] ) : ?>
												<p class="ts-home-hero__eyebrow"><?php echo esc_html( $ts_slide['subtitle'] ); ?></p>
											<?php endif; ?>
											<?php if ( '' !== $ts_slide['title'] ) : ?>
												<h1 class="ts-home-hero__title"><?php echo esc_html( $ts_slide['title'] ); ?></h1>
											<?php endif; ?>
											<?php if ( '' !== $ts_slide['description'] ) : ?>
												<p class="ts-home-hero__description"><?php echo esc_html( $ts_slide['description'] ); ?></p>
											<?php endif; ?>
											<?php if ( '' !== $ts_link_url ) : ?>
												<p class="ts-home-hero__actions">
													<a
														class="ts-button ts-home-hero__button ts-home-hero__button--<?php echo esc_attr( $ts_slide['link_style'] ); ?>"
														href="<?php echo esc_url( $ts_link_url ); ?>"
													>
														<?php echo esc_html( $ts_link_label ); ?>
													</a>
												</p>
											<?php endif; ?>
										</div>
									</div>
								<?php endif; ?>
							</article>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="swiper-pagination ts-home-hero__pagination"></div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $ts_products_output ) : ?>
		<section class="ts-home-products ts-section" aria-labelledby="ts-home-products-title">
			<div class="ts-container ts-stack">
				<div class="ts-home-products__header">
					<?php if ( '' !== $ts_products_title ) : ?>
						<h2 id="ts-home-products-title" class="ts-heading">
							<?php echo esc_html( $ts_products_title ); ?>
						</h2>
					<?php endif; ?>
					<div class="ts-home-products__nav-group" data-ts-home-products-nav hidden>
						<button
							type="button"
							class="ts-home-products__nav ts-home-products__nav--prev"
							data-ts-home-products-prev
							aria-label="<?php esc_attr_e( 'Previous products', 'tailwindscore' ); ?>"
						>
							<span class="ts-home-products__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</button>
						<button
							type="button"
							class="ts-home-products__nav ts-home-products__nav--next"
							data-ts-home-products-next
							aria-label="<?php esc_attr_e( 'Next products', 'tailwindscore' ); ?>"
						>
							<span class="ts-home-products__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</button>
					</div>
				</div>
				<div class="ts-home-products__content">
					<div class="ts-home-products__viewport">
						<div class="swiper ts-home-products__swiper" data-ts-home-products>
							<?php echo $ts_products_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode HTML. ?>
						</div>
					</div>
					<div class="ts-home-products__footer" data-ts-home-products-controls hidden>
						<div class="swiper-pagination ts-home-products__pagination" data-ts-home-products-pagination></div>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
