<?php
declare(strict_types=1);

/**
 * 桌面内联归档筛选侧栏。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$context = tailwindscore_archive_get_context();
$groups  = $context['sidebar_groups'];

if ( ! is_array( $groups ) || array() === $groups ) {
	return;
}
?>
<aside
	class="wc-archive-sidebar wc-archive-sidebar--inline"
	data-ts-archive-filters
	aria-label="<?php esc_attr_e( 'Product filters', 'tailwindscore' ); ?>"
>
	<h2 class="wc-archive-sidebar__heading"><?php esc_html_e( 'Filters', 'tailwindscore' ); ?></h2>
	<?php get_template_part( 'template-parts/woocommerce/archive/filter-groups' ); ?>
</aside>
