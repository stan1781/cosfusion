<?php
declare(strict_types=1);

/**
 * 文章正文下方：相关文章。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$ts_related = tailwindscore_get_related_posts( (int) get_the_ID(), 3 );

if ( array() === $ts_related ) {
	return;
}
?>
<section class="post-related stack" aria-labelledby="ts-post-related-title">
	<h2 id="ts-post-related-title" class="post-related__title">
		<?php esc_html_e( 'Related posts', 'tailwindscore' ); ?>
	</h2>
	<div class="post-related__grid">
		<?php foreach ( $ts_related as $ts_related_post ) : ?>
			<?php
			get_template_part(
				'template-parts/content/post-card',
				null,
				array(
					'post'    => $ts_related_post,
					'variant' => 'card',
					'tag'     => 'article',
				)
			);
			?>
		<?php endforeach; ?>
	</div>
</section>
