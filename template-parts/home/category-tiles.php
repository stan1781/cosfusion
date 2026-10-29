<?php
/**
 * 分类磁贴网格。
 *
 * @var array<string, mixed> $args
 */
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$terms   = isset( $args['terms'] ) && is_array( $args['terms'] ) ? $args['terms'] : array();
$columns = isset( $args['columns'] ) ? max( 1, min( 6, (int) $args['columns'] ) ) : 4;
$title   = isset( $args['title'] ) ? trim( (string) $args['title'] ) : '';

if ( array() === $terms ) {
	return;
}
?>
<section class="category-tiles" data-ts-category-tiles data-ts-columns="<?php echo esc_attr( (string) $columns ); ?>" style="--ts-category-tiles-columns: <?php echo esc_attr( (string) $columns ); ?>">
	<div class="container">
		<?php if ( '' !== $title ) : ?>
			<?php
			if ( function_exists( 'tailwindscore_render_section_title' ) ) {
				tailwindscore_render_section_title( $title );
			} else {
				printf( '<h2 class="category-tiles__heading">%s</h2>', esc_html( $title ) );
			}
			?>
		<?php endif; ?>
		<ul class="category-tiles__grid">
			<?php foreach ( $terms as $term ) : ?>
				<?php
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				$media = function_exists( 'tailwindscore_category_tiles_term_image' )
					? tailwindscore_category_tiles_term_image( $term )
					: array( 'html' => '', 'url' => '' );
				$link  = $media['url'];
				if ( '' === $link ) {
					continue;
				}
				$label = $term->name;
				?>
				<li class="category-tiles__item">
					<a class="category-tiles__card" href="<?php echo esc_url( $link ); ?>">
						<span class="category-tiles__media<?php echo '' === $media['html'] ? ' category-tiles__media--placeholder' : ''; ?>">
							<?php
							if ( '' !== $media['html'] ) {
								echo $media['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</span>
						<span class="category-tiles__label">
							<span class="category-tiles__name"><?php echo esc_html( $label ); ?></span>
							<span class="category-tiles__arrow" aria-hidden="true">→</span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
