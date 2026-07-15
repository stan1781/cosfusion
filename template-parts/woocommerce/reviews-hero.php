<?php
declare(strict_types=1);

/**
 * 评价 hero：客户照片 + 摘要卡片。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$gallery = Tailwindscore_Showcase_Gallery::get_instance();

if ( ! $gallery ) {
	return;
}
?>
<div class="reviews-hero">
	<div class="reviews-hero__gallery">
		<?php $gallery->render_showcase_gallery_strip(); ?>
	</div>
	<aside class="reviews-hero__aside">
		<?php get_template_part( 'template-parts/woocommerce/reviews-summary-card' ); ?>
	</aside>
</div>
