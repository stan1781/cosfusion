<?php
/**
 * 单品评分（主题覆盖）。
 *
 * 相对 WooCommerce `templates/single-product/rating.php` @version 3.6.0：
 * 评价链接文案去掉 customer，使用主题文本域。
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package TailwindScore
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$rating_count = $product->get_rating_count();
$review_count = $product->get_review_count();
$average      = $product->get_average_rating();

if ( $rating_count > 0 ) : ?>

	<div class="woocommerce-product-rating">
		<?php echo wc_get_rating_html( $average, $rating_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( comments_open() ) : ?>
			<a href="#reviews" class="woocommerce-review-link" rel="nofollow">(<?php
				printf(
					/* translators: %s: HTML span with review count */
					_n( '%s review', '%s reviews', $review_count, 'tailwindscore' ),
					'<span class="count">' . esc_html( (string) $review_count ) . '</span>'
				);
			?>)</a>
		<?php endif; ?>
	</div>

	<?php
endif;
