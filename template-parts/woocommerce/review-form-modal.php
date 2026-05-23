<?php
declare(strict_types=1);

/**
 * Product review form modal.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

if ( ! tailwindscore_can_leave_product_review() ) {
	return;
}

$modal_title = tailwindscore_get_review_form_open_label();
?>
<div
	id="ts-review-form-modal"
	class="ts-review-form-modal"
	data-ts-review-form-modal
	hidden
	aria-hidden="true"
>
	<button
		type="button"
		class="ts-review-form-modal__backdrop"
		data-ts-review-form-close
		aria-label="<?php esc_attr_e( 'Close review form', 'tailwindscore' ); ?>"
	></button>
	<div
		id="ts-review-form-panel"
		class="ts-review-form-modal__dialog"
		role="dialog"
		aria-modal="true"
		aria-labelledby="ts-review-form-modal-title"
	>
		<div class="ts-review-form-modal__header">
			<h2 id="ts-review-form-modal-title" class="ts-review-form-modal__title"><?php echo esc_html( $modal_title ); ?></h2>
			<?php
			tailwindscore_render_close_button(
				__( 'Close review form', 'tailwindscore' ),
				array(
					'attrs' => array(
						'data-ts-review-form-close' => '',
					),
				)
			);
			?>
		</div>
		<div class="ts-review-form-modal__body">
			<?php tailwindscore_render_product_review_form(); ?>
		</div>
	</div>
</div>
