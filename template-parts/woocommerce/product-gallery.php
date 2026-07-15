<?php
declare(strict_types=1);

/**
 * 单品商品 Swiper 图库挂载点。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$gallery_images  = tailwindscore_gallery_parent_images( $product );
$gallery_config  = tailwindscore_product_gallery_config( $product->get_id() );
$config_json     = wp_json_encode( $gallery_config );
$has_multi_slides = count( $gallery_images ) > 1;

do_action( 'woocommerce_before_single_product_summary' );
?>
<div
	class="ts-product-gallery<?php echo $has_multi_slides ? ' ts-product-gallery--multi' : ''; ?>"
	data-ts-product-gallery
	<?php if ( is_string( $config_json ) && '' !== $config_json ) : ?>
		data-product-gallery="<?php echo esc_attr( $config_json ); ?>"
	<?php endif; ?>
>
	<div class="ts-product-gallery__layout">
		<div class="ts-product-gallery__thumbs-col ts-product-gallery__thumbs-col--desktop" aria-hidden="false">
			<div class="swiper ts-product-gallery__thumbs" data-ts-gallery-thumbs>
				<div class="swiper-wrapper" data-ts-gallery-thumbs-wrapper>
					<?php
					foreach ( $gallery_images as $index => $img ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo tailwindscore_gallery_render_slide_html( $img, (int) $index, false, true );
					}
					?>
				</div>
			</div>
		</div>

		<div class="ts-product-gallery__main-col">
			<div class="swiper ts-product-gallery__main" data-ts-gallery-main>
				<div class="swiper-wrapper" data-ts-gallery-main-wrapper>
					<?php
					foreach ( $gallery_images as $index => $img ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo tailwindscore_gallery_render_slide_html( $img, (int) $index, 0 === (int) $index );
					}
					?>
				</div>
			</div>
			<button
				type="button"
				class="ts-product-gallery__nav ts-product-gallery__nav--prev"
				data-ts-gallery-prev
				aria-label="<?php esc_attr_e( 'Previous image', 'tailwindscore' ); ?>"
				<?php echo $has_multi_slides ? '' : ' hidden'; ?>
			>
				<span class="ts-product-gallery__nav-icon"><?php echo tailwindscore_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</button>
			<button
				type="button"
				class="ts-product-gallery__nav ts-product-gallery__nav--next"
				data-ts-gallery-next
				aria-label="<?php esc_attr_e( 'Next image', 'tailwindscore' ); ?>"
				<?php echo $has_multi_slides ? '' : ' hidden'; ?>
			>
				<span class="ts-product-gallery__nav-icon"><?php echo tailwindscore_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</button>
			<div class="ts-product-gallery__pagination swiper-pagination" data-ts-gallery-pagination></div>
			<div class="ts-product-gallery__counter" data-ts-gallery-counter hidden></div>
		</div>
	</div>
</div>
