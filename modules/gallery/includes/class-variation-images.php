<?php
declare(strict_types=1);

/**
 * Variation gallery meta + admin UI + available_variation payload.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variation images (no frontend jQuery).
 */
class Tailwindscore_Gallery_Variation_Images {

	/**
	 * @return int
	 */
	private function max_images(): int {
		$max = (int) tailwindscore_mod( 'product_gallery_variation_max_images', 10 );
		return max( 1, min( 20, $max ) );
	}

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_variation_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_images' ), 10, 1 );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_images_to_variation_data' ), 10, 3 );
		add_action( 'admin_footer', array( $this, 'admin_scripts' ) );
		add_filter( 'wpo_get_multiple_image_ids_in_post_meta', array( $this, 'wp_optimize_fields' ) );
		add_action( 'init', array( $this, 'register_rest_meta' ) );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'add_variation_gallery_to_rest' ), 10, 3 );
	}

	/**
	 * Expose `_variation_images` on variation REST meta_data (same key as wc-enhanced-tools).
	 *
	 * @return void
	 */
	public function register_rest_meta(): void {
		register_post_meta(
			'product_variation',
			'_variation_images',
			array(
				'type'          => 'array',
				'single'        => true,
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'auth_callback' => static function (): bool {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}

	/**
	 * @param WP_REST_Response       $response REST response.
	 * @param WC_Product_Variation   $object   Variation.
	 * @param WP_REST_Request        $request  Request.
	 * @return WP_REST_Response
	 */
	public function add_variation_gallery_to_rest( $response, $object, $request ) {
		$data    = $response->get_data();
		$gallery = array();
		$seen    = array();

		$main_id = $object->get_image_id();
		if ( $main_id ) {
			$item = $this->build_rest_image_item( (int) $main_id );
			if ( $item ) {
				$gallery[]       = $item;
				$seen[ $main_id ] = true;
			}
		}

		$variation_images = get_post_meta( $object->get_id(), '_variation_images', true );
		if ( is_array( $variation_images ) ) {
			foreach ( $variation_images as $attachment_id ) {
				$attachment_id = absint( $attachment_id );
				if ( ! $attachment_id || isset( $seen[ $attachment_id ] ) ) {
					continue;
				}
				$item = $this->build_rest_image_item( $attachment_id );
				if ( $item ) {
					$gallery[]                 = $item;
					$seen[ $attachment_id ] = true;
				}
			}
		}

		$data['variation_gallery'] = $gallery;
		$response->set_data( $data );
		return $response;
	}

	/**
	 * @param int $attachment_id Attachment ID.
	 * @return array<string, mixed>|null
	 */
	private function build_rest_image_item( int $attachment_id ): ?array {
		$src = wp_get_attachment_url( $attachment_id );
		if ( ! $src ) {
			return null;
		}
		return array(
			'id'   => $attachment_id,
			'src'  => $src,
			'name' => get_the_title( $attachment_id ),
			'alt'  => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		);
	}

	/**
	 * @param int    $image_id     Attachment ID.
	 * @param bool   $is_main      Main flag.
	 * @return array<string, mixed>|null
	 */
	private function build_image_data( int $image_id, bool $is_main = false ): ?array {
		return tailwindscore_gallery_build_image_item( $image_id, $is_main );
	}

	/**
	 * @param int        $loop           Loop index.
	 * @param array      $variation_data Variation data.
	 * @param WP_Post    $variation      Variation post.
	 * @return void
	 */
	public function render_variation_field( int $loop, array $variation_data, WP_Post $variation ): void {
		$max               = $this->max_images();
		$variation_images  = get_post_meta( $variation->ID, '_variation_images', true );
		if ( ! is_array( $variation_images ) ) {
			$variation_images = array();
		}
		?>
		<div class="form-row form-row-full tailwindscore-variation-images">
			<h4><?php esc_html_e( 'Variation images', 'tailwindscore' ); ?></h4>
			<p class="description">
				<?php
				printf(
					/* translators: %d: max images */
					esc_html__( 'Add up to %d images for this variation.', 'tailwindscore' ),
					$max
				);
				?>
			</p>
			<div class="variation-images-wrapper" data-max="<?php echo esc_attr( (string) $max ); ?>">
				<div class="variation-images-container">
					<?php foreach ( $variation_images as $image_id ) : ?>
						<div class="variation-image-item">
							<?php echo wp_get_attachment_image( absint( $image_id ), 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<input type="hidden" name="variation_images[<?php echo esc_attr( (string) $variation->ID ); ?>][]" value="<?php echo esc_attr( (string) absint( $image_id ) ); ?>">
							<button type="button" class="remove-variation-image button" aria-label="<?php esc_attr_e( 'Remove image', 'tailwindscore' ); ?>">×</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="add-variation-image button" <?php disabled( count( $variation_images ) >= $max ); ?>>
					<?php
					printf(
						/* translators: 1: current count, 2: max */
						esc_html__( 'Add image (%1$d/%2$d)', 'tailwindscore' ),
						count( $variation_images ),
						$max
					);
					?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public function save_variation_images( int $variation_id ): void {
		if ( isset( $_POST['variation_images'][ $variation_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$images = array_map( 'absint', (array) wp_unslash( $_POST['variation_images'][ $variation_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$images = array_slice( array_values( array_filter( $images ) ), 0, $this->max_images() );
			update_post_meta( $variation_id, '_variation_images', $images );
		} else {
			delete_post_meta( $variation_id, '_variation_images' );
		}
	}

	/**
	 * @param array      $variation_data Variation data.
	 * @param WC_Product $product        Parent product.
	 * @param WC_Product $variation      Variation product.
	 * @return array
	 */
	public function add_variation_images_to_variation_data( array $variation_data, WC_Product $product, WC_Product $variation ): array {
		$variation_images = get_post_meta( $variation->get_id(), '_variation_images', true );
		if ( empty( $variation_images ) || ! is_array( $variation_images ) ) {
			return $variation_data;
		}

		$images_data = array();

		if ( $variation->get_image_id() ) {
			$main = $this->build_image_data( (int) $variation->get_image_id(), true );
			if ( $main ) {
				$images_data[] = $main;
			}
		}

		foreach ( $variation_images as $image_id ) {
			$item = $this->build_image_data( absint( $image_id ), false );
			if ( $item ) {
				$images_data[] = $item;
			}
		}

		if ( ! empty( $images_data ) ) {
			$variation_data['variation_images'] = $images_data;
		}

		return $variation_data;
	}

	/**
	 * @return void
	 */
	public function admin_scripts(): void {
		global $post;
		if ( ! $post || 'product' !== get_post_type( $post ) ) {
			return;
		}

		wp_enqueue_media();
		$max = $this->max_images();
		?>
		<script>
		jQuery(function($) {
			const maxImages = <?php echo (int) $max; ?>;

			$(document).on('click', '.add-variation-image', function(e) {
				e.preventDefault();
				const wrapper = $(this).closest('.variation-images-wrapper');
				const container = wrapper.find('.variation-images-container');
				const variation = wrapper.closest('.woocommerce_variation');
				const button = $(this);
				const variationId = variation.find('input[name^="variable_post_id"]').val();
				if (!variationId) return;

				let currentCount = container.find('.variation-image-item').length;
				if (currentCount >= maxImages) return;

				const frame = wp.media({
					title: '<?php echo esc_js( __( 'Select variation images', 'tailwindscore' ) ); ?>',
					multiple: true,
					library: { type: 'image' }
				});

				frame.on('select', function() {
					const attachments = frame.state().get('selection').toJSON();
					attachments.forEach(function(attachment) {
						if (currentCount >= maxImages) return;
						if (container.find('input[value="' + attachment.id + '"]').length) return;
						const thumb = attachment.sizes && attachment.sizes.woocommerce_thumbnail
							? attachment.sizes.woocommerce_thumbnail.url
							: (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url);
						const item = $(
							'<div class="variation-image-item">' +
							'<img src="' + thumb + '" alt="">' +
							'<input type="hidden" name="variation_images[' + variationId + '][]" value="' + attachment.id + '">' +
							'<button type="button" class="remove-variation-image button">×</button>' +
							'</div>'
						);
						container.append(item.hide().fadeIn(200));
						currentCount++;
					});
					const newCount = container.find('.variation-image-item').length;
					button.text('<?php echo esc_js( __( 'Add image', 'tailwindscore' ) ); ?> (' + newCount + '/' + maxImages + ')');
					button.prop('disabled', newCount >= maxImages);
					wrapper.toggleClass('has-images', newCount > 0);
					variation.find('input, select, textarea').first().trigger('change');
				});
				frame.open();
			});

			$(document).on('click', '.remove-variation-image', function(e) {
				e.preventDefault();
				const item = $(this).closest('.variation-image-item');
				const variation = item.closest('.woocommerce_variation');
				const wrapper = item.closest('.variation-images-wrapper');
				const button = wrapper.find('.add-variation-image');
				item.fadeOut(200, function() {
					$(this).remove();
					const newCount = wrapper.find('.variation-image-item').length;
					button.text('<?php echo esc_js( __( 'Add image', 'tailwindscore' ) ); ?> (' + newCount + '/' + maxImages + ')');
					button.prop('disabled', newCount >= maxImages);
					wrapper.toggleClass('has-images', newCount > 0);
					variation.find('input, select, textarea').first().trigger('change');
				});
			});
		});
		</script>
		<style>
			.tailwindscore-variation-images .variation-images-container {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin-bottom: 10px;
				min-height: 80px;
				padding: 10px;
				border: 1px dashed #ccc;
				border-radius: 4px;
			}
			.tailwindscore-variation-images .variation-image-item {
				position: relative;
				width: 80px;
				height: 80px;
				border: 1px solid #ddd;
				border-radius: 4px;
				overflow: hidden;
			}
			.tailwindscore-variation-images .variation-image-item img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}
			.tailwindscore-variation-images .remove-variation-image {
				position: absolute;
				top: -5px;
				right: -5px;
				background: #b32d2e;
				color: #fff;
				border: none;
				border-radius: 50%;
				width: 20px;
				height: 20px;
				line-height: 18px;
				cursor: pointer;
			}
		</style>
		<?php
	}

	/**
	 * @param array<int, string> $fields Fields.
	 * @return array<int, string>
	 */
	public function wp_optimize_fields( array $fields ): array {
		$fields[] = '_variation_images';
		return $fields;
	}
}
