<?php
declare(strict_types=1);

/**
 * 单篇文章模板：主列（正文 + 相关）+ Post Sidebar（桌面内联 / 移动 Drawer）。
 *
 * @package TailwindScore
 */

get_header();

$ts_has_post_sidebar = tailwindscore_has_post_sidebar();
$ts_shell_class      = 'content-shell';

if ( $ts_has_post_sidebar ) {
	$ts_shell_class .= ' content-shell--has-sidebar content-shell--has-post-sidebar';
}
?>
<main id="primary" class="site-main main"<?php echo $ts_has_post_sidebar ? ' data-ts-post-layout' : ''; ?>>
	<div class="container section">
		<div class="<?php echo esc_attr( $ts_shell_class ); ?>">
			<section class="content-main">
				<?php if ( $ts_has_post_sidebar ) : ?>
					<div class="post-toolbar">
						<?php get_template_part( 'template-parts/content/post-sidebar-toggle' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<article <?php post_class( array( 'entry', 'stack', 'entry--post' ) ); ?>>
							<?php if ( tailwindscore_show_entry_header() ) : ?>
								<?php get_template_part( 'template-parts/content/entry-header' ); ?>
							<?php endif; ?>
							<div class="entry-content entry__content">
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

				<?php get_template_part( 'template-parts/content/post-related' ); ?>
			</section>

			<?php if ( $ts_has_post_sidebar ) : ?>
				<?php get_sidebar( 'post' ); ?>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $ts_has_post_sidebar ) : ?>
		<?php get_template_part( 'template-parts/content/post-sidebar-drawer' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();
