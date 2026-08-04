<?php
declare(strict_types=1);

/**
 * 商店 / 分类归档布局壳层。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="wc-archive stack" data-ts-archive>
	<div class="wc-archive__notices">
		<?php woocommerce_output_all_notices(); ?>
	</div>

	<?php get_template_part( 'template-parts/woocommerce/archive/filters-mobile' ); ?>

	<div class="wc-archive-layout">
		<?php get_template_part( 'template-parts/woocommerce/archive/sidebar-filters' ); ?>

		<div class="wc-archive-main">
			<?php get_template_part( 'template-parts/woocommerce/archive/loop' ); ?>
		</div>
	</div>
</section>
