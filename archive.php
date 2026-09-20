<?php
declare(strict_types=1);

/**
 * 博客归档（分类 / 标签 / 日期 / 作者）：网格列表。
 *
 * @package TailwindScore
 */

get_header();

$ts_has_sidebar = tailwindscore_has_sidebar();
$ts_shell_class = 'content-shell';

if ( $ts_has_sidebar ) {
	$ts_shell_class .= ' content-shell--has-sidebar';
}
?>
<main id="primary" class="site-main main">
	<div class="container section">
		<div class="<?php echo esc_attr( $ts_shell_class ); ?>">
			<section class="content-main blog-archive">
				<?php get_template_part( 'template-parts/content/blog-archive-header' ); ?>

				<?php if ( have_posts() ) : ?>
					<ul class="blog-archive__grid">
						<?php
						while ( have_posts() ) :
							the_post();
							$ts_post = get_post();

							if ( ! $ts_post instanceof WP_Post ) {
								continue;
							}

							get_template_part(
								'template-parts/content/post-card',
								null,
								array(
									'post'    => $ts_post,
									'variant' => 'grid',
									'tag'     => 'li',
								)
							);
						endwhile;
						?>
					</ul>

					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 1,
							'prev_text' => __( 'Previous', 'tailwindscore' ),
							'next_text' => __( 'Next', 'tailwindscore' ),
							'class'     => 'blog-archive__pagination',
						)
					);
					?>
				<?php else : ?>
					<section class="empty-state stack">
						<h2 class="heading"><?php esc_html_e( 'Nothing here yet.', 'tailwindscore' ); ?></h2>
						<p><?php esc_html_e( 'No content found.', 'tailwindscore' ); ?></p>
					</section>
				<?php endif; ?>
			</section>
			<?php if ( $ts_has_sidebar ) : ?>
				<?php get_sidebar(); ?>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
