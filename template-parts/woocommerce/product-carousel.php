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
$eyebrow            = isset( $args['eyebrow'] ) ? trim( (string) $args['eyebrow'] ) : '';
$view_all           = isset( $args['view_all'] ) ? trim( (string) $args['view_all'] ) : '';
$view_all_url       = isset( $args['view_all_url'] ) ? trim( (string) $args['view_all_url'] ) : '';
$loop_html          = isset( $args['loop_html'] ) ? (string) $args['loop_html'] : '';
$columns_mobile     = isset( $args['columns_mobile'] )
	? (float) $args['columns_mobile']
	: ( function_exists( 'tailwindscore_get_products_carousel_columns_mobile' ) ? tailwindscore_get_products_carousel_columns_mobile() : 1.5 );
$columns_desktop    = isset( $args['columns_desktop'] )
	? (float) $args['columns_desktop']
	: ( function_exists( 'tailwindscore_get_products_carousel_columns_desktop' ) ? tailwindscore_get_products_carousel_columns_desktop() : 3.0 );
$columns_wide       = isset( $args['columns_wide'] ) && null !== $args['columns_wide'] && '' !== $args['columns_wide']
	? (float) $args['columns_wide']
	: null;
$gap                = array_key_exists( 'gap', $args )
	? ( function_exists( 'tailwindscore_parse_products_carousel_gap_optional' )
		? tailwindscore_parse_products_carousel_gap_optional( $args['gap'] )
		: ( is_numeric( $args['gap'] ) ? (int) $args['gap'] : null ) )
	: null;
$section_class      = isset( $args['section_class'] ) ? trim( (string) $args['section_class'] ) : '';
$title_class        = isset( $args['title_class'] ) ? trim( (string) $args['title_class'] ) : 'section-title--inline';
$section_data_attrs = isset( $args['section_data_attrs'] ) && is_array( $args['section_data_attrs'] )
	? $args['section_data_attrs']
	: array();
$wrap_container = ! empty( $args['wrap_container'] );

$display  = function_exists( 'tailwindscore_normalize_products_carousel_display' )
	? tailwindscore_normalize_products_carousel_display( $args['card'] ?? 'grid', $args['carousel'] ?? '1' )
	: array(
		'card'     => 'grid',
		'carousel' => '1',
	);
$card     = $display['card'];
$carousel = $display['carousel'];

if ( ! isset( $section_data_attrs['data-ts-card'] ) ) {
	$section_data_attrs['data-ts-card'] = $card;
}
if ( ! isset( $section_data_attrs['data-ts-carousel'] ) ) {
	$section_data_attrs['data-ts-carousel'] = $carousel;
}

if ( '' === trim( $loop_html ) ) {
	return;
}

$has_title     = '' !== trim( $title );
$has_eyebrow   = '' !== $eyebrow;
$has_view_all  = '' !== $view_all && '' !== $view_all_url;
// eyebrow / view_all 时用 featured 头（大标题 + 右侧链接）；否则沿用 section-title--inline。
$use_featured_header = $has_eyebrow || $has_view_all;
$has_header          = $has_title || $has_eyebrow || $has_view_all;
$section_class       = trim( 'product-carousel ' . $section_class );

$is_static = '0' === $carousel;

// 轮播 foreshadow + 静态 grid 共用列 CSS 变量。
$cols_style = sprintf(
	'--ts-product-carousel-cols-mobile:%s;--ts-product-carousel-cols-desktop:%s;',
	(string) $columns_mobile,
	(string) $columns_desktop
);
if ( null !== $columns_wide ) {
	$cols_style .= sprintf( '--ts-product-carousel-cols-wide:%s;', (string) $columns_wide );
}

$static_style = $cols_style;
if ( $is_static && function_exists( 'tailwindscore_products_carousel_static_columns' ) ) {
	$cols_mobile  = tailwindscore_products_carousel_static_columns( $columns_mobile );
	$cols_desktop = tailwindscore_products_carousel_static_columns( $columns_desktop );
	$static_style = sprintf(
		'--ts-product-carousel-cols-mobile:%d;--ts-product-carousel-cols-desktop:%d;',
		$cols_mobile,
		$cols_desktop
	);
	if ( null !== $columns_wide ) {
		$static_style .= sprintf(
			'--ts-product-carousel-cols-wide:%d;',
			tailwindscore_products_carousel_static_columns( $columns_wide )
		);
	}
}

if ( null !== $gap ) {
	$static_style .= sprintf( '--ts-product-carousel-gap:%dpx;', max( 0, min( 48, $gap ) ) );
}

$section_style = $is_static ? $static_style : $cols_style;
if ( ! $is_static && null !== $gap ) {
	$section_style .= sprintf( '--ts-product-carousel-gap:%dpx;', max( 0, min( 48, $gap ) ) );
}
?>
<section
	class="<?php echo esc_attr( $section_class ); ?>"
	<?php echo $has_title ? 'aria-labelledby="' . esc_attr( $section_id ) . '"' : ( $has_eyebrow ? 'aria-label="' . esc_attr( $eyebrow ) . '"' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr applied. ?>
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
	<?php if ( null !== $gap ) : ?>
		data-ts-gap="<?php echo esc_attr( (string) $gap ); ?>"
	<?php endif; ?>
	<?php if ( '' !== $section_style ) : ?>
		style="<?php echo esc_attr( $section_style ); ?>"
	<?php endif; ?>
>
	<?php if ( $wrap_container ) : ?>
		<div class="container">
	<?php endif; ?>
	<?php if ( $has_header ) : ?>
	<header class="product-carousel__header<?php echo $use_featured_header ? ' product-carousel__header--featured' : ''; ?>">
		<?php if ( $use_featured_header ) : ?>
			<div class="product-carousel__heading">
				<?php if ( $has_eyebrow ) : ?>
					<p class="product-carousel__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( $has_title ) : ?>
					<h2 id="<?php echo esc_attr( $section_id ); ?>" class="product-carousel__title"><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>
			</div>
			<div class="product-carousel__header-actions">
				<?php if ( $has_view_all ) : ?>
					<a class="product-carousel__view-all" href="<?php echo esc_url( $view_all_url ); ?>">
						<span class="product-carousel__view-all-label"><?php echo esc_html( $view_all ); ?></span>
						<span class="product-carousel__view-all-icon" aria-hidden="true"><?php echo function_exists( 'tailwindscore_icon' ) ? tailwindscore_icon( 'chevron-right' ) : '&rarr;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG from theme map. ?></span>
					</a>
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
			</div>
		<?php else : ?>
			<?php if ( $has_title ) : ?>
				<?php
				tailwindscore_render_section_title(
					$title,
					array(
						'id'    => $section_id,
						'class' => $title_class,
					)
				);
				?>
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
		<?php endif; ?>
	</header>
	<?php else : ?>
	<header class="product-carousel__header">
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
	<?php endif; ?>
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
	<?php if ( $wrap_container ) : ?>
		</div>
	<?php endif; ?>
</section>
