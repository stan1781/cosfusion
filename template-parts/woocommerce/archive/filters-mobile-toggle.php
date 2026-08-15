<?php
declare(strict_types=1);

/**
 * 移动端工具栏：打开筛选 Drawer 的按钮（图标 + Filters 文案）。
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
	class="wc-archive-filters-mobile__toggle btn btn-outline"
	data-ts-archive-filters-toggle
	aria-expanded="false"
	aria-controls="<?php echo esc_attr( $panel_id ); ?>"
>
	<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'sliders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="wc-archive-filters-mobile__label"><?php echo esc_html__( 'Filters', 'tailwindscore' ); ?></span>
	<?php if ( $active_count > 0 ) : ?>
		<span class="wc-archive-filters-mobile__count" data-ts-archive-filters-count><?php echo esc_html( (string) $active_count ); ?></span>
	<?php else : ?>
		<span class="wc-archive-filters-mobile__count is-empty" data-ts-archive-filters-count hidden></span>
	<?php endif; ?>
</button>
