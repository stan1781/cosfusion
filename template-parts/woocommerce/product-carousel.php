<?php
declare(strict_types=1);

/**
 * 产品 Swiper 轮播壳层。
 *
 * @var array<string, mixed> $args get_template_part() 传入的参数。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$section_id         = isset( $args['section_id'] ) ? (string) $args['section_id'] : wp_unique_id( 'ts-product-carousel-' );
$title              = isset( $args['title'] ) ? (string) $args['title'] : '';
$loop_html          = isset( $args['loop_html'] ) ? (string) $args['loop_html'] : '';
$columns_mobile     = isset( $args['columns_mobile'] ) ? (float) $args['columns_mobile'] : 2.5;
$columns_desktop    = isset( $args['columns_desktop'] ) ? (float) $args['columns_desktop'] : 4.0;
$columns_wide       = isset( $args['columns_wide'] ) && null !== $args['columns_wide'] && '' !== $args['columns_wide']
	? (float) $args['columns_wide']
	: null;
$gap                = isset( $args['gap'] ) ? (int) $args['gap'] : 16;
$section_class      = isset( $args['section_class'] ) ? trim( (string) $args['section_class'] ) : '';
$title_class        = isset( $args['title_class'] ) ? trim( (string) $args['title_class'] ) : 'heading';
$section_data_attrs = isset( $args['section_data_attrs'] ) && is_array( $args['section_data_attrs'] )
	? $args['section_data_attrs']
	: array();

if ( '' === trim( $loop_html ) ) {
	return;
}

$has_title     = '' !== $title;
$section_class = trim( 'product-carousel ' . $section_class );
?>
<section
	class="<?php echo esc_attr( $section_class ); ?>"
	<?php echo $has_title ? 'aria-labelledby="' . esc_attr( $section_id ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr applied. ?>
	<?php
	foreach ( $section_data_attrs as $attr_name => $attr_value ) {
		$attr_name = (string) $attr_name;
		if ( '' === $attr_name ) {
			continue;
		}
		if ( '' === (string) $attr_value ) {
			echo esc_attr( $attr_name ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr applied.
		} else {
			echo esc_attr( $attr_name ) . '="' . esc_attr( (string) $attr_value ) . '" '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr applied.
		}
	}
	?>
	data-ts-product-carousel
	data-ts-columns-mobile="<?php echo esc_attr( (string) $columns_mobile ); ?>"
	data-ts-columns-desktop="<?php echo esc_attr( (string) $columns_desktop ); ?>"
	<?php if ( null !== $columns_wide ) : ?>
		data-ts-columns-wide="<?php echo esc_attr( (string) $columns_wide ); ?>"
	<?php endif; ?>
	data-ts-gap="<?php echo esc_attr( (string) $gap ); ?>"
>
	<header class="product-carousel__header">
		<?php if ( $has_title ) : ?>
			<h2 id="<?php echo esc_attr( $section_id ); ?>" class="<?php echo esc_attr( $title_class ); ?>">
				<?php echo esc_html( $title ); ?>
			</h2>
		<?php endif; ?>
		<div class="product-carousel__nav-group" data-ts-product-carousel-nav hidden>
			<button
				type="button"
				class="product-carousel__nav product-carousel__nav--prev"
				data-ts-product-carousel-prev
				aria-label="<?php esc_attr_e( 'Previous products', 'tailwindscore' ); ?>"
			>
				<span class="product-carousel__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
			</button>
			<button
				type="button"
				class="product-carousel__nav product-carousel__nav--next"
				data-ts-product-carousel-next
				aria-label="<?php esc_attr_e( 'Next products', 'tailwindscore' ); ?>"
			>
				<span class="product-carousel__nav-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
			</button>
		</div>
	</header>
	<div class="product-carousel__content">
		<div class="product-carousel__viewport">
			<div class="swiper product-carousel__swiper woocommerce">
				<?php echo $loop_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce loop markup. ?>
			</div>
		</div>
		<div class="product-carousel__footer" data-ts-product-carousel-controls hidden>
			<div
				class="swiper-pagination product-carousel__pagination"
				data-ts-product-carousel-pagination
			></div>
		</div>
	</div>
</section>
