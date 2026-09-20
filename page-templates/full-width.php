<?php
/**
 * Template Name: Full Width
 * Template Post Type: page
 * Description: No site-container width constraint; suited to custom HTML and Content Blocks.
 */
declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main site-main--full-width">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'page-full-width' ); ?>>
			<div class="ts-content-root">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
