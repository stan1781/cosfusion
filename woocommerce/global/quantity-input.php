<?php
/**
 * 商品数量输入（主题步进器）。
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 *
 * @var bool   $readonly 输入框是否设为只读。
 * @var string $type     input 的 type 属性。
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'woocommerce' );

$classes = is_array( $classes ) ? $classes : array_filter( array_map( 'trim', explode( ' ', (string) $classes ) ) );
if ( ! in_array( 'ts-quantity__input', $classes, true ) ) {
	$classes[] = 'ts-quantity__input';
}

get_template_part(
	'template-parts/components/quantity-input',
	null,
	array(
		'input_id'     => $input_id,
		'input_name'   => $input_name,
		'input_value'  => $input_value,
		'input_class'  => implode( ' ', $classes ),
		'min_value'    => (string) $min_value,
		'max_value'    => 0 < (float) $max_value ? (string) $max_value : '',
		'step'         => (string) $step,
		'readonly'     => $readonly,
		'type'         => $type,
		'placeholder'  => $placeholder,
		'inputmode'    => $inputmode,
		'autocomplete' => isset( $autocomplete ) ? (string) $autocomplete : 'on',
		'label'        => $label,
		'context'      => 'product',
	)
);
