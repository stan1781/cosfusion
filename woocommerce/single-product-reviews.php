<?php
/**
 * 单品商品评价。
 *
 * @package TailwindScore
 * @see     woocommerce/templates/single-product-reviews.php
 * @version 9.7.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

?>
<div id="reviews" class="woocommerce-Reviews">
	<div id="comments">
		<h2 class="woocommerce-Reviews-title">
			<?php
			$count = $product->get_review_count();
			if ( $count && wc_review_ratings_enabled() ) {
				/* translators: 1: reviews count 2: product name */
				$reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'woocommerce' ) ), esc_html( $count ), '<span>' . get_the_title() . '</span>' );
				echo apply_filters( 'woocommerce_reviews_title', $reviews_title, $count, $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				esc_html_e( 'Reviews', 'woocommerce' );
			}
			?>
		</h2>

		<?php
		/**
		 * 在商品评价列表之前触发（客户照片展示 hero 等）。
		 */
		do_action( 'woocommerce_before_single_product_reviews' );
		?>

		<?php if ( have_comments() ) : ?>
			<ol class="commentlist ts-wc-review-list">
				<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
			</ol>

			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="woocommerce-pagination">';
				paginate_comments_links(
					apply_filters(
						'woocommerce_comment_pagination_args',
						array(
							'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
							'next_text' => is_rtl() ? '&larr;' : '&rarr;',
							'type'      => 'list',
						)
					)
				);
				echo '</nav>';
			endif;
			?>
		<?php else : ?>
			<p class="woocommerce-noreviews"><?php esc_html_e( 'There are no reviews yet.', 'woocommerce' ); ?></p>
		<?php endif; ?>

		<?php tailwindscore_render_review_form_fallback_cta(); ?>
	</div>

	<?php if ( tailwindscore_can_leave_product_review( $product ) ) : ?>
		<?php get_template_part( 'template-parts/woocommerce/review-form-modal' ); ?>
	<?php elseif ( ! tailwindscore_product_has_showcase_images( $product->get_id() ) ) : ?>
		<p class="woocommerce-verification-required"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ); ?></p>
	<?php endif; ?>

	<div class="clear"></div>
</div>
