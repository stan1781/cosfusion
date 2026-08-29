<?php
declare(strict_types=1);

/**
 * 购物车行项目编辑表单（可变商品选项）。
 *
 * @package TailwindScore
 *
 * @var string               $cart_item_key 购物车行项目键。
 * @var array<string, mixed> $cart_item     购物车行项目数据。
 * @var WC_Product_Variable  $product       父级可变商品。
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
$price_html                    = $variation_product instanceof WC_Product
	? $variation_product->get_price_html()
	: $edit_product->get_price_html();

$thumb_product = $variation_product instanceof WC_Product ? $variation_product : $edit_product;
$thumb_id      = (int) $thumb_product->get_image_id();
if ( $thumb_id < 1 ) {
	$thumb_id = (int) $edit_product->get_image_id();
}

$thumb_src    = $thumb_id > 0 ? (string) wp_get_attachment_image_url( $thumb_id, 'woocommerce_gallery_thumbnail' ) : '';
$thumb_srcset = $thumb_id > 0 ? (string) wp_get_attachment_image_srcset( $thumb_id, 'woocommerce_gallery_thumbnail' ) : '';
$thumb_sizes  = $thumb_id > 0 ? (string) wp_get_attachment_image_sizes( $thumb_id, 'woocommerce_gallery_thumbnail' ) : '';
$thumb_alt    = $thumb_id > 0 ? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
if ( '' === $thumb_alt ) {
	$thumb_alt = $display_name;
}
?>
<div
	class="cart-item-edit-form"
	data-ts-cart-item-edit-form
	data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
>
	<?php
	if ( function_exists( 'tailwindscore_print_size_guide_config_script' ) ) {
		tailwindscore_print_size_guide_config_script( (int) $edit_product->get_id() );
	}
	?>
	<div class="cart-item-edit-form__layout">
		<?php if ( '' !== $thumb_src ) : ?>
			<div class="cart-item-edit-form__image">
				<img
					class="cart-item-edit-form__thumb"
					src="<?php echo esc_url( $thumb_src ); ?>"
					<?php if ( '' !== $thumb_srcset ) : ?>
						srcset="<?php echo esc_attr( $thumb_srcset ); ?>"
					<?php endif; ?>
					<?php if ( '' !== $thumb_sizes ) : ?>
						sizes="<?php echo esc_attr( $thumb_sizes ); ?>"
					<?php endif; ?>
					alt="<?php echo esc_attr( $thumb_alt ); ?>"
					width="96"
					height="96"
					decoding="async"
					data-ts-cart-item-edit-image-fallback-src="<?php echo esc_url( $thumb_src ); ?>"
					data-ts-cart-item-edit-image-fallback-srcset="<?php echo esc_attr( $thumb_srcset ); ?>"
					data-ts-cart-item-edit-image-fallback-sizes="<?php echo esc_attr( $thumb_sizes ); ?>"
					data-ts-cart-item-edit-image-fallback-alt="<?php echo esc_attr( $thumb_alt ); ?>"
				/>
			</div>
		<?php endif; ?>

		<div class="cart-item-edit-form__main">
			<p class="cart-item-edit-form__name"><?php echo esc_html( $display_name ); ?></p>
			<?php if ( '' !== $price_html ) : ?>
				<div class="cart-item-edit-form__price price">
					<?php echo wp_kses_post( $price_html ); ?>
				</div>
			<?php endif; ?>

			<div class="cart-item-edit-form__variations">
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
		</div>
	</div>

	<?php
	/**
	 * 渲染购物车行项目的额外可编辑字段（自定义字段、捆绑包等）。
	 *
	 * @param array<string, mixed> $cart_item     购物车行项目。
	 * @param string               $cart_item_key 购物车行项目键。
	 */
	do_action( 'tailwindscore_cart_item_edit_form', $cart_item, $cart_item_key );
	?>
</div>
