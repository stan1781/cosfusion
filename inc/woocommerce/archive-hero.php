<?php
declare(strict_types=1);

/**
 * Shop / taxonomy archive hero (full-width background + title).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

const TAILWINDSCORE_WC_ARCHIVE_HERO_META_KEY = '_ts_archive_hero_bg';

/**
 * Whether the current request uses the archive hero layout.
 */
function tailwindscore_wc_archive_has_hero_layout(): bool {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return false;
	}

	return is_shop() || is_product_category() || is_product_tag();
}

/**
 * Resolve attachment ID from theme_mod or term meta value.
 *
 * @param mixed $value Raw stored value (attachment ID or legacy URL).
 */
function tailwindscore_wc_archive_hero_resolve_attachment_id( $value ): int {
	if ( is_numeric( $value ) ) {
		$attachment_id = absint( $value );

		return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
	}

	$url = is_string( $value ) ? trim( $value ) : '';

	if ( '' === $url ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( $url );

	return $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
}

/**
 * Background attachment ID for the current archive hero.
 */
function tailwindscore_wc_archive_hero_attachment_id(): int {
	if ( is_shop() ) {
		$shop_bg = tailwindscore_wc_archive_hero_resolve_attachment_id(
			tailwindscore_mod( 'shop_page_header_bg', '' )
		);

		if ( $shop_bg > 0 ) {
			return $shop_bg;
		}
	} elseif ( is_product_taxonomy() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$term_bg = tailwindscore_wc_archive_hero_resolve_attachment_id(
				get_term_meta( $term->term_id, TAILWINDSCORE_WC_ARCHIVE_HERO_META_KEY, true )
			);

			if ( $term_bg > 0 ) {
				return $term_bg;
			}
		}
	}

	return tailwindscore_wc_archive_hero_resolve_attachment_id(
		tailwindscore_mod( 'shop_archive_header_bg_default', '' )
	);
}

/**
 * Full-size background URL for the archive hero, if configured.
 */
function tailwindscore_wc_archive_hero_background_url(): string {
	$attachment_id = tailwindscore_wc_archive_hero_attachment_id();

	if ( $attachment_id < 1 ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, 'full' );

	return is_string( $url ) ? $url : '';
}

/**
 * Render full-width archive hero above breadcrumbs.
 */
function tailwindscore_wc_render_archive_hero(): void {
	if ( ! tailwindscore_wc_archive_has_hero_layout() ) {
		return;
	}

	get_template_part( 'template-parts/woocommerce/archive-hero' );
}
add_action( 'woocommerce_before_main_content', 'tailwindscore_wc_render_archive_hero', 15 );

/**
 * Open archive content shell after hero (breadcrumb + product grid).
 */
function tailwindscore_wc_archive_shell_open(): void {
	if ( ! tailwindscore_wc_archive_has_hero_layout() ) {
		return;
	}

	echo '<div class="ts-container ts-section ts-wc-shell ts-wc-shell--archive">';
}
add_action( 'woocommerce_before_main_content', 'tailwindscore_wc_archive_shell_open', 18 );

/**
 * @param string               $cfg     Kirki config id.
 * @param array<string, string> $targets Customizer panel/section IDs keyed by area.
 */
function tailwindscore_wc_archive_hero_kirki_register( string $cfg, array $targets ): void {
	if ( ! class_exists( 'Kirki' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'shop_archive_header_bg_default',
			'label'       => esc_html__( 'Default archive hero background', 'tailwindscore' ),
			'description' => esc_html__( 'Fallback background for product categories and tags without their own hero image.', 'tailwindscore' ),
			'section'     => 'tailwindscore_shop_archive',
			'priority'    => 10,
			'default'     => '',
			'choices'     => array(
				'save_as' => 'id',
			),
		)
	);

	Kirki::add_field(
		$cfg,
		array(
			'type'        => 'image',
			'settings'    => 'shop_page_header_bg',
			'label'       => esc_html__( 'Shop page hero background', 'tailwindscore' ),
			'description' => esc_html__( 'Background for the main shop page. Falls back to the default archive background when empty.', 'tailwindscore' ),
			'section'     => 'tailwindscore_shop_archive',
			'priority'    => 20,
			'default'     => '',
			'choices'     => array(
				'save_as' => 'id',
			),
		)
	);
}
add_action( 'tailwindscore/kirki/register', 'tailwindscore_wc_archive_hero_kirki_register', 10, 2 );

