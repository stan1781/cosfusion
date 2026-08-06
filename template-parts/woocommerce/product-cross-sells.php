<?php
declare(strict_types=1);

/**
 * 单品 Cross-sells 紧凑行列表。
 *
 * @package TailwindScore
 *
 * @var array<string, mixed> $args {
 *     @type string $title       区块标题。
 *     @type string $section_id  标题元素 id。
 *     @type int[]  $product_ids 商品 ID。
 * }
 */

defined( 'ABSPATH' ) || exit;

$title       = isset( $args['title'] ) ? (string) $args['title'] : '';
$section_id  = isset( $args['section_id'] ) ? (string) $args['section_id'] : 'ts-product-cross-sells-title';
$product_ids = isset( $args['product_ids'] ) && is_array( $args['product_ids'] ) ? $args['product_ids'] : array();

if ( array() === $product_ids ) {
	return;
}

global $product;
$previous_product = $product instanceof WC_Product ? $product : null;
?>
<section
	class="product-cross-sells"
	data-ts-product-cross-sells
	<?php if ( '' !== $title ) : ?>
		aria-labelledby="<?php echo esc_attr( $section_id ); ?>"
	<?php endif; ?>
>
	<?php if ( '' !== $title ) : ?>
		<h2 class="product-cross-sells__title heading" id="<?php echo esc_attr( $section_id ); ?>">
			<?php echo esc_html( $title ); ?>
		</h2>
	<?php endif; ?>

	<ul class="product-cross-sells__list">
		<?php foreach ( $product_ids as $product_id ) : ?>
			<?php
			$item = wc_get_product( (int) $product_id );
			if ( ! $item instanceof WC_Product || ! $item->is_visible() ) {
				continue;
			}
			$product = $item;
			get_template_part(
				'template-parts/woocommerce/product-cross-sell-item',
				null,
				array(
					'product' => $item,
				)
			);
			?>
		<?php endforeach; ?>
	</ul>
</section>
<?php
$product = $previous_product;
