<?php
declare(strict_types=1);

/**
 * Product review form helpers.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current user may leave a review on the product.
 *
 * @param WC_Product|null $product Product.
 * @return bool
 */
function tailwindscore_can_leave_product_review( ?WC_Product $product = null ): bool {
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! $product ) {
		return false;
	}

	return get_option( 'woocommerce_review_rating_verification_required' ) === 'no'
		|| wc_customer_bought_product( '', get_current_user_id(), $product->get_id() );
}

/**
 * Whether the product has customer photos for the showcase strip.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function tailwindscore_product_has_showcase_images( int $product_id ): bool {
	$gallery = Tailwindscore_Showcase_Gallery::get_instance();

	return $gallery ? $gallery->has_showcase_images( $product_id ) : false;
}

/**
 * Label for opening the review form modal.
 *
 * @return string
 */
function tailwindscore_get_review_form_open_label(): string {
	if ( have_comments() ) {
		return esc_html__( 'Write a review', 'tailwindscore' );
	}

	/* translators: %s: product title */
	return sprintf(
		esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'tailwindscore' ),
		get_the_title()
	);
}

/**
 * WooCommerce product review comment_form arguments.
 *
 * @return array<string, mixed>
 */
function tailwindscore_get_product_review_comment_form_args(): array {
	$commenter = wp_get_current_commenter();

	$comment_form = array(
		/* translators: %s is product title */
		'title_reply'          => have_comments() ? esc_html__( 'Add a review', 'woocommerce' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'woocommerce' ), get_the_title() ),
		'title_reply_to'       => esc_html__( 'Leave a Reply to %s', 'woocommerce' ),
		'title_reply_before'   => '<span id="reply-title" class="comment-reply-title screen-reader-text" role="heading" aria-level="3">',
		'title_reply_after'    => '</span>',
		'comment_notes_after'  => '',
		'label_submit'         => esc_html__( 'Submit', 'woocommerce' ),
		'logged_in_as'         => '',
		'comment_field'        => '',
		'class_form'           => 'comment-form ts-review-form',
		'submit_button'        => '<button type="submit" name="%1$s" id="%2$s" class="%3$s ts-button" value="%4$s">%4$s</button>',
		'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
	);

	$name_email_required = (bool) get_option( 'require_name_email', 1 );
	$fields              = array(
		'author' => array(
			'label'        => __( 'Name', 'woocommerce' ),
			'type'         => 'text',
			'value'        => $commenter['comment_author'],
			'required'     => $name_email_required,
			'autocomplete' => 'name',
		),
		'email'  => array(
			'label'        => __( 'Email', 'woocommerce' ),
			'type'         => 'email',
			'value'        => $commenter['comment_author_email'],
			'required'     => $name_email_required,
			'autocomplete' => 'email',
		),
	);

	$comment_form['fields'] = array();

	foreach ( $fields as $key => $field ) {
		$field_html  = '<p class="comment-form-' . esc_attr( $key ) . '">';
		$field_html .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] );

		if ( $field['required'] ) {
			$field_html .= '&nbsp;<span class="required">*</span>';
		}

		$field_html .= '</label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field['type'] ) . '" autocomplete="' . esc_attr( $field['autocomplete'] ) . '" value="' . esc_attr( $field['value'] ) . '" size="30" ' . ( $field['required'] ? 'required' : '' ) . ' /></p>';

		$comment_form['fields'][ $key ] = $field_html;
	}

	$account_page_url = wc_get_page_permalink( 'myaccount' );
	if ( $account_page_url ) {
		/* translators: %s opening and closing link tags respectively */
		$comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'woocommerce' ), '<a href="' . esc_url( $account_page_url ) . '">', '</a>' ) . '</p>';
	}

	if ( wc_review_ratings_enabled() ) {
		$rating_required = wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '';
		$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Your rating', 'woocommerce' ) . $rating_required . '</label><select name="rating" id="rating" ' . ( wc_review_ratings_required() ? 'required' : '' ) . '>
			<option value="">' . esc_html__( 'Rate&hellip;', 'woocommerce' ) . '</option>
			<option value="5">' . esc_html__( 'Perfect', 'woocommerce' ) . '</option>
			<option value="4">' . esc_html__( 'Good', 'woocommerce' ) . '</option>
			<option value="3">' . esc_html__( 'Average', 'woocommerce' ) . '</option>
			<option value="2">' . esc_html__( 'Not that bad', 'woocommerce' ) . '</option>
			<option value="1">' . esc_html__( 'Very poor', 'woocommerce' ) . '</option>
		</select></div>';
	}

	$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

	return apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form );
}

/**
 * Render the product review comment form markup.
 *
 * @return void
 */
function tailwindscore_render_product_review_form(): void {
	?>
	<div id="review_form_wrapper" class="ts-review-form-modal__form-wrap">
		<div id="review_form">
			<?php comment_form( tailwindscore_get_product_review_comment_form_args() ); ?>
		</div>
	</div>
	<?php
}

/**
 * Fallback CTA below the review list when there is no customer photo showcase.
 *
 * @return void
 */
function tailwindscore_render_review_form_fallback_cta(): void {
	global $product;

	if ( ! $product instanceof WC_Product || ! comments_open() ) {
		return;
	}

	if ( ! tailwindscore_can_leave_product_review( $product ) ) {
		return;
	}

	if ( tailwindscore_product_has_showcase_images( $product->get_id() ) ) {
		return;
	}

	$label = tailwindscore_get_review_form_open_label();
	?>
	<p class="ts-reviews-fallback-cta">
		<a
			href="#"
			data-ts-review-form-open
			aria-haspopup="dialog"
			aria-controls="ts-review-form-modal"
			role="button"
		>
			<?php echo esc_html( $label ); ?>
		</a>
	</p>
	<?php
}
