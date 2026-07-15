<?php
/**
 * 评论评价模板
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.6.0
 */

defined( 'ABSPATH' ) || exit;

global $comment;

$verified = wc_review_is_from_verified_owner( $comment->comment_ID );
?>
<li <?php comment_class( 'ts-wc-review' ); ?> id="li-comment-<?php comment_ID(); ?>">
	<div id="comment-<?php comment_ID(); ?>" class="comment-body">
		<?php
		$rating      = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
		$rating_html = $rating ? wc_get_rating_html( $rating ) : '';
		$author_name = get_comment_author( $comment );
		$date_display = get_comment_date( _x( 'M j, Y', 'review date format', 'tailwindscore' ), $comment );
		$date_iso     = get_comment_date( DATE_W3C, $comment );
		?>

		<div class="ts-wc-review__header">
			<div class="ts-wc-review__avatar">
				<?php echo get_avatar( $comment, 45 ); ?>
			</div>
			<div class="ts-wc-review__meta">
				<div class="ts-wc-review__author-row">
					<strong><?php echo esc_html( $author_name ); ?></strong>
					<?php
					if ( 'review' === $comment->comment_type ) {
						$country_code = get_comment_meta( $comment->comment_ID, '_review_country_code', true );
						if ( empty( $country_code ) ) {
							$country_code = 'US';
						}
						$country_info = function_exists( 'wc_review_get_country_info' ) ? wc_review_get_country_info( $country_code ) : false;
						if ( $country_info ) {
							echo '<span class="ts-wc-review__country">';
							echo '<img src="' . esc_url( $country_info['flag_url'] ) . '" alt="' . esc_attr( $country_info['name'] ) . '" width="20" height="14">';
							echo '<span>' . esc_html( $country_code ) . '</span>';
							echo '</span>';
						}
					}
					?>
					<?php if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $verified ) : ?>
						<span class="ts-wc-review__verified" title="<?php esc_attr_e( 'Verified owner', 'tailwindscore' ); ?>">
							<?php esc_html_e( 'Verified', 'tailwindscore' ); ?>
						</span>
					<?php endif; ?>
				</div>
				<div class="ts-wc-review__rating-row">
					<?php if ( $rating_html ) : ?>
						<div class="ts-wc-review__rating"><?php echo wp_kses_post( $rating_html ); ?></div>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( $date_iso ); ?>"><?php echo esc_html( $date_display ); ?></time>
				</div>
			</div>
		</div>

		<?php do_action( 'woocommerce_review_before_comment_text', $comment ); ?>

		<div class="ts-wc-review__body">
			<div class="ts-wc-review__text">
				<?php do_action( 'woocommerce_review_comment_text', $comment ); ?>
			</div>
			<div class="ts-wc-review__media">
				<?php do_action( 'woocommerce_review_after_comment_text', $comment ); ?>
			</div>
		</div>
	</div>
</li>
