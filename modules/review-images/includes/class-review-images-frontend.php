<?php
declare(strict_types=1);

/**
 * 评论图片前台展示与评论集成。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 评论图片前台 Hook。
 */
class Tailwindscore_Review_Images_Frontend {

	private static ?self $instance = null;

	/** @var array<string, int>|null */
	private ?array $attachment_global_index_map = null;

	/** @var int|null */
	private ?int $attachment_global_index_product_id = null;

	/** @var array<string, mixed>|null */
	private ?array $lightbox_payload_cache = null;

	/** @var int|null */
	private ?int $lightbox_payload_product_id = null;

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

		add_action( 'woocommerce_before_single_product_reviews', array( $this, 'output_product_lightbox_config' ), 4 );
		add_action( 'woocommerce_review_after_comment_text', array( $this, 'print_review_images_action' ), 10, 1 );
		add_filter( 'comments_array', array( $this, 'sort_comments_by_date_desc' ), 10, 2 );

		if ( ! Tailwindscore_Review_Images_Settings::is_enabled() ) {
			return;
		}

		add_action( 'comment_form_top', array( $this, 'display_image_upload_field' ), 10 );
		add_action( 'comment_post', array( $this, 'attach_images_to_comment' ), 10, 3 );
		add_action( 'delete_comment', array( $this, 'delete_comment_images' ), 10, 2 );
		add_filter( 'wpo_unused_images_ids', array( $this, 'exclude_review_images_from_unused' ), 10, 2 );
		add_filter( 'wpo_keep_unused_images', array( $this, 'mark_review_images_as_used' ), 10, 2 );
		add_action( 'edit_comment', array( $this, 'clear_avatar_cache_on_edit' ), 10, 2 );
		add_filter( 'get_avatar', array( $this, 'force_avatar_refresh' ), 10, 5 );
	}

	/**
	 * @return void
	 */
	public function display_image_upload_field(): void {
		if ( ! is_product() ) {
			return;
		}

		$max_images = Tailwindscore_Review_Images_Settings::get_max_images();
		$max_size   = Tailwindscore_Review_Images_Settings::get_max_file_size();
		$nonce         = wp_create_nonce( 'review_image_upload' );
		$review_client = tailwindscore_review_images_config();
		$client_cfg    = wp_json_encode(
			array(
				'reviewImages'     => $review_client['reviewImages'],
				'reviewImagesI18n' => $review_client['i18n'],
			)
		);
		?>
		<div
			class="review-images-upload"
			id="review-images-upload-wrapper"
			data-tailwindscore-review-images-config="<?php echo esc_attr( (string) $client_cfg ); ?>"
		>
			<input type="hidden" id="review-images-uploaded" name="review_images_uploaded" value="">
			<input type="hidden" id="review-images-nonce" value="<?php echo esc_attr( $nonce ); ?>">
			<div class="review-images-toolbar" id="review-images-toolbar">
				<div class="review-images-toolbar__row">
					<span class="review-images-toolbar__label">
						<?php esc_html_e( 'Photos', 'tailwindscore' ); ?>
						<span class="review-images-toolbar__hint">
							<?php
							printf(
								/* translators: %d: max images */
								esc_html__( '(up to %d)', 'tailwindscore' ),
								(int) $max_images
							);
							?>
						</span>
					</span>
					<input
						type="file"
						id="review-images-input"
						name="review_images[]"
						multiple
						accept="image/jpeg,image/jpg,image/png,image/webp"
						class="screen-reader-text"
						tabindex="-1"
					>
					<label
						for="review-images-input"
						class="btn btn-sm btn-outline"
						id="review-images-select-label"
					>
						<?php esc_html_e( 'Choose images', 'tailwindscore' ); ?>
					</label>
					<span class="review-images-toolbar__count" aria-live="polite">
						<span id="review-images-count">0</span>/<?php echo esc_html( (string) $max_images ); ?>
					</span>
				</div>
				<p class="review-images-toolbar__meta">
					<?php
					printf(
						/* translators: %s: max file size */
						esc_html__( 'JPG, PNG, WebP · max %s each', 'tailwindscore' ),
						esc_html( size_format( $max_size, 1 ) )
					);
					?>
				</p>
			</div>
			<div id="review-images-preview" class="review-images-preview hidden"></div>
			<div id="review-images-progress" class="review-images-progress hidden" aria-hidden="true">
				<div class="review-images-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
					<span class="review-images-progress__fill"></span>
				</div>
			</div>
			<div id="review-images-alerts" class="review-images-alerts"></div>
		</div>
		<?php
	}

	/**
	 * @param int        $comment_id       评论 ID。
	 * @param int|string $comment_approved 审批状态。
	 * @param array      $commentdata      评论数据。
	 * @return void
	 */
	public function attach_images_to_comment( $comment_id, $comment_approved, $commentdata ): void {
		unset( $commentdata );
		$max_images      = Tailwindscore_Review_Images_Settings::get_max_images();
		$image_ids       = array();
		$submitted_ids   = array();

		if ( isset( $_POST['review_images_uploaded'] ) && '' !== (string) $_POST['review_images_uploaded'] ) {
			$decoded = json_decode( wp_unslash( (string) $_POST['review_images_uploaded'] ), true );
			if ( is_array( $decoded ) ) {
				$submitted_ids = array_values( array_filter( array_map( 'absint', $decoded ) ) );
				$image_ids     = array_slice( $submitted_ids, 0, $max_images );
			}
		}

		if ( empty( $image_ids ) ) {
			$image_ids = $this->find_recent_unlinked_review_images( get_current_user_id() );
		}

		if ( empty( $image_ids ) ) {
			return;
		}

		$valid_image_ids = array();
		$user_id         = get_current_user_id();

		foreach ( $image_ids as $image_id ) {
			$image_id = absint( $image_id );
			if ( ! $image_id ) {
				continue;
			}

			$attachment = get_post( $image_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				continue;
			}

			$is_review_image   = get_post_meta( $image_id, '_review_image', true );
			$linked_comment_id = get_post_meta( $image_id, '_review_comment_id', true );

			if ( ! empty( $linked_comment_id ) && (int) $linked_comment_id === (int) $comment_id ) {
				$valid_image_ids[] = $image_id;
				continue;
			}

			if ( ! empty( $linked_comment_id ) && (int) $linked_comment_id !== (int) $comment_id ) {
				continue;
			}

			if ( ! $is_review_image ) {
				update_post_meta( $image_id, '_review_image', true );
				$is_review_image = true;
			}

			$attachment_author = (int) get_post_field( 'post_author', $image_id );
			$can_attach        = false;

			if ( $user_id > 0 ) {
				$can_attach = ( $attachment_author === $user_id )
					|| ( 0 === $attachment_author )
					|| current_user_can( 'moderate_comments' );
			} else {
				$can_attach = ( 0 === $attachment_author );
			}

			if ( $can_attach && $is_review_image ) {
				update_post_meta( $image_id, '_review_comment_id', $comment_id );
				$valid_image_ids[] = $image_id;
			}

			if ( count( $valid_image_ids ) >= $max_images ) {
				break;
			}
		}

		$valid_image_ids = array_values( array_slice( array_unique( $valid_image_ids ), 0, $max_images ) );

		if ( ! empty( $valid_image_ids ) ) {
			update_comment_meta( $comment_id, '_review_images', $valid_image_ids );
		}

		$this->cleanup_unattached_review_submissions( $submitted_ids, $valid_image_ids, $user_id );

		if ( ! get_comment_meta( $comment_id, '_review_country_code', true ) ) {
			update_comment_meta( $comment_id, '_review_country_code', 'US' );
		}
	}

	/**
	 * @param int $user_id 用户 ID。
	 * @return int[]
	 */
	private function find_recent_unlinked_review_images( int $user_id ): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => Tailwindscore_Review_Images_Settings::get_max_images(),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'author'         => $user_id > 0 ? $user_id : 0,
				'date_query'     => array(
					array( 'after' => '2 hours ago' ),
				),
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_review_image',
						'value' => '1',
					),
					array(
						'key'     => '_review_comment_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$ids = array();
		foreach ( $query->posts as $post ) {
			$ids[] = (int) $post->ID;
		}
		wp_reset_postdata();

		return $ids;
	}

	/**
	 * 移除未关联到评论的暂存评论上传。
	 *
	 * @param int[] $submitted_ids   表单 payload 中的 ID。
	 * @param int[] $attached_ids    评论上保存的 ID。
	 * @param int   $user_id         当前用户 ID。
	 * @return void
	 */
	private function cleanup_unattached_review_submissions( array $submitted_ids, array $attached_ids, int $user_id ): void {
		if ( empty( $submitted_ids ) ) {
			return;
		}

		$attached_lookup = array_fill_keys( $attached_ids, true );

		foreach ( $submitted_ids as $image_id ) {
			$image_id = absint( $image_id );
			if ( ! $image_id || isset( $attached_lookup[ $image_id ] ) ) {
				continue;
			}

			if ( ! $this->can_delete_staged_review_attachment( $image_id, $user_id ) ) {
				continue;
			}

			wp_delete_attachment( $image_id, true );
		}
	}

	/**
	 * @param int $attachment_id 附件 ID。
	 * @param int $user_id       当前用户 ID。
	 * @return bool
	 */
	private function can_delete_staged_review_attachment( int $attachment_id, int $user_id ): bool {
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		if ( ! get_post_meta( $attachment_id, '_review_image', true ) ) {
			return false;
		}

		if ( get_post_meta( $attachment_id, '_review_comment_id', true ) ) {
			return false;
		}

		$attachment_author = (int) get_post_field( 'post_author', $attachment_id );

		if ( $user_id > 0 ) {
			return $attachment_author === $user_id || 0 === $attachment_author || current_user_can( 'moderate_comments' );
		}

		return 0 === $attachment_author;
	}

	/**
	 * @param int      $comment_id 评论 ID。
	 * @param WP_Comment $comment  评论对象。
	 * @return void
	 */
	public function delete_comment_images( $comment_id, $comment ): void {
		unset( $comment );
		$image_ids = get_comment_meta( $comment_id, '_review_images', true );
		if ( ! is_array( $image_ids ) || empty( $image_ids ) ) {
			return;
		}

		foreach ( $image_ids as $image_id ) {
			wp_delete_attachment( (int) $image_id, true );
		}
		delete_comment_meta( $comment_id, '_review_images' );
	}

	/**
	 * @param WP_Comment|string $comment 评论。
	 * @return void
	 */
	public function print_review_images_action( $comment ): void {
		if ( ! is_object( $comment ) ) {
			$comment = get_comment( $comment );
		}
		if ( ! $comment ) {
			return;
		}

		$image_ids = get_comment_meta( $comment->comment_ID, '_review_images', true );
		if ( ! is_array( $image_ids ) || empty( $image_ids ) ) {
			return;
		}

		$this->render_comment_images( $image_ids, (int) $comment->comment_ID, $comment );
	}

	/**
	 * 输出统一商品 lightbox payload（展示条 + 评论图片）。
	 *
	 * @return void
	 */
	public function output_product_lightbox_config(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product ) {
			return;
		}

		$payload = $this->build_product_lightbox_payload( (int) $product->get_id() );
		if ( empty( $payload['images'] ) ) {
			return;
		}

		$json = wp_json_encode( $payload );
		if ( ! $json ) {
			return;
		}
		?>
		<script type="application/json" id="ts-product-lightbox-data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
		<?php
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return array{images: array<int, array<string, mixed>>, slides: array<int, array<string, mixed>>}
	 */
	public function build_product_lightbox_payload( int $product_id ): array {
		if ( null !== $this->lightbox_payload_cache && $this->lightbox_payload_product_id === $product_id ) {
			return $this->lightbox_payload_cache;
		}

		$gallery = Tailwindscore_Showcase_Gallery::get_instance();
		if ( ! $gallery ) {
			return array(
				'images' => array(),
				'slides' => array(),
			);
		}

		$images              = array();
		$slides              = array();
		$attachment_index_map = array();

		foreach ( $gallery->get_product_image_ids( $product_id ) as $attachment_id ) {
			$image = $this->build_lightbox_image_from_attachment( (int) $attachment_id );
			if ( ! $image ) {
				continue;
			}

			$attachment_index_map[ (string) $attachment_id ] = count( $images );
			$images[] = $image;
			$slides[] = $this->get_slide_meta_for_attachment( (int) $attachment_id );
		}

		$this->attachment_global_index_map        = $attachment_index_map;
		$this->attachment_global_index_product_id = $product_id;
		$this->lightbox_payload_cache             = array(
			'images' => $images,
			'slides' => $slides,
		);
		$this->lightbox_payload_product_id        = $product_id;

		return $this->lightbox_payload_cache;
	}

	/**
	 * @param int $attachment_id 附件 ID。
	 * @return array{full: string, thumb: string, id: int}|null
	 */
	public function build_lightbox_image_from_attachment( int $attachment_id ): ?array {
		$full  = wp_get_attachment_image_url( $attachment_id, 'full' ) ?: wp_get_attachment_image_url( $attachment_id, 'large' );
		$thumb = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		if ( ! $full ) {
			return null;
		}

		return array(
			'full'  => $full,
			'thumb' => $thumb ? $thumb : $full,
			'id'    => $attachment_id,
		);
	}

	/**
	 * @param int $attachment_id 附件 ID。
	 * @return array<string, mixed>
	 */
	private function get_slide_meta_for_attachment( int $attachment_id ): array {
		$comment_id = (int) get_post_meta( $attachment_id, '_review_comment_id', true );
		if ( $comment_id <= 0 ) {
			return array();
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return array();
		}

		$comment_data = $this->get_comment_data_for_lightbox( $comment );
		if ( ! $comment_data ) {
			return array();
		}

		return array(
			'id'               => (int) $comment_data['id'],
			'author'           => $comment_data['author'],
			'date'             => $comment_data['date'],
			'rating'           => (int) $comment_data['rating'],
			'content'          => $comment_data['content'],
			'country_code'     => $comment_data['country_code'],
			'country_name'     => $comment_data['country_name'],
			'country_flag_url' => $comment_data['country_flag_url'],
			'verified'         => (bool) $comment_data['verified'],
		);
	}

	/**
	 * @param int $product_id 商品 ID。
	 * @return array<string, int>
	 */
	private function get_attachment_global_index_map( int $product_id ): array {
		if ( null !== $this->attachment_global_index_map && $this->attachment_global_index_product_id === $product_id ) {
			return $this->attachment_global_index_map;
		}

		$this->build_product_lightbox_payload( $product_id );

		return $this->attachment_global_index_map ?? array();
	}

	/**
	 * @param int $attachment_id 附件 ID。
	 * @param int $product_id    商品 ID。
	 * @return int
	 */
	public function get_global_index_for_attachment( int $attachment_id, int $product_id ): int {
		$map = $this->get_attachment_global_index_map( $product_id );
		return $map[ (string) $attachment_id ] ?? 0;
	}

	private function render_comment_images( array $image_ids, int $comment_id, $comment ): void {
		unset( $image_ids );
		$comment_data = $this->get_comment_data_for_lightbox( $comment );
		if ( ! $comment_data || empty( $comment_data['images'] ) ) {
			return;
		}

		$product_id = (int) $comment->comment_post_ID;
		?>
		<div class="review-images-display" data-comment-id="<?php echo esc_attr( (string) $comment_id ); ?>">
			<?php foreach ( $comment_data['images'] as $image ) : ?>
				<?php
				$attachment_id = (int) ( $image['id'] ?? 0 );
				$full_url      = $image['full'] ?? '';
				$thumb_url     = $image['thumb'] ?? $full_url;
				if ( ! $attachment_id || ! $full_url ) {
					continue;
				}
				$global_index = $this->get_global_index_for_attachment( $attachment_id, $product_id );
				?>
				<button
					type="button"
					class="review-images-display__thumb review-image-thumb"
					data-image-index="<?php echo esc_attr( (string) $global_index ); ?>"
				>
					<img
						src="<?php echo esc_url( $thumb_url ); ?>"
						alt=""
						loading="lazy"
						decoding="async"
						width="80"
						height="80"
					>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param WP_Comment $comment 评论。
	 * @return array<string, mixed>|null
	 */
	public function get_comment_data_for_lightbox( $comment ): ?array {
		if ( ! $comment ) {
			return null;
		}

		$comment_id = (int) $comment->comment_ID;
		$rating     = (int) get_comment_meta( $comment_id, 'rating', true );
		$image_ids  = get_comment_meta( $comment_id, '_review_images', true );
		$images     = array();

		if ( is_array( $image_ids ) ) {
			foreach ( $image_ids as $img_id ) {
				$full_url  = wp_get_attachment_image_url( (int) $img_id, 'full' );
				$thumb_url = wp_get_attachment_image_url( (int) $img_id, 'thumbnail' );
				if ( $full_url ) {
					$images[] = array(
						'full'  => $full_url,
						'thumb' => $thumb_url ? $thumb_url : $full_url,
						'id'    => (int) $img_id,
					);
				}
			}
		}

		$country_code = get_comment_meta( $comment_id, '_review_country_code', true );
		if ( empty( $country_code ) ) {
			$country_code = 'US';
		}
		$country_info = self::get_country_info( (string) $country_code );

		$verified = function_exists( 'wc_review_is_from_verified_owner' )
			&& wc_review_is_from_verified_owner( $comment_id )
			&& 'yes' === get_option( 'woocommerce_review_rating_verification_label' );

		$product_id = (int) $comment->comment_post_ID;
		$product    = wc_get_product( $product_id );

		return array(
			'id'                 => $comment_id,
			'author'             => $comment->comment_author,
			'date'               => get_comment_date( 'm/d/Y', $comment_id ),
			'rating'             => $rating,
			'content'            => trim( preg_replace( '/\s+/', ' ', (string) $comment->comment_content ) ),
			'images'             => $images,
			'country_code'       => $country_info['code'] ?? 'US',
			'country_name'       => $country_info['name'] ?? '',
			'country_flag_url'   => $country_info['flag_url'] ?? '',
			'verified'           => $verified,
			'product_id'         => $product_id,
			'product_title'      => $product ? $product->get_name() : '',
			'product_url'        => $product ? get_permalink( $product_id ) : '',
			'product_image_url'  => $product && $product->get_image_id()
				? (string) wp_get_attachment_image_url( $product->get_image_id(), 'medium' )
				: '',
		);
	}

	/**
	 * 支持的国家列表（code => 显示名）。前后台唯一数据源，
	 * 避免 Admin / Frontend 各维护一份易漂移的重复列表。
	 *
	 * @return array<string, string>
	 */
	public static function get_supported_countries(): array {
		return array(
			'US' => __( 'United States', 'tailwindscore' ),
			'GB' => __( 'United Kingdom', 'tailwindscore' ),
			'CA' => __( 'Canada', 'tailwindscore' ),
			'AU' => __( 'Australia', 'tailwindscore' ),
			'CN' => __( 'China', 'tailwindscore' ),
			'JP' => __( 'Japan', 'tailwindscore' ),
			'KR' => __( 'South Korea', 'tailwindscore' ),
			'SG' => __( 'Singapore', 'tailwindscore' ),
			'DE' => __( 'Germany', 'tailwindscore' ),
			'FR' => __( 'France', 'tailwindscore' ),
			'ES' => __( 'Spain', 'tailwindscore' ),
			'IT' => __( 'Italy', 'tailwindscore' ),
			'BR' => __( 'Brazil', 'tailwindscore' ),
			'MX' => __( 'Mexico', 'tailwindscore' ),
			'IN' => __( 'India', 'tailwindscore' ),
			'RU' => __( 'Russia', 'tailwindscore' ),
		);
	}

	/**
	 * @param string $country_code 国家代码。
	 * @return array{code: string, name: string, flag_url: string}
	 */
	public static function get_country_info( string $country_code ): array {
		$country_code = strtoupper( $country_code );
		$countries    = self::get_supported_countries();

		if ( ! isset( $countries[ $country_code ] ) ) {
			$country_code = 'US';
		}

		return array(
			'code'     => $country_code,
			'name'     => $countries[ $country_code ] ?? $countries['US'],
			'flag_url' => 'https://flagcdn.com/w20/' . strtolower( $country_code ) . '.png',
		);
	}

	/**
	 * @param array $comments 评论列表。
	 * @param int   $post_id  文章 ID。
	 * @return array
	 */
	public function sort_comments_by_date_desc( $comments, $post_id ) {
		unset( $post_id );
		if ( ! is_product() || empty( $comments ) ) {
			return $comments;
		}

		usort(
			$comments,
			static function ( $a, $b ) {
				$time_a = strtotime( $a->comment_date_gmt );
				$time_b = strtotime( $b->comment_date_gmt );
				return $time_b <=> $time_a;
			}
		);

		return $comments;
	}

	/**
	 * @param int[] $unused_images_ids ID 列表。
	 * @param int   $blog_id           站点 ID。
	 * @return int[]
	 */
	public function exclude_review_images_from_unused( $unused_images_ids, $blog_id = 1 ) {
		unset( $blog_id );
		if ( ! is_array( $unused_images_ids ) || empty( $unused_images_ids ) ) {
			return $unused_images_ids;
		}

		return array_values( array_diff( $unused_images_ids, self::get_all_review_image_ids() ) );
	}

	/**
	 * @param bool $keep            保留标记。
	 * @param int  $attachment_id   附件 ID。
	 * @return bool
	 */
	public function mark_review_images_as_used( $keep, $attachment_id ) {
		if ( $keep ) {
			return $keep;
		}

		return in_array( (int) $attachment_id, self::get_all_review_image_ids(), true ) ? true : $keep;
	}

	/**
	 * 所有已挂载到评论的图片 ID（跨评论合集，已去重）。
	 * 供 `exclude_review_images_from_unused()` / `mark_review_images_as_used()` 共用，
	 * 避免重复查询与反序列化循环。
	 *
	 * @return int[]
	 */
	private static function get_all_review_image_ids(): array {
		global $wpdb;
		$comment_metas = $wpdb->get_col(
			"SELECT meta_value FROM {$wpdb->commentmeta} WHERE meta_key = '_review_images' AND meta_value != ''"
		);

		$ids = array();
		foreach ( (array) $comment_metas as $meta_value ) {
			$decoded = maybe_unserialize( $meta_value );
			if ( is_array( $decoded ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $decoded ) );
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param int   $comment_id 评论 ID。
	 * @param array $data       评论数据。
	 * @return void
	 */
	public function clear_avatar_cache_on_edit( $comment_id, $data ): void {
		unset( $data );
		update_comment_meta( $comment_id, '_avatar_last_updated', time() );
		clean_comment_cache( $comment_id );
	}

	/**
	 * @param string $avatar        头像 HTML。
	 * @param mixed  $id_or_email   ID 或邮箱。
	 * @param int    $size          尺寸。
	 * @param string $default       默认值。
	 * @param string $alt           alt 文本。
	 * @return string
	 */
	public function force_avatar_refresh( $avatar, $id_or_email, $size, $default, $alt ) {
		unset( $size, $default, $alt );
		if ( ! is_product() ) {
			return $avatar;
		}

		$comment = is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ? $id_or_email : null;
		if ( ! $comment || ! get_comment_meta( $comment->comment_ID, '_avatar_last_updated', true ) ) {
			return $avatar;
		}

		return $avatar;
	}
}
