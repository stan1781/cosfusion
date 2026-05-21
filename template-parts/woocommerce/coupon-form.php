<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Shared coupon form markup (ts-stack + ts-input-group).
 *
 * @var array $args {
 *     @type string $context drawer|checkout
 * }
 */
$context = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'drawer';

if ( 'checkout' === $context ) {
	$detached_form_id = 'woocommerce-checkout-form-coupon';
	$input_id         = 'coupon_code';
	$label_class      = 'screen-reader-text';
	$label_text       = __( 'Coupon:', 'woocommerce' );
	$placeholder      = __( 'Enter code', 'tailwindscore' );
	$button_text      = __( 'Apply', 'tailwindscore' );
	$button_name      = 'apply_coupon';
	$button_value     = __( 'Apply coupon', 'woocommerce' );
} else {
	$form_classes  = 'ts-cart-drawer-cart__coupon-form ts-coupon-form ts-stack';
	$form_id       = '';
	$form_action   = wc_get_cart_url();
	$input_id      = 'ts-cart-coupon-code';
	$label_class   = '';
	$label_text    = __( 'Coupon code', 'tailwindscore' );
	$placeholder   = __( 'Enter code', 'tailwindscore' );
	$button_text   = __( 'Apply', 'tailwindscore' );
	$button_name   = '';
	$button_value  = '';
	$use_cart_ajax = true;
}

if ( 'checkout' === $context ) :
	?>
	<div class="ts-checkout-coupon-fields ts-coupon-form ts-stack" data-ts-checkout-coupon-fields>
		<label class="<?php echo esc_attr( $label_class ); ?>" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label_text ); ?></label>
		<div class="ts-input-group">
			<input
				id="<?php echo esc_attr( $input_id ); ?>"
				class="ts-input"
				type="text"
				name="coupon_code"
				value=""
				autocomplete="off"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				form="<?php echo esc_attr( $detached_form_id ); ?>"
				data-ts-checkout-coupon-input
			>
			<button
				class="ts-button ts-button--secondary"
				type="submit"
				form="<?php echo esc_attr( $detached_form_id ); ?>"
				data-ts-button-loading
				data-ts-cart-coupon-submit
				<?php if ( $button_name ) : ?>
					name="<?php echo esc_attr( $button_name ); ?>"
				<?php endif; ?>
				<?php if ( $button_value ) : ?>
					value="<?php echo esc_attr( $button_value ); ?>"
				<?php endif; ?>
			><?php echo esc_html( $button_text ); ?></button>
		</div>
	</div>
	<?php
	return;
endif;
?>
<form
	class="<?php echo esc_attr( $form_classes ); ?>"
	method="post"
	<?php if ( $form_id ) : ?>
		id="<?php echo esc_attr( $form_id ); ?>"
	<?php endif; ?>
	<?php if ( $form_action ) : ?>
		action="<?php echo esc_url( $form_action ); ?>"
	<?php endif; ?>
	<?php if ( $use_cart_ajax ) : ?>
		data-ts-cart-coupon-form
	<?php endif; ?>
>
	<label class="<?php echo esc_attr( $label_class ); ?>" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label_text ); ?></label>
	<div class="ts-input-group">
		<input
			id="<?php echo esc_attr( $input_id ); ?>"
			class="ts-input"
			type="text"
			name="coupon_code"
			value=""
			autocomplete="off"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php if ( $use_cart_ajax ) : ?>
				data-ts-cart-coupon-input
			<?php endif; ?>
		>
		<button
			class="ts-button ts-button--secondary"
			type="submit"
			data-ts-button-loading
			<?php if ( $button_name ) : ?>
				name="<?php echo esc_attr( $button_name ); ?>"
			<?php endif; ?>
			<?php if ( $button_value ) : ?>
				value="<?php echo esc_attr( $button_value ); ?>"
			<?php endif; ?>
			<?php if ( $use_cart_ajax ) : ?>
				data-ts-cart-coupon-submit
			<?php endif; ?>
		><?php echo esc_html( $button_text ); ?></button>
	</div>
</form>
