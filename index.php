<?php
declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<div class="ts-container ts-section">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'ts-stack' ); ?>>
					<h1 class="ts-heading"><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No content found.', 'tailwindscore' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

