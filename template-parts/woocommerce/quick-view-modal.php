<?php
declare(strict_types=1);

/**
 * Quick View 弹窗外壳（内容通过 AJAX 加载）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	id="ts-quick-view-modal"
	class="ts-quick-view-modal"
	data-ts-quick-view-modal
	hidden
	aria-hidden="true"
>
	<button
		type="button"
		class="ts-quick-view-modal__backdrop"
		data-ts-quick-view-close
		aria-label="<?php esc_attr_e( 'Close quick view', 'tailwindscore' ); ?>"
	></button>
	<div
		id="ts-quick-view-panel"
		class="ts-quick-view-modal__dialog"
		role="dialog"
		aria-modal="true"
	>
		<?php
		tailwindscore_render_close_button(
			__( 'Close quick view', 'tailwindscore' ),
			array(
				'attrs' => array(
					'data-ts-quick-view-close' => '',
				),
			)
		);
		?>
		<div class="ts-quick-view-modal__body" data-ts-quick-view-body>
			<div class="ts-quick-view-modal__loading" data-ts-quick-view-loading hidden>
				<span class="ts-quick-view-modal__spinner" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Loading…', 'tailwindscore' ); ?></span>
			</div>
			<div class="ts-quick-view-modal__content woocommerce" data-ts-quick-view-content></div>
		</div>
	</div>
</div>
