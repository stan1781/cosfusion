<?php
/**
 * Variable product add to cart (custom variation selector).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_type( 'variable' ) ) {
	return;
}

if ( ! function_exists( 'tailwindscore_variations_enabled' ) || ! tailwindscore_variations_enabled() ) {
	wc_get_template(
		'single-product/add-to-cart/variable.php',
		array(
			'attributes'                    => $attributes,
			'available_variations'          => $available_variations,
			'selected_variation'            => $selected_variation,
			'selected_variation_attributes' => $selected_variation_attributes,
		),
		'',
		WC()->plugin_path() . 'templates/'
	);
	return;
}

$product_id                  = $product->get_id();
$color_selector_default_mode = Tailwindscore_Variation_Settings::get_effective_color_selector_default_mode( $product_id );
$color_selector_shape        = Tailwindscore_Variation_Settings::get_color_selector_shape();
$color_grid_show_text        = Tailwindscore_Variation_Settings::grid_show_text();

$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

$ts_variations_client = wp_json_encode(
	array(
		'settings' => Tailwindscore_Variation_Settings::get_frontend_config( $product_id ),
		'i18n'     => array(
			'list'         => __( 'List', 'tailwindscore' ),
			'grid'         => __( 'Grid', 'tailwindscore' ),
			'switchToList' => __( 'Switch to list mode', 'tailwindscore' ),
			'switchToGrid' => __( 'Switch to grid mode', 'tailwindscore' ),
		),
	)
);

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $product_id ); ?>" data-ts-color-mode="<?php echo esc_attr( $color_selector_default_mode ); ?>" data-tailwindscore-variations-config="<?php echo esc_attr( $ts_variations_client ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<div class="variations ts-variations" role="presentation">
			<?php foreach ( $attributes as $attribute_name => $options ) : ?>
				<?php
				$is_color_attribute = tailwindscore_is_color_variation_attribute( $attribute_name, $product_id );
				$is_size_attribute  = tailwindscore_is_size_variation_attribute( $attribute_name );
				$attr_slug          = sanitize_title( $attribute_name );
				?>
				<div class="variation-row ts-variation-row<?php echo $is_size_attribute ? ' ts-variation-row--size' : ''; ?>">
					<div class="label ts-variation-row__label">
						<label for="<?php echo esc_attr( $attr_slug ); ?>">
							<?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>:
							<span class="selected-attribute-value" data-for="<?php echo esc_attr( $attr_slug ); ?>"></span>
						</label>
						<?php if ( $is_color_attribute ) : ?>
							<div class="variation-display-toggle ts-variation-display-toggle" data-attribute="<?php echo esc_attr( $attr_slug ); ?>">
								<button
									type="button"
									class="display-mode-btn ts-variation-display-toggle__btn"
									data-current-mode="<?php echo esc_attr( $color_selector_default_mode ); ?>"
									aria-label="<?php echo esc_attr( 'grid' === $color_selector_default_mode ? __( 'Switch to list mode', 'tailwindscore' ) : __( 'Switch to grid mode', 'tailwindscore' ) ); ?>"
								>
									<span class="display-mode-btn-label"><?php echo esc_html( 'grid' === $color_selector_default_mode ? __( 'List', 'tailwindscore' ) : __( 'Grid', 'tailwindscore' ) ); ?></span>
								</button>
							</div>
						<?php endif; ?>
						<?php
						if ( $is_size_attribute ) {
							/**
							 * After size attribute label (Size Guide trigger in a future module).
							 *
							 * @param string     $attribute_name Attribute key.
							 * @param WC_Product $product        Product.
							 */
							do_action( 'tailwindscore_after_size_attribute_label', $attribute_name, $product );
						}
						?>
					</div>
					<div class="value ts-variation-row__value">
						<?php
						wc_dropdown_variation_attribute_options(
							array(
								'options'          => $options,
								'attribute'        => $attribute_name,
								'product'          => $product,
								'class'            => 'hidden-variation-select',
								'show_option_none' => __( 'Choose an option', 'woocommerce' ),
							)
						);

						$selected = isset( $_REQUEST[ 'attribute_' . $attr_slug ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'attribute_' . $attr_slug ] ) ) : $product->get_variation_default_attribute( $attribute_name ); // phpcs:ignore WordPress.Security.NonceVerification

						$color_classes = ' shape-' . $color_selector_shape;
						if ( $is_color_attribute ) {
							$color_classes .= ' mode-' . $color_selector_default_mode;
							if ( $color_grid_show_text ) {
								$color_classes .= ' show-grid-text';
							}
						}

						printf(
							'<ul class="custom-variation-selector %1$s%2$s" data-attribute="%3$s">',
							esc_attr( $is_color_attribute ? 'color-selector' : 'text-selector' ),
							esc_attr( $color_classes ),
							esc_attr( $attr_slug )
						);

						if ( $options ) {
							if ( taxonomy_exists( $attribute_name ) ) {
								$terms = wc_get_product_terms( $product_id, $attribute_name, array( 'fields' => 'all' ) );

								foreach ( $terms as $term ) {
									if ( ! in_array( $term->slug, $options, true ) ) {
										continue;
									}

									$class                 = ( $selected === $term->slug ) ? 'selected' : '';
									$variation_image       = '';
									$variation_description = '';

									foreach ( $available_variations as $variation ) {
										$attr_key = 'attribute_' . $attr_slug;
										if ( isset( $variation['attributes'][ $attr_key ] ) && $variation['attributes'][ $attr_key ] === $term->slug ) {
											if ( isset( $variation['image']['thumb_src'] ) ) {
												$variation_image = $variation['image']['thumb_src'];
											}
											$variation_description = $variation['variation_description'] ?? '';
											break;
										}
									}

									if ( $is_color_attribute ) {
										echo '<li class="' . esc_attr( $class ) . '" data-value="' . esc_attr( $term->slug ) . '" data-name="' . esc_attr( $term->name ) . '" title="' . esc_attr( $term->name ) . '">';
										echo '<span class="variation-media">';
										if ( $variation_image ) {
											echo '<img src="' . esc_url( $variation_image ) . '" alt="' . esc_attr( $term->name ) . '">';
										} else {
											echo '<span style="background-color:' . esc_attr( $term->slug ) . '"></span>';
										}
										echo '</span><span class="variation-content">';
										echo '<span class="variation-name">' . esc_html( $term->name ) . '</span>';
										if ( $variation_description ) {
											echo '<span class="variation-desc">' . nl2br( esc_html( strip_tags( $variation_description ) ) ) . '</span>';
										}
										echo '</span></li>';
									} else {
										echo '<li class="' . esc_attr( $class ) . '" data-value="' . esc_attr( $term->slug ) . '" data-name="' . esc_attr( $term->name ) . '">' . esc_html( $term->name );
										if ( $variation_description ) {
											echo '<div class="variation-desc">' . nl2br( esc_html( strip_tags( $variation_description ) ) ) . '</div>';
										}
										echo '</li>';
									}
								}
							} else {
								foreach ( $options as $option ) {
									$class                 = ( $selected === $option ) ? 'selected' : '';
									$variation_image       = '';
									$variation_description = '';

									foreach ( $available_variations as $variation ) {
										$attr_key = 'attribute_' . $attr_slug;
										if ( isset( $variation['attributes'][ $attr_key ] ) && $variation['attributes'][ $attr_key ] === $option ) {
											if ( isset( $variation['image']['thumb_src'] ) ) {
												$variation_image = $variation['image']['thumb_src'];
											}
											$variation_description = $variation['variation_description'] ?? '';
											break;
										}
									}

									if ( $is_color_attribute ) {
										echo '<li class="' . esc_attr( $class ) . '" data-value="' . esc_attr( $option ) . '" data-name="' . esc_attr( $option ) . '" title="' . esc_attr( $option ) . '">';
										echo '<span class="variation-media">';
										if ( $variation_image ) {
											echo '<img src="' . esc_url( $variation_image ) . '" alt="' . esc_attr( $option ) . '">';
										} else {
											echo '<span style="background-color:' . esc_attr( $option ) . '"></span>';
										}
										echo '</span><span class="variation-content">';
										echo '<span class="variation-name">' . esc_html( $option ) . '</span>';
										if ( $variation_description ) {
											echo '<span class="variation-desc">' . nl2br( esc_html( strip_tags( $variation_description ) ) ) . '</span>';
										}
										echo '</span></li>';
									} else {
										echo '<li class="' . esc_attr( $class ) . '" data-value="' . esc_attr( $option ) . '" data-name="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</li>';
									}
								}
							}
						}

						echo '</ul>';
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );
			do_action( 'woocommerce_single_variation' );
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
