<?php
declare(strict_types=1);

/**
 * Cart line item edit form (variable product options).
 *
 * @package TailwindScore
 *
 * @var string               $cart_item_key Cart item key.
 * @var array<string, mixed> $cart_item     Cart item data.
 * @var WC_Product_Variable  $product       Parent variable product.
 */

defined( 'ABSPATH' ) || exit;

$cart_item_key = isset( $args['cart_item_key'] ) ? (string) $args['cart_item_key'] : '';
$cart_item     = isset( $args['cart_item'] ) && is_array( $args['cart_item'] ) ? $args['cart_item'] : array();
$edit_product  = $args['product'] ?? null;

if ( ! $edit_product instanceof WC_Product_Variable || '' === $cart_item_key ) {
	return;
}

global $product;
$product = $edit_product;

$selected_variation_attributes = tailwindscore_cart_item_edit_selected_attributes( $cart_item );
$variation_product             = $cart_item['data'] ?? null;
$display_name                  = $variation_product instanceof WC_Product ? $variation_product->get_name() : $edit_product->get_name();
?>
<div
	class="ts-cart-item-edit-form"
	data-ts-cart-item-edit-form
	data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
>
	<p class="ts-cart-item-edit-form__name"><?php echo esc_html( $display_name ); ?></p>

	<div class="ts-cart-item-edit-form__variations">
		<?php
		wc_get_template(
			'single-product/add-to-cart/variable-quick-view.php',
			array(
				'attributes'                    => $edit_product->get_variation_attributes(),
				'available_variations'          => $edit_product->get_available_variations(),
				'selected_variation'            => false,
				'selected_variation_attributes' => $selected_variation_attributes,
				'context'                       => 'cart-edit',
				'current_variation_id'          => isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0,
			)
		);
		?>
	</div>

	<?php
	/**
	 * Render extra editable fields for a cart line item (custom fields, bundles, etc.).
	 *
	 * @param array<string, mixed> $cart_item     Cart item.
	 * @param string               $cart_item_key Cart item key.
	 */
	do_action( 'tailwindscore_cart_item_edit_form', $cart_item, $cart_item_key );
	?>
</div>
