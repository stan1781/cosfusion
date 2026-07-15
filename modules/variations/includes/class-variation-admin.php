<?php
declare(strict_types=1);

/**
 * 后台商品级变体选择器设置。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 变体选择器商品 meta UI。
 */
class Tailwindscore_Variation_Admin {

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_attributes', array( $this, 'render_product_settings' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_settings' ) );
	}

	/**
	 * @return void
	 */
	public function render_product_settings(): void {
		global $post;

		if ( ! $post || ! $post->ID ) {
			return;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$attributes = $product->get_variation_attributes();
		if ( empty( $attributes ) ) {
			return;
		}

		$disabled_attributes = array_map(
			'strtolower',
			Tailwindscore_Variation_Settings::get_product_disabled_image_attributes( $post->ID )
		);
		$color_attributes    = array_map(
			'strtolower',
			Tailwindscore_Variation_Settings::get_color_attributes()
		);
		$global_mode         = (string) tailwindscore_mod( 'variation_color_default_mode', 'grid' );
		if ( ! in_array( $global_mode, array( 'grid', 'list' ), true ) ) {
			$global_mode = 'grid';
		}
		$product_mode = Tailwindscore_Variation_Settings::get_product_color_selector_default_mode_raw( $post->ID );
		?>
		<div class="tailwindscore-variation-admin-settings" style="margin-top:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">
			<h3 style="margin-top:0;"><?php esc_html_e( 'Variation selector display', 'tailwindscore' ); ?></h3>
			<p class="description" style="margin-bottom:15px;">
				<?php esc_html_e( 'Disable image mode per attribute, or override the default color layout for this product.', 'tailwindscore' ); ?>
			</p>
			<p style="margin-bottom:15px;">
				<label for="tailwindscore_variation_product_color_default_mode"><strong><?php esc_html_e( 'Color selector default layout', 'tailwindscore' ); ?></strong></label><br />
				<select name="tailwindscore_variation_product_color_default_mode" id="tailwindscore_variation_product_color_default_mode" style="min-width:220px;margin-top:6px;">
					<option value="" <?php selected( $product_mode, '' ); ?>>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: global mode */
								__( 'Use global default (current: %s)', 'tailwindscore' ),
								'list' === $global_mode ? 'List' : 'Grid'
							)
						);
						?>
					</option>
					<option value="grid" <?php selected( $product_mode, 'grid' ); ?>>Grid</option>
					<option value="list" <?php selected( $product_mode, 'list' ); ?>>List</option>
				</select>
			</p>
			<table class="widefat">
				<thead>
					<tr>
						<th style="padding:10px;"><?php esc_html_e( 'Attribute', 'tailwindscore' ); ?></th>
						<th style="padding:10px;"><?php esc_html_e( 'Global', 'tailwindscore' ); ?></th>
						<th style="padding:10px;"><?php esc_html_e( 'Disable image display', 'tailwindscore' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $attributes as $attribute_name => $options ) : ?>
						<?php
						$attribute_lower = strtolower( $attribute_name );
						$is_global_color = in_array( $attribute_lower, $color_attributes, true );
						$is_disabled     = in_array( $attribute_lower, $disabled_attributes, true );
						$field_name      = 'tailwindscore_variation_disable_image_' . sanitize_key( $attribute_name );
						?>
						<tr>
							<td style="padding:10px;">
								<strong><?php echo esc_html( wc_attribute_label( $attribute_name ) ); ?></strong><br>
								<small style="color:#666;"><?php echo esc_html( $attribute_name ); ?></small>
							</td>
							<td style="padding:10px;">
								<?php if ( $is_global_color ) : ?>
									<span style="color:#46b450;"><?php esc_html_e( 'Image display', 'tailwindscore' ); ?></span>
								<?php else : ?>
									<span style="color:#666;"><?php esc_html_e( 'Text display', 'tailwindscore' ); ?></span>
								<?php endif; ?>
							</td>
							<td style="padding:10px;">
								<label>
									<input
										type="checkbox"
										name="<?php echo esc_attr( $field_name ); ?>"
										value="1"
										<?php checked( $is_disabled, true ); ?>
										<?php disabled( ! $is_global_color ); ?>
									/>
									<?php esc_html_e( 'Disable image display', 'tailwindscore' ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param int $post_id 商品 ID。
	 * @return void
	 */
	public function save_product_settings( int $post_id ): void {
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$attributes       = $product->get_variation_attributes();
		$color_attributes = array_map(
			'strtolower',
			Tailwindscore_Variation_Settings::get_color_attributes()
		);
		$disabled         = array();

		foreach ( $attributes as $attribute_name => $options ) {
			if ( in_array( strtolower( $attribute_name ), $color_attributes, true ) ) {
				$field_name = 'tailwindscore_variation_disable_image_' . sanitize_key( $attribute_name );
				if ( isset( $_POST[ $field_name ] ) && '1' === $_POST[ $field_name ] ) { // phpcs:ignore WordPress.Security.NonceVerification
					$disabled[] = $attribute_name;
				}
			}
		}

		if ( ! empty( $disabled ) ) {
			update_post_meta( $post_id, '_wc_variation_selector_disabled_image_attributes', $disabled );
		} else {
			delete_post_meta( $post_id, '_wc_variation_selector_disabled_image_attributes' );
		}

		$mode = isset( $_POST['tailwindscore_variation_product_color_default_mode'] ) // phpcs:ignore WordPress.Security.NonceVerification
			? sanitize_key( wp_unslash( $_POST['tailwindscore_variation_product_color_default_mode'] ) )
			: '';

		if ( 'grid' === $mode || 'list' === $mode ) {
			update_post_meta( $post_id, '_wc_variation_selector_color_default_mode', $mode );
		} else {
			delete_post_meta( $post_id, '_wc_variation_selector_color_default_mode' );
		}
	}
}
