<?php
declare(strict_types=1);

/**
 * 评价摘要卡片（评分 + 打开评价弹窗触发器）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}

if ( ! $product ) {
	return;
}

$can_review   = tailwindscore_can_leave_product_review( $product );
$review_count = (int) $product->get_review_count();
$average      = (float) $product->get_average_rating();
$rating_html  = wc_review_ratings_enabled() && $review_count > 0
	? wc_get_rating_html( $average )
	: '';
?>
<div class="reviews-summary-card">
	<?php if ( wc_review_ratings_enabled() && $review_count > 0 ) : ?>
		<div class="woocommerce-product-rating reviews-summary-card__rating">
			<?php if ( $rating_html ) : ?>
				<div class="reviews-summary-card__stars" aria-hidden="true">
					<?php echo $rating_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
			<p class="reviews-summary-card__score">
				<span class="reviews-summary-card__score-value"><?php echo esc_html( number_format( $average, 1 ) ); ?></span>
				<span class="reviews-summary-card__score-out-of"><?php esc_html_e( 'out of 5', 'tailwindscore' ); ?></span>
			</p>
		</div>
	<?php endif; ?>

	<p class="reviews-summary-card__count">
		<?php
		if ( $review_count > 0 ) {
			echo esc_html(
				sprintf(
					/* translators: %d: number of reviews */
					_n( '%d review', '%d reviews', $review_count, 'tailwindscore' ),
					$review_count
				)
			);
		} else {
			esc_html_e( 'No reviews yet', 'tailwindscore' );
		}
		?>
	</p>

	<?php if ( $can_review ) : ?>
		<button
			type="button"
			class="btn reviews-summary-card__cta"
			data-ts-review-form-open
			aria-haspopup="dialog"
			aria-controls="ts-review-form-modal"
		>
			<?php echo esc_html( tailwindscore_get_review_form_open_label() ); ?>
		</button>
	<?php else : ?>
		<p class="reviews-summary-card__notice woocommerce-verification-required">
			<?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ); ?>
		</p>
	<?php endif; ?>
</div>
