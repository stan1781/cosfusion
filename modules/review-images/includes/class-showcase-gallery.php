<?php
declare(strict_types=1);

/**
 * Product showcase gallery (customer photos strip).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Showcase gallery for approved review images and admin-curated photos.
 */
class Tailwindscore_Showcase_Gallery {

	private static ?self $instance = null;

	private int $cache_duration = 3600;
	private int $max_display    = 50;

	/**
	 * @return self|null
	 */
	public static function get_instance(): ?self {
		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function __construct() {
		self::$instance = $this;

		add_action( 'add_meta_boxes', array( $this, 'add_product_showcase_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_product_showcase_meta_box' ) );
		add_action( 'woocommerce_before_single_product_reviews', array( $this, 'render_reviews_hero' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'updated_comment_meta', array( $this, 'clear_cache_on_comment_update' ), 10, 4 );
		add_action( 'deleted_comment_meta', array( $this, 'clear_cache_on_comment_delete' ), 10, 3 );
		add_action( 'comment_post', array( $this, 'clear_cache_on_comment_post' ), 10, 3 );
		add_action( 'delete_comment', array( $this, 'clear_cache_on_comment_delete_action' ), 10, 1 );
		add_filter( 'wpo_unused_images_ids', array( $this, 'exclude_showcase_images_from_unused' ), 10, 2 );
		add_filter( 'wpo_keep_unused_images', array( $this, 'mark_showcase_images_as_used' ), 10, 2 );
	}

	/**
	 * @return void
	 */
	public function add_product_showcase_meta_box(): void {
		add_meta_box(
			'product_showcase_gallery',
			__( 'Customer photos showcase', 'tailwindscore' ),
			array( $this, 'display_product_showcase_meta_box' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function display_product_showcase_meta_box( $post ): void {
		wp_nonce_field( 'save_product_showcase_gallery', '_product_showcase_gallery_nonce' );
		$image_ids  = get_post_meta( $post->ID, '_product_showcase_images', true );
		$image_ids  = is_array( $image_ids ) ? $image_ids : array();
		$ids_string = implode( ',', array_map( 'absint', $image_ids ) );
		?>
		<div id="wc-product-showcase-gallery-admin">
			<p class="description"><?php esc_html_e( 'Curated customer photos shown in the product showcase strip.', 'tailwindscore' ); ?></p>
			<div id="wc-product-showcase-gallery-admin-list" class="ts-showcase-admin-list">
				<?php foreach ( $image_ids as $img_id ) : ?>
					<?php $thumb = wp_get_attachment_image_url( (int) $img_id, 'thumbnail' ); ?>
					<?php if ( ! $thumb ) { continue; } ?>
					<div class="ts-showcase-admin-list__item" data-id="<?php echo esc_attr( (string) $img_id ); ?>">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="">
						<button type="button" class="delete-showcase-image" aria-label="<?php esc_attr_e( 'Remove', 'tailwindscore' ); ?>">&times;</button>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button" id="wc-product-showcase-gallery-admin-add"><?php esc_html_e( 'Add images', 'tailwindscore' ); ?></button>
			</p>
			<input type="hidden" id="wc-product-showcase-gallery-admin-ids" name="_product_showcase_images" value="<?php echo esc_attr( $ids_string ); ?>">
		</div>
		<?php
	}

	/**
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_product_showcase_meta_box( $post_id ): void {
		if ( ! isset( $_POST['_product_showcase_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['_product_showcase_gallery_nonce'] ) ), 'save_product_showcase_gallery' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( isset( $_POST['_product_showcase_images'] ) ) {
			$raw     = sanitize_text_field( wp_unslash( (string) $_POST['_product_showcase_images'] ) );
			$new_ids = array();
			foreach ( explode( ',', $raw ) as $id ) {
				$id = absint( trim( $id ) );
				if ( $id > 0 && 'attachment' === get_post_type( $id ) && str_starts_with( (string) get_post_mime_type( $id ), 'image/' ) ) {
					$new_ids[] = $id;
				}
			}
			update_post_meta( $post_id, '_product_showcase_images', array_unique( $new_ids ) );
		} else {
			delete_post_meta( $post_id, '_product_showcase_images' );
		}

		$this->clear_cache( (int) $post_id );
	}

	/**
	 * @param string $hook Hook suffix.
	 * @return void
	 */
	public function admin_enqueue_scripts( string $hook ): void {
		global $post_type;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== $post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'ts-product-showcase-gallery-admin',
			get_template_directory_uri() . '/modules/review-images/assets/admin/product-showcase-gallery-admin.js',
			array( 'jquery' ),
			TAILWINDSCORE_MODULE_REVIEW_IMAGES_VERSION,
			true
		);
	}

	/**
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	public function get_product_image_ids( int $product_id ): array {
		return $this->get_all_showcase_images( $product_id );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	private function get_all_showcase_images( int $product_id ): array {
		$cache_key = 'product_showcase_images_' . $product_id;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$all_image_ids = array();
		$admin_ids     = get_post_meta( $product_id, '_product_showcase_images', true );
		if ( is_array( $admin_ids ) ) {
			$all_image_ids = array_merge( $all_image_ids, $admin_ids );
		}

		$comments = get_comments(
			array(
				'post_id' => $product_id,
				'type'    => 'review',
				'status'  => 'approve',
				'number'  => 999,
			)
		);

		foreach ( $comments as $comment ) {
			$comment_image_ids = get_comment_meta( $comment->comment_ID, '_review_images', true );
			if ( is_array( $comment_image_ids ) ) {
				$all_image_ids = array_merge( $all_image_ids, $comment_image_ids );
			}
		}

		$valid = array();
		foreach ( array_unique( array_map( 'absint', $all_image_ids ) ) as $image_id ) {
			$attachment = get_post( $image_id );
			if ( $attachment && 'attachment' === $attachment->post_type && str_starts_with( (string) get_post_mime_type( $image_id ), 'image/' ) ) {
				$valid[] = $image_id;
			}
		}

		$valid = array_slice( $valid, 0, $this->max_display );
		set_transient( $cache_key, $valid, $this->cache_duration );
		return $valid;
	}

	/**
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private function clear_cache( int $product_id ): void {
		delete_transient( 'product_showcase_images_' . $product_id );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function has_showcase_images( int $product_id ): bool {
		$frontend = Tailwindscore_Review_Images_Frontend::get_instance();
		if ( ! $frontend ) {
			return false;
		}

		$payload = $frontend->build_product_lightbox_payload( $product_id );

		return ! empty( $payload['images'] );
	}

	/**
	 * @return void
	 */
	public function render_reviews_hero(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}

		if ( ! $product || ! $this->has_showcase_images( $product->get_id() ) ) {
			return;
		}

		get_template_part( 'template-parts/woocommerce/reviews-hero' );
	}

	/**
	 * Showcase gallery strip (no hero wrapper).
	 *
	 * @return void
	 */
	public function render_showcase_gallery_strip(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}

		if ( ! $product ) {
			return;
		}

		$frontend = Tailwindscore_Review_Images_Frontend::get_instance();
		if ( ! $frontend ) {
			return;
		}

		$payload = $frontend->build_product_lightbox_payload( $product->get_id() );
		if ( empty( $payload['images'] ) ) {
			return;
		}
		?>
		<section class="ts-showcase-gallery product-showcase-gallery" aria-label="<?php esc_attr_e( 'Customer photos', 'tailwindscore' ); ?>">
			<h3 class="ts-showcase-gallery__title"><?php esc_html_e( 'Customer Photos', 'tailwindscore' ); ?></h3>
			<div
				class="ts-showcase-gallery__strip product-showcase-gallery-container"
				id="product-showcase-gallery"
				data-showcase-rows="2"
				data-showcase-thumb="64"
				data-showcase-gap="8"
				data-showcase-more-label="<?php echo esc_attr__( '+%d more', 'tailwindscore' ); ?>"
			>
				<?php foreach ( $payload['images'] as $index => $image ) : ?>
					<button
						type="button"
						class="ts-showcase-gallery__item review-image-thumb"
						data-image-index="<?php echo esc_attr( (string) $index ); ?>"
					>
						<img src="<?php echo esc_url( $image['thumb'] ); ?>" alt="" loading="lazy" width="64" height="64">
					</button>
				<?php endforeach; ?>
				<button
					type="button"
					class="ts-showcase-gallery__item ts-showcase-gallery__more review-image-thumb hidden"
					data-image-index="0"
					aria-label="<?php esc_attr_e( 'View more customer photos', 'tailwindscore' ); ?>"
				>
					<span class="ts-showcase-gallery__more-label">+0</span>
				</button>
			</div>
		</section>
		<?php
	}

	/**
	 * @param int    $meta_id    Meta ID.
	 * @param int    $comment_id Comment ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function clear_cache_on_comment_update( $meta_id, $comment_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_review_images' === $meta_key ) {
			$comment = get_comment( $comment_id );
			if ( $comment && $comment->comment_post_ID ) {
				$this->clear_cache( (int) $comment->comment_post_ID );
			}
		}
	}

	/**
	 * @param int[]  $meta_ids   Meta IDs.
	 * @param int    $comment_id Comment ID.
	 * @param string $meta_key   Meta key.
	 * @return void
	 */
	public function clear_cache_on_comment_delete( $meta_ids, $comment_id, $meta_key ): void {
		unset( $meta_ids );
		if ( '_review_images' === $meta_key ) {
			$comment = get_comment( $comment_id );
			if ( $comment && $comment->comment_post_ID ) {
				$this->clear_cache( (int) $comment->comment_post_ID );
			}
		}
	}

	/**
	 * @param int   $comment_id       Comment ID.
	 * @param int   $comment_approved Approval.
	 * @param array $commentdata      Data.
	 * @return void
	 */
	public function clear_cache_on_comment_post( $comment_id, $comment_approved, $commentdata ): void {
		unset( $comment_id, $comment_approved );
		if ( ! empty( $commentdata['comment_post_ID'] ) ) {
			$this->clear_cache( (int) $commentdata['comment_post_ID'] );
		}
	}

	/**
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	public function clear_cache_on_comment_delete_action( $comment_id ): void {
		$comment = get_comment( $comment_id );
		if ( $comment && $comment->comment_post_ID ) {
			$this->clear_cache( (int) $comment->comment_post_ID );
		}
	}

	/**
	 * @param int[] $unused_images_ids IDs.
	 * @param int   $blog_id           Blog ID.
	 * @return int[]
	 */
	public function exclude_showcase_images_from_unused( $unused_images_ids, $blog_id = 1 ) {
		unset( $blog_id );
		return $this->filter_unused_ids( $unused_images_ids );
	}

	/**
	 * @param bool $keep          Keep.
	 * @param int  $attachment_id Attachment ID.
	 * @return bool
	 */
	public function mark_showcase_images_as_used( $keep, $attachment_id ) {
		if ( $keep ) {
			return $keep;
		}
		global $wpdb;
		$product_metas = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_showcase_images' AND meta_value != ''" );
		foreach ( (array) $product_metas as $meta_value ) {
			$ids = maybe_unserialize( $meta_value );
			if ( is_array( $ids ) && in_array( (int) $attachment_id, array_map( 'intval', $ids ), true ) ) {
				return true;
			}
		}
		return $keep;
	}

	/**
	 * @param int[] $unused_images_ids IDs.
	 * @return int[]
	 */
	private function filter_unused_ids( array $unused_images_ids ): array {
		if ( empty( $unused_images_ids ) ) {
			return $unused_images_ids;
		}
		global $wpdb;
		$protected = array();
		$metas     = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_showcase_images' AND meta_value != ''" );
		foreach ( (array) $metas as $meta_value ) {
			$ids = maybe_unserialize( $meta_value );
			if ( is_array( $ids ) ) {
				$protected = array_merge( $protected, array_map( 'intval', $ids ) );
			}
		}
		return array_values( array_diff( $unused_images_ids, array_unique( $protected ) ) );
	}
}
