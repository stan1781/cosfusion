<?php
declare(strict_types=1);

/**
 * 购物车行项目编辑弹窗外壳。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	id="ts-cart-item-edit-modal"
	class="cart-item-edit-modal"
	data-ts-cart-item-edit-modal
	hidden
	aria-hidden="true"
>
	<button
		type="button"
		class="cart-item-edit-modal__backdrop"
		data-ts-cart-item-edit-close
		aria-label="<?php esc_attr_e( 'Close edit options', 'tailwindscore' ); ?>"
	></button>
	<div
		id="ts-cart-item-edit-panel"
		class="cart-item-edit-modal__dialog"
		role="dialog"
		aria-modal="true"
		aria-labelledby="ts-cart-item-edit-modal-title"
	>
		<div class="cart-item-edit-modal__header">
			<h2 id="ts-cart-item-edit-modal-title" class="cart-item-edit-modal__title"><?php esc_html_e( 'Edit options', 'tailwindscore' ); ?></h2>
			<?php
			tailwindscore_render_close_button(
				__( 'Close edit options', 'tailwindscore' ),
				array(
					'attrs' => array(
						'data-ts-cart-item-edit-close' => '',
					),
				)
			);
			?>
		</div>
		<div class="cart-item-edit-modal__body" data-ts-cart-item-edit-body>
			<div class="cart-item-edit-modal__loading" data-ts-cart-item-edit-loading hidden>
				<span class="cart-item-edit-modal__spinner" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Loading…', 'tailwindscore' ); ?></span>
			</div>
			<div class="cart-item-edit-modal__content" data-ts-cart-item-edit-content></div>
		</div>
		<div class="cart-item-edit-modal__footer">
			<button type="button" class="btn btn-secondary" data-ts-cart-item-edit-cancel>
				<?php esc_html_e( 'Cancel', 'tailwindscore' ); ?>
			</button>
			<button type="button" class="btn" data-ts-cart-item-edit-save data-ts-button-loading>
				<?php esc_html_e( 'Save', 'tailwindscore' ); ?>
			</button>
		</div>
	</div>
</div>
