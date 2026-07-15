<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div
	id="ts-confirm-receipt-modal"
	class="confirm-receipt-modal"
	data-ts-confirm-receipt-modal
	hidden
	aria-hidden="true"
>
	<button
		type="button"
		class="confirm-receipt-modal__backdrop"
		data-ts-confirm-receipt-close
		aria-label="<?php esc_attr_e( 'Close dialog', 'tailwindscore' ); ?>"
	></button>
	<div
		class="confirm-receipt-modal__dialog stack"
		role="dialog"
		aria-modal="true"
		aria-labelledby="ts-confirm-receipt-title"
	>
		<div class="confirm-receipt-modal__header">
			<h2 id="ts-confirm-receipt-title" class="confirm-receipt-modal__title">
				<?php esc_html_e( 'Confirm Receipt', 'tailwindscore' ); ?>
			</h2>
			<?php
			tailwindscore_render_close_button(
				__( 'Close dialog', 'tailwindscore' ),
				array(
					'attrs' => array(
						'data-ts-confirm-receipt-close' => '',
					),
				)
			);
			?>
		</div>
		<div class="confirm-receipt-modal__body">
			<p data-ts-confirm-receipt-message>
				<?php esc_html_e( 'Are you sure you have received this order? This action will mark the order as completed.', 'tailwindscore' ); ?>
			</p>
		</div>
		<div class="confirm-receipt-modal__footer">
			<button type="button" class="btn btn-ghost" data-ts-confirm-receipt-close>
				<?php esc_html_e( 'Cancel', 'tailwindscore' ); ?>
			</button>
			<button type="button" class="btn" data-ts-confirm-receipt-confirm>
				<?php esc_html_e( 'Confirm', 'tailwindscore' ); ?>
			</button>
		</div>
	</div>
</div>
