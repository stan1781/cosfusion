<?php
declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main main">
	<div class="container section">
		<div class="content-shell<?php echo tailwindscore_has_sidebar() ? ' content-shell--has-sidebar' : ''; ?>">
			<section class="content-main">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<?php
						$ts_entry_classes = array( 'entry', 'stack' );
						if ( is_singular( 'post' ) ) {
							$ts_entry_classes[] = 'entry--post';
						}
						?>
						<article <?php post_class( $ts_entry_classes ); ?>>
							<?php if ( tailwindscore_show_entry_header() ) : ?>
								<?php get_template_part( 'template-parts/content/entry-header' ); ?>
							<?php endif; ?>
							<div class="entry-content entry__content ts-content-root">
								<?php the_content(); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<section class="empty-state stack">
						<h1 class="heading"><?php esc_html_e( 'Nothing here yet.', 'tailwindscore' ); ?></h1>
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
