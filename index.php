<?php
declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main ts-main">
	<div class="ts-container ts-section">
		<div class="ts-content-shell<?php echo tailwindscore_has_sidebar() ? ' ts-content-shell--has-sidebar' : ''; ?>">
			<section class="ts-content-main">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'ts-entry ts-stack' ); ?>>
							<header class="ts-entry__header ts-stack">
								<h1 class="ts-heading"><?php the_title(); ?></h1>
							</header>
							<div class="entry-content ts-entry__content">
								<?php the_content(); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<section class="ts-empty-state ts-stack">
						<h1 class="ts-heading"><?php esc_html_e( 'Nothing here yet.', 'tailwindscore' ); ?></h1>
						<p><?php esc_html_e( 'No content found.', 'tailwindscore' ); ?></p>
					</section>
				<?php endif; ?>
			</section>
			<?php if ( tailwindscore_has_sidebar() ) : ?>
				<?php get_sidebar(); ?>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