/**
 * Taxonomies that support a per-term archive hero background.
 *
 * @return string[]
 */
function tailwindscore_wc_archive_hero_taxonomies(): array {
	return array( 'product_cat', 'product_tag' );
}

/**
 * @param string $taxonomy Taxonomy slug.
 */
function tailwindscore_wc_archive_hero_term_field_label( string $taxonomy ): string {
	return esc_html__( 'Archive hero background', 'tailwindscore' );
}

/**
 * @param string $taxonomy Taxonomy slug.
 */
function tailwindscore_wc_archive_hero_term_field_description( string $taxonomy ): string {
	return esc_html__( 'Optional full-width background for this archive page. Uses the default archive background when empty.', 'tailwindscore' );
}

/**
 * Add-term form field.
 *
 * @param string $taxonomy Taxonomy slug.
 */
function tailwindscore_wc_archive_hero_add_term_field( string $taxonomy ): void {
	if ( ! in_array( $taxonomy, tailwindscore_wc_archive_hero_taxonomies(), true ) ) {
		return;
	}
	?>
	<div class="form-field term-ts-archive-hero-wrap">
		<label for="ts-archive-hero-bg"><?php echo tailwindscore_wc_archive_hero_term_field_label( $taxonomy ); ?></label>
		<div class="ts-archive-hero-media">
			<div class="ts-archive-hero-media__preview"></div>
			<input type="hidden" id="ts-archive-hero-bg" name="ts_archive_hero_bg" value="" />
			<button type="button" class="button ts-archive-hero-media__upload"><?php esc_html_e( 'Upload image', 'tailwindscore' ); ?></button>
			<button type="button" class="button ts-archive-hero-media__remove" hidden><?php esc_html_e( 'Remove', 'tailwindscore' ); ?></button>
		</div>
		<p class="description"><?php echo tailwindscore_wc_archive_hero_term_field_description( $taxonomy ); ?></p>
	</div>
	<?php
}

/**
 * Edit-term form field.
 *
 * @param WP_Term $term     Term object.
 * @param string  $taxonomy Taxonomy slug.
 */
