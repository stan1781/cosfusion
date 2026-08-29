<?php
declare(strict_types=1);

/**
 * 数量步进器（减 / 输入 / 加）。
 *
 * @var array<string, mixed> $args 模板参数。
 */

$input_id       = isset( $args['input_id'] ) ? (string) $args['input_id'] : '';
$input_name     = isset( $args['input_name'] ) ? (string) $args['input_name'] : 'quantity';
$input_value    = isset( $args['input_value'] ) ? (string) $args['input_value'] : '1';
$input_class    = isset( $args['input_class'] ) ? (string) $args['input_class'] : 'quantity__input qty';
$min_value      = isset( $args['min_value'] ) ? (string) $args['min_value'] : '1';
$max_value      = isset( $args['max_value'] ) ? (string) $args['max_value'] : '';
$step           = isset( $args['step'] ) ? (string) $args['step'] : '1';
$readonly       = ! empty( $args['readonly'] );
$type           = isset( $args['type'] ) ? (string) $args['type'] : 'number';
$placeholder    = isset( $args['placeholder'] ) ? (string) $args['placeholder'] : '';
$inputmode      = isset( $args['inputmode'] ) ? (string) $args['inputmode'] : 'numeric';
$autocomplete   = isset( $args['autocomplete'] ) ? (string) $args['autocomplete'] : 'on';
$label          = isset( $args['label'] ) ? (string) $args['label'] : __( 'Quantity', 'tailwindscore' );
$cart_item_key  = isset( $args['cart_item_key'] ) ? (string) $args['cart_item_key'] : '';
$context        = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'product';
$decrease_label = isset( $args['decrease_label'] ) ? (string) $args['decrease_label'] : __( 'Decrease quantity', 'tailwindscore' );
$increase_label = isset( $args['increase_label'] ) ? (string) $args['increase_label'] : __( 'Increase quantity', 'tailwindscore' );
$aria_label     = isset( $args['aria_label'] ) ? (string) $args['aria_label'] : __( 'Product quantity', 'woocommerce' );

$step_attrs      = '' !== $cart_item_key ? sprintf( ' data-cart-item-key="%s"', esc_attr( $cart_item_key ) ) : '';
// 购物车页与抽屉使用 Store API 数量控件（见 cart/index.ts）。
$is_cart_context = in_array( $context, array( 'cart', 'cart-drawer', 'drawer' ), true );
$is_compact      = in_array( $context, array( 'cart', 'cart-drawer', 'drawer' ), true );
$wrapper_classes = 'quantity quantity' . ( $is_compact ? ' quantity-compact' : '' );
?>
<div class="<?php echo esc_attr( $wrapper_classes ); ?>" data-ts-quantity<?php echo $is_cart_context ? ' data-ts-cart-quantity-controls' : ''; ?>>
	<?php do_action( 'woocommerce_before_quantity_input_field' ); ?>
	<button
		type="button"
		class="quantity__step"
		data-ts-quantity-step="-1"
		<?php echo $step_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		aria-label="<?php echo esc_attr( $decrease_label ); ?>"
	>
		<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'minus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	</button>
	<?php if ( '' !== $input_id ) : ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
	<?php endif; ?>
	<input
		<?php if ( '' !== $input_id ) : ?>
			id="<?php echo esc_attr( $input_id ); ?>"
		<?php endif; ?>
		class="<?php echo esc_attr( $input_class ); ?>"
		type="<?php echo esc_attr( $type ); ?>"
		<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
		<?php if ( '' !== $input_name ) : ?>
			name="<?php echo esc_attr( $input_name ); ?>"
		<?php endif; ?>
		value="<?php echo esc_attr( $input_value ); ?>"
		aria-label="<?php echo esc_attr( $aria_label ); ?>"
		min="<?php echo esc_attr( $min_value ); ?>"
		<?php if ( '' !== $max_value && (float) $max_value > 0 ) : ?>
			max="<?php echo esc_attr( $max_value ); ?>"
		<?php endif; ?>
		<?php if ( ! $readonly ) : ?>
			step="<?php echo esc_attr( $step ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			autocomplete="<?php echo esc_attr( $autocomplete ); ?>"
		<?php endif; ?>
		data-ts-quantity-input
		<?php echo $step_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	/>
	<button
		type="button"
		class="quantity__step"
		data-ts-quantity-step="1"
		<?php echo $step_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		aria-label="<?php echo esc_attr( $increase_label ); ?>"
	>
		<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'plus' ); ?></span>
	</button>
	<?php do_action( 'woocommerce_after_quantity_input_field' ); ?>
</div>
