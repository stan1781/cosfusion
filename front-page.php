<?php
declare(strict_types=1);

/**
 * 首页模板（由 Kirki 驱动的首页）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ts_hero_slides      = tailwindscore_homepage_hero_slides();
$ts_occasion_title   = tailwindscore_homepage_occasion_title();
$ts_occasion_items   = tailwindscore_homepage_occasion_items();
$ts_trending_title   = tailwindscore_homepage_trending_title();
$ts_trending_items   = tailwindscore_homepage_trending_items();
$ts_products_title   = tailwindscore_homepage_products_title();
$ts_products_output  = tailwindscore_homepage_should_render_products()
	? do_shortcode( tailwindscore_homepage_products_shortcode() )
	: '';
$ts_shop_by_color_title = tailwindscore_homepage_shop_by_color_title();
$ts_shop_by_color_items = tailwindscore_homepage_shop_by_color_items();
?>
<main id="primary" class="site-main main home">
	<?php if ( ! empty( $ts_hero_slides ) ) : ?>
		<section class="home-hero" aria-label="<?php esc_attr_e( 'Hero', 'tailwindscore' ); ?>">
			<div class="swiper home-hero__swiper" data-ts-home-hero>
				<div class="swiper-wrapper">
					<?php foreach ( $ts_hero_slides as $ts_slide_index => $ts_slide ) : ?>
						<?php
						$ts_slide_alt = '' !== $ts_slide['title']
							? $ts_slide['title']
							: __( 'Homepage banner', 'tailwindscore' );
						$ts_link_url        = $ts_slide['link_url'];
						$ts_link_label      = '' !== $ts_slide['link_label']
							? $ts_slide['link_label']
							: __( 'Shop now', 'tailwindscore' );
						$ts_link_context      = '' !== $ts_slide['title']
							? $ts_slide['title']
							: $ts_slide['subtitle'];
						$ts_link_aria_label   = $ts_link_label;
						if ( '' !== $ts_link_context ) {
							$ts_link_aria_label = sprintf(
								/* translators: 1: call-to-action label, 2: slide heading or eyebrow */
								__( '%1$s: %2$s', 'tailwindscore' ),
								$ts_link_label,
								$ts_link_context
							);
						}
						?>
						<div class="swiper-slide">
							<article class="home-hero__slide home-hero__slide--text-<?php echo esc_attr( $ts_slide['text_color'] ); ?>">
								<div class="home-hero__media">
									<?php
									echo tailwindscore_homepage_hero_slide_image_markup( $ts_slide, $ts_slide_index, $ts_slide_alt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已在 helper 中转义。
									?>
								</div>
								<?php
								$ts_has_hero_copy = '' !== $ts_slide['subtitle']
									|| '' !== $ts_slide['title']
									|| '' !== $ts_slide['description']
									|| '' !== $ts_link_url;
								?>
								<?php if ( $ts_has_hero_copy ) : ?>
									<div class="container home-hero__content">
										<div class="home-hero__copy">
											<?php if ( '' !== $ts_slide['subtitle'] ) : ?>
												<p class="home-hero__eyebrow"><?php echo esc_html( $ts_slide['subtitle'] ); ?></p>
											<?php endif; ?>
											<?php if ( '' !== $ts_slide['title'] ) : ?>
												<h2 class="home-hero__title"><?php echo esc_html( $ts_slide['title'] ); ?></h2>
											<?php endif; ?>
											<?php if ( '' !== $ts_slide['description'] ) : ?>
												<p class="home-hero__description"><?php echo esc_html( $ts_slide['description'] ); ?></p>
											<?php endif; ?>
											<?php if ( '' !== $ts_link_url ) : ?>
												<p class="home-hero__actions">
													<a
														class="btn home-hero__button home-hero__button--<?php echo esc_attr( $ts_slide['link_style'] ); ?>"
														href="<?php echo esc_url( $ts_link_url ); ?>"
														aria-label="<?php echo esc_attr( $ts_link_aria_label ); ?>"
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
				<div class="swiper-pagination home-hero__pagination"></div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( tailwindscore_homepage_should_render_occasion() ) : ?>
		<?php $ts_occasion_count = count( $ts_occasion_items ); ?>
		<section class="home-occasion section" aria-labelledby="ts-home-occasion-title">
			<div class="container stack">
				<?php if ( '' !== $ts_occasion_title ) : ?>
					<h2 id="ts-home-occasion-title" class="home-section-title">
						<?php echo esc_html( $ts_occasion_title ); ?>
					</h2>
				<?php endif; ?>
				<ul
					class="home-occasion__grid"
					style="<?php echo esc_attr( tailwindscore_homepage_grid_style_attr( $ts_occasion_count ) ); ?>"
				>
				<?php foreach ( $ts_occasion_items as $ts_occasion_item ) : ?>
					<?php
					$ts_occasion_alt = '' !== $ts_occasion_item['title']
						? $ts_occasion_item['title']
						: __( 'Shop by occasion', 'tailwindscore' );
					$ts_occasion_href = $ts_occasion_item['link_url'];
					$ts_occasion_tag  = '' !== $ts_occasion_href ? 'a' : 'article';
					?>
					<li class="home-occasion__item">
					<<?php echo esc_attr( $ts_occasion_tag ); ?>
						class="home-occasion__card"
						<?php if ( '' !== $ts_occasion_href ) : ?>
							href="<?php echo esc_url( $ts_occasion_href ); ?>"
						<?php endif; ?>
					>
						<div class="home-occasion__media">
							<?php
							echo tailwindscore_homepage_grid_card_image_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已在 helper 中转义。
								$ts_occasion_item,
								'home-occasion__image',
								$ts_occasion_alt,
								tailwindscore_homepage_grid_image_sizes( $ts_occasion_count )
							);
							?>
						</div>
						<div class="home-occasion__overlay">
							<div class="home-occasion__copy">
								<?php if ( '' !== $ts_occasion_item['title'] ) : ?>
									<h3 class="home-occasion__title"><?php echo esc_html( $ts_occasion_item['title'] ); ?></h3>
								<?php endif; ?>
								<?php if ( '' !== $ts_occasion_item['subtitle'] ) : ?>
									<p class="home-occasion__subtitle"><?php echo esc_html( $ts_occasion_item['subtitle'] ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $ts_occasion_item['link_label'] ) : ?>
									<span class="btn home-occasion__button"><?php echo esc_html( $ts_occasion_item['link_label'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</<?php echo esc_attr( $ts_occasion_tag ); ?>>
					</li>
				<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( tailwindscore_homepage_should_render_trending() ) : ?>
		<?php $ts_trending_count = count( $ts_trending_items ); ?>
		<section class="home-trending section" aria-labelledby="ts-home-trending-title">
			<div class="container stack">
				<?php if ( '' !== $ts_trending_title ) : ?>
					<h2 id="ts-home-trending-title" class="home-section-title">
						<?php echo esc_html( $ts_trending_title ); ?>
					</h2>
				<?php endif; ?>
				<ul
					class="home-trending__grid"
					style="<?php echo esc_attr( tailwindscore_homepage_grid_style_attr( $ts_trending_count ) ); ?>"
				>
				<?php foreach ( $ts_trending_items as $ts_trending_item ) : ?>
					<?php
					$ts_trending_alt   = '' !== $ts_trending_item['title']
						? $ts_trending_item['title']
						: __( 'Trending collection', 'tailwindscore' );
					$ts_trending_href  = $ts_trending_item['link_url'];
					$ts_trending_tag   = '' !== $ts_trending_href ? 'a' : 'article';
					$ts_trending_lines = tailwindscore_homepage_split_trending_title( $ts_trending_item['title'] );
					?>
					<li class="home-trending__item">
					<<?php echo esc_attr( $ts_trending_tag ); ?>
						class="home-trending__card"
						<?php if ( '' !== $ts_trending_href ) : ?>
							href="<?php echo esc_url( $ts_trending_href ); ?>"
						<?php endif; ?>
					>
						<div class="home-trending__media">
							<?php
							echo tailwindscore_homepage_grid_card_image_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已在 helper 中转义。
								$ts_trending_item,
								'home-trending__image',
								$ts_trending_alt,
								tailwindscore_homepage_grid_image_sizes( $ts_trending_count )
							);
							?>
						</div>
						<div class="home-trending__overlay">
							<div class="home-trending__copy">
								<?php if ( '' !== $ts_trending_lines['line1'] || '' !== $ts_trending_lines['line2'] ) : ?>
									<h3 class="home-trending__title">
										<?php if ( '' !== $ts_trending_lines['line1'] ) : ?>
											<span class="home-trending__title-line home-trending__title-line--heading"><?php echo esc_html( $ts_trending_lines['line1'] ); ?></span>
										<?php endif; ?>
										<?php if ( '' !== $ts_trending_lines['line2'] ) : ?>
											<span class="home-trending__title-line home-trending__title-line--body"><?php echo esc_html( $ts_trending_lines['line2'] ); ?></span>
										<?php endif; ?>
									</h3>
								<?php endif; ?>
								<?php if ( '' !== $ts_trending_item['link_label'] ) : ?>
									<span class="home-trending__link"><?php echo esc_html( $ts_trending_item['link_label'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</<?php echo esc_attr( $ts_trending_tag ); ?>>
					</li>
				<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $ts_products_output ) : ?>
		<section class="home-products section" aria-labelledby="ts-home-products-title">
			<div class="container stack">
				<div class="home-products__header">
					<?php if ( '' !== $ts_products_title ) : ?>
						<h2 id="ts-home-products-title" class="heading">
							<?php echo esc_html( $ts_products_title ); ?>
						</h2>
					<?php endif; ?>
					<div class="home-products__nav-group" data-ts-home-products-nav hidden>
						<button
							type="button"
							class="home-products__nav home-products__nav--prev"
							data-ts-home-products-prev
							aria-label="<?php esc_attr_e( 'Previous products', 'tailwindscore' ); ?>"
						>
							<span class="home-products__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</button>
						<button
							type="button"
							class="home-products__nav home-products__nav--next"
							data-ts-home-products-next
							aria-label="<?php esc_attr_e( 'Next products', 'tailwindscore' ); ?>"
						>
							<span class="home-products__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</button>
					</div>
				</div>
				<div class="home-products__content">
					<div class="home-products__viewport">
						<div class="swiper home-products__swiper woocommerce" data-ts-home-products>
							<?php echo $ts_products_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 短代码 HTML。 ?>
						</div>
					</div>
					<div class="home-products__footer" data-ts-home-products-controls hidden>
						<div class="swiper-pagination home-products__pagination" data-ts-home-products-pagination></div>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- 按颜色选购 -->
	<?php if ( tailwindscore_homepage_should_render_shop_by_color() ) : ?>
		<section class="home-colors section" aria-labelledby="ts-home-colors-title">
			<div class="container stack">
				<?php if ( '' !== $ts_shop_by_color_title ) : ?>
					<h2 id="ts-home-colors-title" class="home-section-title">
						<?php echo esc_html( $ts_shop_by_color_title ); ?>
					</h2>
				<?php endif; ?>
				<ul class="home-colors__list">
					<?php foreach ( $ts_shop_by_color_items as $ts_color_item ) : ?>
						<li class="home-colors__item">
							<a class="home-colors__link" href="<?php echo esc_url( $ts_color_item['url'] ); ?>">
								<span
									class="home-colors__swatch<?php echo $ts_color_item['is_light'] ? ' home-colors__swatch--light' : ''; ?>"
									style="--ts-home-color-swatch: <?php echo esc_attr( $ts_color_item['hex'] ); ?>;"
								></span>
								<span class="home-colors__label"><?php echo esc_html( $ts_color_item['label'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
					<li class="home-colors__item home-colors__item--more">
						<span class="home-colors__divider" aria-hidden="true"></span>
						<a class="home-colors__link" href="<?php echo esc_url( tailwindscore_homepage_shop_by_color_more_url() ); ?>">
							<span class="home-colors__swatch home-colors__swatch--more">
								<span class="home-colors__more-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</span>
							<span class="home-colors__label"><?php esc_html_e( 'More', 'tailwindscore' ); ?></span>
						</a>
					</li>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( tailwindscore_homepage_should_render_trust() ) : ?>
		<?php $ts_trust_items = tailwindscore_homepage_trust_items(); ?>
		<section class="home-trust" aria-label="<?php esc_attr_e( 'Store benefits', 'tailwindscore' ); ?>">
			<div class="container">
				<ul class="home-trust__list">
					<?php foreach ( $ts_trust_items as $ts_trust_item ) : ?>
						<li class="home-trust__item">
							<div class="home-trust__card">
								<span class="home-trust__icon icon" aria-hidden="true"><?php echo tailwindscore_icon( $ts_trust_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<h3 class="home-trust__title"><?php echo esc_html( $ts_trust_item['title'] ); ?></h3>
								<?php if ( '' !== $ts_trust_item['description'] ) : ?>
									<p class="home-trust__description"><?php echo esc_html( $ts_trust_item['description'] ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

</main>
<?php
get_footer();