function tailwindscore_wc_archive_hero_edit_term_field( WP_Term $term, string $taxonomy ): void {
	if ( ! in_array( $taxonomy, tailwindscore_wc_archive_hero_taxonomies(), true ) ) {
		return;
	}

	$attachment_id = tailwindscore_wc_archive_hero_resolve_attachment_id(
		get_term_meta( $term->term_id, TAILWINDSCORE_WC_ARCHIVE_HERO_META_KEY, true )
	);
	$preview_url   = $attachment_id > 0 ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	$preview_url   = is_string( $preview_url ) ? $preview_url : '';
	?>
	<tr class="form-field term-ts-archive-hero-wrap">
		<th scope="row">
			<label for="ts-archive-hero-bg"><?php echo tailwindscore_wc_archive_hero_term_field_label( $taxonomy ); ?></label>
		</th>
		<td>
			<div class="ts-archive-hero-media">
				<div class="ts-archive-hero-media__preview">
					<?php if ( '' !== $preview_url ) : ?>
						<img src="<?php echo esc_url( $preview_url ); ?>" alt="" />
					<?php endif; ?>
				</div>
				<input type="hidden" id="ts-archive-hero-bg" name="ts_archive_hero_bg" value="<?php echo esc_attr( (string) $attachment_id ); ?>" />
				<button type="button" class="button ts-archive-hero-media__upload"><?php esc_html_e( 'Upload image', 'tailwindscore' ); ?></button>
				<button type="button" class="button ts-archive-hero-media__remove" <?php echo $attachment_id > 0 ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'tailwindscore' ); ?></button>
			</div>
			<p class="description"><?php echo tailwindscore_wc_archive_hero_term_field_description( $taxonomy ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Persist term hero background.
 *
 * @param int $term_id Term ID.
 */
function tailwindscore_wc_archive_hero_save_term_meta( int $term_id ): void {
	if ( ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}

	if ( ! isset( $_POST['ts_archive_hero_bg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$attachment_id = tailwindscore_wc_archive_hero_resolve_attachment_id(
		wp_unslash( (string) $_POST['ts_archive_hero_bg'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
	);

	if ( $attachment_id > 0 ) {
		update_term_meta( $term_id, TAILWINDSCORE_WC_ARCHIVE_HERO_META_KEY, $attachment_id );
	} else {
		delete_term_meta( $term_id, TAILWINDSCORE_WC_ARCHIVE_HERO_META_KEY );
	}
}

/**
 * Register taxonomy admin UI hooks.
 */
function tailwindscore_wc_archive_hero_register_term_fields(): void {
	foreach ( tailwindscore_wc_archive_hero_taxonomies() as $taxonomy ) {
		add_action( "{$taxonomy}_add_form_fields", 'tailwindscore_wc_archive_hero_add_term_field' );
		add_action(
			"{$taxonomy}_edit_form_fields",
			static function ( WP_Term $term ) use ( $taxonomy ): void {
				tailwindscore_wc_archive_hero_edit_term_field( $term, $taxonomy );
			},
			10,
			1
		);
		add_action( "created_{$taxonomy}", 'tailwindscore_wc_archive_hero_save_term_meta' );
		add_action( "edited_{$taxonomy}", 'tailwindscore_wc_archive_hero_save_term_meta' );
	}
}
add_action( 'admin_init', 'tailwindscore_wc_archive_hero_register_term_fields' );

/**
 * Media picker for term hero background fields.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 */
function tailwindscore_wc_archive_hero_admin_assets( string $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( (string) $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! in_array( $taxonomy, tailwindscore_wc_archive_hero_taxonomies(), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_register_script( 'tailwindscore-wc-archive-hero-admin', false, array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'tailwindscore-wc-archive-hero-admin' );

	$script = <<<'JS'
(function () {
	const fields = document.querySelectorAll('.term-ts-archive-hero-wrap');

	if (!fields.length || typeof wp === 'undefined' || !wp.media) {
		return;
	}

	fields.forEach((field) => {
		const input = field.querySelector('#ts-archive-hero-bg');
		const preview = field.querySelector('.ts-archive-hero-media__preview');
		const uploadButton = field.querySelector('.ts-archive-hero-media__upload');
		const removeButton = field.querySelector('.ts-archive-hero-media__remove');

		if (!input || !preview || !uploadButton || !removeButton) {
			return;
		}

		let frame = null;

		const renderPreview = (attachment) => {
			preview.innerHTML = '';

			if (!attachment || !attachment.url) {
				removeButton.hidden = true;
				return;
			}

			const img = document.createElement('img');
			img.src = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			img.alt = '';
			preview.appendChild(img);
			removeButton.hidden = false;
		};

		uploadButton.addEventListener('click', (event) => {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: uploadButton.textContent || 'Select image',
				button: { text: uploadButton.textContent || 'Use image' },
				library: { type: 'image' },
				multiple: false,
			});

			frame.on('select', () => {
				const attachment = frame.state().get('selection').first().toJSON();
				input.value = String(attachment.id || '');
				renderPreview(attachment);
			});

			frame.open();
		});

		removeButton.addEventListener('click', (event) => {
			event.preventDefault();
			input.value = '';
			renderPreview(null);
		});
	});
}());
JS;

	wp_add_inline_script( 'tailwindscore-wc-archive-hero-admin', $script );

	wp_register_style( 'tailwindscore-wc-archive-hero-admin', false, array(), '1.0.0' );
	wp_enqueue_style( 'tailwindscore-wc-archive-hero-admin' );
	wp_add_inline_style(
		'tailwindscore-wc-archive-hero-admin',
		'.ts-archive-hero-media__preview img{display:block;max-width:240px;height:auto;margin:0 0 8px;border:1px solid #c3c4c7;border-radius:4px;}'
	);
}
add_action( 'admin_enqueue_scripts', 'tailwindscore_wc_archive_hero_admin_assets' );
