<?php
declare(strict_types=1);

/**
 * 404 模板入口。
 *
 * @package TailwindScore
 */

get_header();
?>
<main id="primary" class="site-main main not-found">
	<?php get_template_part( 'template-parts/site/not-found' ); ?>
</main>
<?php
get_footer();
