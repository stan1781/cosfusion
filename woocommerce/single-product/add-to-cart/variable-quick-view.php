<?php
/**
 * 可变商品加入购物车 — Quick View（文字按钮，无变体图片）。
 *
 * @package TailwindScore
 * @version 0.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_type( 'variable' ) ) {
	return;
}

$product_id = $product->get_id();

$context = isset( $context ) ? sanitize_key( (string) $context ) : 'quick-view';
$is_cart_edit = 'cart-edit' === $context;

// 复用 content-product-quick-view.php 传入的数据（避免重复调用 get_available_variations()）。
if ( ! isset( $attributes ) || ! is_array( $attributes ) ) {
	$attributes = $product->get_variation_attributes();
}
if ( ! isset( $available_variations ) || ! is_array( $available_variations ) ) {
	$available_variations = $product->get_available_variations();
}
if ( ! isset( $selected_variation_attributes ) || ! is_array( $selected_variation_attributes ) ) {
	$selected_variation_attributes = array();
}
if ( ! isset( $current_variation_id ) ) {
	$current_variation_id = 0;
}

$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $product_id ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"<?php echo $is_cart_edit ? ' data-ts-require-variation-selection' : ''; ?>>
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<div class="variations variations quick-view-variations" role="presentation">
			<?php foreach ( $attributes as $attribute_name => $options ) : ?>
				<?php
				$is_size_attribute = function_exists( 'tailwindscore_is_size_variation_attribute' )
					&& tailwindscore_is_size_variation_attribute( $attribute_name );
				$attr_slug         = sanitize_title( $attribute_name );
				$selected          = $selected_variation_attributes[ $attribute_name ]
					?? $selected_variation_attributes[ 'attribute_' . $attr_slug ]
					?? ( isset( $_REQUEST[ 'attribute_' . $attr_slug ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'attribute_' . $attr_slug ] ) ) : $product->get_variation_default_attribute( $attribute_name ) ); // phpcs:ignore WordPress.Security.NonceVerification
				?>
				<div class="variation-row variation-row<?php echo $is_size_attribute ? ' variation-row--size' : ''; ?>">
					<div class="label variation-row__label">
						<label for="<?php echo esc_attr( $attr_slug ); ?>">
							<?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>:
							<span class="selected-attribute-value" data-for="<?php echo esc_attr( $attr_slug ); ?>"></span>
						</label>
					</div>
					<div class="value variation-row__value">
						<?php
						wc_dropdown_variation_attribute_options(
							array(
								'options'          => $options,
								'attribute'        => $attribute_name,
								'product'          => $product,
								'class'            => 'hidden-variation-select',
								'show_option_none' => $is_cart_edit ? false : __( 'Choose an option', 'woocommerce' ),
							)
						);

						printf(
							'<ul class="custom-variation-selector text-selector shape-square" data-attribute="%s">',
							esc_attr( $attr_slug )
						);

						if ( $options ) {
							if ( taxonomy_exists( $attribute_name ) ) {
								$terms = wc_get_product_terms( $product_id, $attribute_name, array( 'fields' => 'all' ) );

								foreach ( $terms as $term ) {
									if ( ! in_array( $term->slug, $options, true ) ) {
										continue;
									}

									$class = ( $selected === $term->slug ) ? 'selected' : '';
									printf(
										'<li class="%1$s" data-value="%2$s" data-name="%3$s" role="button" tabindex="0">%4$s</li>',
										esc_attr( $class ),
										esc_attr( $term->slug ),
										esc_attr( $term->name ),
										esc_html( $term->name )
									);
								}
							} else {
								foreach ( $options as $option ) {
									$class = ( $selected === $option ) ? 'selected' : '';
									printf(
										'<li class="%1$s" data-value="%2$s" data-name="%3$s" role="button" tabindex="0">%4$s</li>',
										esc_attr( $class ),
										esc_attr( $option ),
										esc_attr( $option ),
										esc_html( $option )
									);
								}
							}
						}

						echo '</ul>';

						if ( $is_size_attribute ) {
							/**
							 * 尺码属性值行之后（内联尺码测量；QV 不挂 Size Guide 按钮）。
							 *
							 * @param string     $attribute_name 属性键。
							 * @param WC_Product $product        商品对象。
							 */
							do_action( 'tailwindscore_after_size_attribute_value', $attribute_name, $product );
						}
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );

			if ( $is_cart_edit ) :
				?>
				<div class="woocommerce-variation single_variation" role="alert" aria-relevant="additions"></div>
				<div class="woocommerce-variation-add-to-cart variations_button">
					<input type="hidden" name="variation_id" class="variation_id" value="<?php echo absint( $current_variation_id ); ?>" />
				</div>
				<?php
			else :
				do_action( 'woocommerce_single_variation' );
			endif;

			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
