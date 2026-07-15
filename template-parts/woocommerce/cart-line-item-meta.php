<?php
declare(strict_types=1);

$meta          = isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : array();
$cart_item     = isset( $args['cart_item'] ) && is_array( $args['cart_item'] ) ? $args['cart_item'] : array();
$cart_item_key = isset( $args['cart_item_key'] ) ? (string) $args['cart_item_key'] : '';

if ( empty( $meta ) ) {
	return;
}
?>
<ul class="ts-cart-drawer-cart__details">
	<?php foreach ( $meta as $row ) : ?>
		<?php
		$label = isset( $row['label'] ) ? (string) $row['label'] : '';
		$value = isset( $row['value'] ) ? (string) $row['value'] : '';

		if ( '' === $label || '' === $value ) {
			continue;
		}
		?>
		<li class="ts-cart-drawer-cart__detail">
			<span class="ts-cart-drawer-cart__detail-label"><?php echo esc_html( $label ); ?>:</span>
			<span class="ts-cart-drawer-cart__detail-value"><?php echo wp_kses_post( $value ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
<?php
/**
 * 购物车行项目 meta 渲染完成后触发。
 *
 * @param array<int, array{type: string, label: string, value: string}> $meta
 * @param array<string, mixed>                                           $cart_item
 * @param string                                                         $cart_item_key
 */
do_action( 'tailwindscore_cart_line_item_meta', $meta, $cart_item, $cart_item_key );
