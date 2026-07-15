<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_id        = $product->get_id();
$product_url       = get_permalink( $product_id );
$product_title     = $product->get_name();
if ( defined( 'WC_AJAX_SEARCH_RENDER' ) && WC_AJAX_SEARCH_RENDER ) {
	$product_price = wc_price( wc_get_price_to_display( $product ) );
} else {
	$product_price = $product->get_price_html();
}
$product_image     = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'thumbnail' );
$product_image_url = $product_image ? $product_image[0] : wc_placeholder_img_src();
?>
<div class="search-suggest__item" role="option">
	<a class="search-suggest__link" href="<?php echo esc_url( $product_url ); ?>">
		<span class="search-suggest__media">
			<img src="<?php echo esc_url( $product_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" width="60" height="60" loading="lazy" decoding="async">
		</span>
		<span class="search-suggest__body">
			<span class="search-suggest__title"><?php echo esc_html( $product_title ); ?></span>
			<span class="search-suggest__price"><?php echo wp_kses_post( $product_price ); ?></span>
		</span>
	</a>
</div>
