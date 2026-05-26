<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Shared coupon form markup (checkout + cart drawer).
 *
 * @var array $args {
 *     @type string $context drawer|checkout
 * }
 */
$context = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'drawer';

$is_checkout = ( 'checkout' === $context );

$detached_form_id = $is_checkout ? 'woocommerce-checkout-form-coupon' : '';
$input_id         = $is_checkout ? 'coupon_code' : 'ts-cart-coupon-code';
$label_class      = $is_checkout ? 'screen-reader-text' : 'ts-coupon-form__label';
$label_text       = $is_checkout
	? __( 'Coupon:', 'woocommerce' )
	: __( 'Coupon code', 'tailwindscore' );
$placeholder      = __( 'Discount code or gift card', 'tailwindscore' );
$button_text      = __( 'Apply', 'tailwindscore' );
$button_name      = $is_checkout ? 'apply_coupon' : '';
$button_value     = $is_checkout ? __( 'Apply coupon', 'woocommerce' ) : '';
$form_classes     = $is_checkout
	? 'ts-coupon-form ts-coupon-form--checkout'
	: 'ts-cart-drawer-cart__coupon-form ts-coupon-form ts-coupon-form--drawer';
$form_action      = $is_checkout ? '' : wc_get_cart_url();
$form_id          = $is_checkout ? '' : '';
$use_cart_ajax    = ! $is_checkout;
$wrapper_attrs    = $is_checkout ? ' data-ts-checkout-coupon-fields' : '';

$control_markup = static function () use (
	$input_id,
	$placeholder,
	$button_text,
	$button_name,
	$button_value,
	$detached_form_id,
	$use_cart_ajax
): void {
	?>
	<div class="ts-input-group">
		<input
			id="<?php echo esc_attr( $input_id ); ?>"
			class="ts-input"
			type="text"
			name="coupon_code"
			value=""
			autocomplete="off"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php if ( $detached_form_id ) : ?>
				form="<?php echo esc_attr( $detached_form_id ); ?>"
				data-ts-checkout-coupon-input
			<?php elseif ( $use_cart_ajax ) : ?>
				data-ts-cart-coupon-input
			<?php endif; ?>
		>
		<button
			class="ts-button ts-button--secondary"
			type="submit"
			data-ts-button-loading
			<?php if ( $detached_form_id ) : ?>
				form="<?php echo esc_attr( $detached_form_id ); ?>"
				data-ts-cart-coupon-submit
			<?php elseif ( $use_cart_ajax ) : ?>
				data-ts-cart-coupon-submit
			<?php endif; ?>
			<?php if ( $button_name ) : ?>
				name="<?php echo esc_attr( $button_name ); ?>"
			<?php endif; ?>
			<?php if ( $button_value ) : ?>
				value="<?php echo esc_attr( $button_value ); ?>"
			<?php endif; ?>
		><?php echo esc_html( $button_text ); ?></button>
	</div>
	<?php
};

if ( $is_checkout ) :
	?>
	<div class="<?php echo esc_attr( $form_classes ); ?>"<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<label class="<?php echo esc_attr( $label_class ); ?>" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label_text ); ?></label>
		<?php $control_markup(); ?>
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
	<?php $control_markup(); ?>
</form>
