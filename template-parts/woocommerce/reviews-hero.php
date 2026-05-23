<?php
declare(strict_types=1);

/**
 * Reviews hero: customer photos + summary card.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$gallery = Tailwindscore_Showcase_Gallery::get_instance();

if ( ! $gallery ) {
	return;
}
?>
<div class="ts-reviews-hero">
	<div class="ts-reviews-hero__gallery">
		<?php $gallery->render_showcase_gallery_strip(); ?>
	</div>
	<aside class="ts-reviews-hero__aside">
		<?php get_template_part( 'template-parts/woocommerce/reviews-summary-card' ); ?>
	</aside>
</div>
