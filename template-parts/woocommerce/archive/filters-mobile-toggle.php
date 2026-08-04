<?php
declare(strict_types=1);

/**
 * 移动端工具栏：打开筛选 Drawer 的图标按钮。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$context = tailwindscore_archive_get_context();
$groups  = $context['sidebar_groups'];

if ( ! is_array( $groups ) || array() === $groups ) {
	return;
}

$active_count = isset( $context['active_filter_count'] ) ? (int) $context['active_filter_count'] : 0;
$panel_id     = 'ts-archive-filters-mobile-panel';
?>
<button
	type="button"
	class="wc-archive-filters-mobile__toggle btn-icon"
	data-ts-archive-filters-toggle
	aria-expanded="false"
	aria-controls="<?php echo esc_attr( $panel_id ); ?>"
	aria-label="<?php esc_attr_e( 'Filters', 'tailwindscore' ); ?>"
>
	<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'sliders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<?php if ( $active_count > 0 ) : ?>
		<span class="wc-archive-filters-mobile__count" data-ts-archive-filters-count><?php echo esc_html( (string) $active_count ); ?></span>
	<?php else : ?>
		<span class="wc-archive-filters-mobile__count is-empty" data-ts-archive-filters-count hidden></span>
	<?php endif; ?>
</button>
