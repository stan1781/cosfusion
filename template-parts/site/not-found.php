<?php
declare(strict_types=1);

/**
 * 404 页三段式 markup：Hero、快捷导航、人气商品。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$illustration_url = tailwindscore_get_not_found_illustration_url();
$categories       = tailwindscore_get_not_found_categories();
?>
<section class="not-found__hero" aria-labelledby="ts-not-found-title">
	<div class="container not-found__hero-inner">
		<?php if ( '' !== $illustration_url ) : ?>
			<div class="not-found__hero-media">
				<img
					class="not-found__illustration"
					src="<?php echo esc_url( $illustration_url ); ?>"
					alt=""
					width="480"
					height="360"
					decoding="async"
				/>
			</div>
		<?php endif; ?>
		<div class="not-found__hero-copy">
			<h1 id="ts-not-found-title" class="not-found__title"><?php echo esc_html( tailwindscore_get_not_found_title() ); ?></h1>
			<?php if ( '' !== trim( tailwindscore_get_not_found_description() ) ) : ?>
				<p class="not-found__description"><?php echo esc_html( tailwindscore_get_not_found_description() ); ?></p>
			<?php endif; ?>
			<p class="not-found__actions">
				<a class="btn btn-primary not-found__home-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="not-found__home-btn-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html( tailwindscore_get_not_found_home_button_label() ); ?></span>
				</a>
			</p>
		</div>
	</div>
</section>

<?php if ( array() !== $categories ) : ?>
	<section class="not-found__section not-found__categories" aria-labelledby="ts-not-found-categories-title">
		<div class="container">
			<?php if ( '' !== trim( tailwindscore_get_not_found_categories_title() ) ) : ?>
				<h2 id="ts-not-found-categories-title" class="heading not-found__section-title">
					<?php echo esc_html( tailwindscore_get_not_found_categories_title() ); ?>
				</h2>
			<?php endif; ?>
			<ul class="not-found-categories">
				<?php foreach ( $categories as $category ) : ?>
					<li class="not-found-categories__item">
						<a class="not-found-categories__card" href="<?php echo esc_url( (string) $category['url'] ); ?>">
							<span class="not-found-categories__icon" aria-hidden="true">
								<?php echo tailwindscore_icon( (string) $category['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="not-found-categories__text">
								<span class="not-found-categories__title"><?php echo esc_html( (string) $category['title'] ); ?></span>
								<?php if ( '' !== (string) $category['subtitle'] ) : ?>
									<span class="not-found-categories__subtitle"><?php echo esc_html( (string) $category['subtitle'] ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<?php if ( tailwindscore_not_found_should_show_products() ) : ?>
	<section class="not-found__section not-found__products">
		<div class="container">
			<?php tailwindscore_render_not_found_product_carousel(); ?>
		</div>
	</section>
<?php endif; ?>
