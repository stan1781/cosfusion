<?php
declare(strict_types=1);

/**
 * 移动端：左侧筛选 Drawer（触发按钮在 toolbar）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$context = tailwindscore_archive_get_context();
$groups  = $context['sidebar_groups'];

if ( ! is_array( $groups ) || array() === $groups ) {
	return;
}

$panel_id    = 'ts-archive-filters-mobile-panel';
$title_id    = 'ts-archive-filters-mobile-title';
$close_label = __( 'Close filters', 'tailwindscore' );
?>
<div
	class="wc-archive-filters--mobile"
	data-ts-archive-filters
	data-ts-archive-filters-mobile
	data-open="false"
	aria-hidden="true"
	inert
>
	<button
		type="button"
		class="wc-archive-filters-mobile__backdrop"
		data-ts-archive-filters-close
		aria-label="<?php echo esc_attr( $close_label ); ?>"
	></button>

	<div
		id="<?php echo esc_attr( $panel_id ); ?>"
		class="wc-archive-filters-mobile__panel"
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
		data-ts-archive-filters-panel
	>
		<div class="wc-archive-filters-mobile__header">
			<h2 id="<?php echo esc_attr( $title_id ); ?>" class="wc-archive-filters-mobile__title">
				<?php esc_html_e( 'Filters', 'tailwindscore' ); ?>
			</h2>
			<?php
			tailwindscore_render_close_button(
				$close_label,
				array(
					'class' => 'wc-archive-filters-mobile__close',
					'attrs' => array(
						'data-ts-archive-filters-close' => '',
					),
				)
			);
			?>
		</div>

		<div class="wc-archive-filters-mobile__body">
			<?php get_template_part( 'template-parts/woocommerce/archive/filter-groups' ); ?>
		</div>
	</div>
</div>
