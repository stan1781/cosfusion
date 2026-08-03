<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 内容区块 — 后台 metabox、保存与列表 Shortcode 列。
 */
final class Tailwindscore_Content_Block_Admin {

	private const NONCE_ACTION = 'tailwindscore_content_block_save';
	private const NONCE_FIELD  = 'tailwindscore_content_block_nonce';

	/** @var string HTML 保存时从正文中提取、待合并进 CSS meta 的样式。 */
	private static string $pending_extracted_css = '';

	public static function init(): void {
		add_action( 'add_meta_boxes', array( self::class, 'register_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_preview_assets' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'filter_post_data' ), 10, 2 );
		add_action( 'save_post_' . Tailwindscore_Content_Block_Cpt::POST_TYPE, array( self::class, 'save_block_meta' ), 10, 2 );
		add_filter( 'manage_' . Tailwindscore_Content_Block_Cpt::POST_TYPE . '_posts_columns', array( self::class, 'register_list_columns' ) );
		add_action( 'manage_' . Tailwindscore_Content_Block_Cpt::POST_TYPE . '_posts_custom_column', array( self::class, 'render_list_column' ), 10, 2 );
	}

	public static function register_meta_boxes(): void {
		add_meta_box(
			'ts-content-block-fields',
			__( '区块内容', 'tailwindscore' ),
			array( self::class, 'render_fields_metabox' ),
			Tailwindscore_Content_Block_Cpt::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ts-content-block-shortcode',
			__( '短代码', 'tailwindscore' ),
			array( self::class, 'render_shortcode_metabox' ),
			Tailwindscore_Content_Block_Cpt::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * 仅在 ts_block 编辑页加载预览资源。
	 */
	public static function enqueue_preview_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Tailwindscore_Content_Block_Cpt::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$base = get_template_directory_uri() . '/modules/content-blocks/assets/';
		$ver  = defined( 'TAILWINDSCORE_CONTENT_BLOCKS_VERSION' ) ? TAILWINDSCORE_CONTENT_BLOCKS_VERSION : '0.1.0';

		wp_enqueue_style(
			'tailwindscore-content-block-preview',
			$base . 'admin-preview.css',
			array(),
			$ver
		);

		wp_enqueue_script(
			'tailwindscore-content-block-preview',
			$base . 'admin-preview.js',
			array(),
			$ver,
			true
		);

		wp_localize_script(
			'tailwindscore-content-block-preview',
			'tsContentBlockPreview',
			array(
				'resetCss' => self::preview_reset_css(),
				'tokenCss' => self::preview_token_css(),
			)
		);
	}

	public static function render_fields_metabox( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$html = (string) $post->post_content;
		$css  = get_post_meta( $post->ID, Tailwindscore_Content_Block_Cpt::CSS_META_KEY, true );
		$css  = is_string( $css ) ? $css : '';
		?>
		<div class="content-block-editor" data-ts-block-editor>
			<div class="wp-editor-tools hide-if-no-js content-block-editor__tools">
				<div class="wp-editor-tabs">
					<button
						type="button"
						id="ts-block-tab-code"
						class="wp-switch-editor switch-html content-block-editor__tab is-active"
						data-ts-block-tab="code"
						aria-pressed="true"
					>
						<?php esc_html_e( '代码', 'tailwindscore' ); ?>
					</button>
					<button
						type="button"
						id="ts-block-tab-preview"
						class="wp-switch-editor switch-tmce content-block-editor__tab"
						data-ts-block-tab="preview"
						aria-pressed="false"
					>
						<?php esc_html_e( '预览', 'tailwindscore' ); ?>
					</button>
				</div>
			</div>

			<div
				id="ts-block-panel-code"
				class="content-block-editor__panel"
				data-ts-block-panel="code"
			>
				<p>
					<label for="ts_block_html"><strong><?php esc_html_e( 'HTML', 'tailwindscore' ); ?></strong></label>
				</p>
				<textarea
					id="ts_block_html"
					name="ts_block_html"
					class="large-text code"
					rows="16"
					style="width:100%; font-family: monospace;"
				><?php echo esc_textarea( $html ); ?></textarea>
				<p class="description">
					<?php esc_html_e( '可嵌套 WooCommerce 等短代码，例如 [ts_products_carousel limit="4"]。', 'tailwindscore' ); ?>
					<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
						<?php esc_html_e( '具备完整权限时可包含 <script>；保存时会自动将 <style> 移至 CSS 字段。', 'tailwindscore' ); ?>
					<?php else : ?>
						<?php esc_html_e( '当前账号无法保存 <script> 或 <style> 标签。', 'tailwindscore' ); ?>
					<?php endif; ?>
				</p>
				<p>
					<label for="ts_block_css"><strong><?php esc_html_e( 'CSS', 'tailwindscore' ); ?></strong></label>
				</p>
				<textarea
					id="ts_block_css"
					name="ts_block_css"
					class="large-text code"
					rows="10"
					style="width:100%; font-family: monospace;"
				><?php echo esc_textarea( $css ); ?></textarea>
				<p class="description">
					<?php esc_html_e( '可直接贴上纯 CSS，或使用 <style>...</style> 包裹（AI 生成内容常见格式）。', 'tailwindscore' ); ?>
				</p>
				<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?>
					<p class="description">
						<?php esc_html_e( '当前账号无 unfiltered_html 权限，保存时 HTML 会经过安全过滤。', 'tailwindscore' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div
				id="ts-block-panel-preview"
				class="content-block-editor__panel"
				data-ts-block-panel="preview"
				hidden
			>
				<p class="description content-block-preview-hint">
					<?php esc_html_e( '预览区块 HTML / CSS 与内联 <script>；不展开短代码，不加载主题 TypeScript。切换至此分页时会重新渲染。', 'tailwindscore' ); ?>
				</p>
				<div class="content-block-preview-frame-wrap">
					<iframe
						id="ts-block-preview-frame"
						title="<?php esc_attr_e( '内容区块预览', 'tailwindscore' ); ?>"
						sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
					></iframe>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * 预览 iframe 基础 reset（不引入主题 app 样式）。
	 */
	private static function preview_reset_css(): string {
		return <<<'CSS'
*,
*::before,
*::after {
	box-sizing: border-box;
}
html,
body {
	margin: 0;
	padding: 0;
	background: var(--ts-surface, #fff);
	color: var(--ts-text-primary, #181818);
	font-family: var(--ts-font-body, system-ui, sans-serif);
	font-size: var(--ts-font-size-base, 1rem);
	line-height: var(--ts-line-height-base, 1.5);
}
img {
	max-width: 100%;
	height: auto;
}
CSS;
	}

	/**
	 * 最小 --ts-*（结构 token），供区块 CSS 使用。
	 */
	private static function preview_token_css(): string {
		return <<<'CSS'
:root {
	--ts-primary: #181818;
	--ts-on-primary: #ffffff;
	--ts-secondary: #faf8f6;
	--ts-on-secondary: #181818;
	--ts-surface: #ffffff;
	--ts-surface-alt: #faf9f7;
	--ts-surface-muted: #faf8f6;
	--ts-text-primary: #181818;
	--ts-text-secondary: #4a4a4a;
	--ts-text-muted: #4a4a4a;
	--ts-border-color: #ece6df;
	--ts-border-color-strong: #d8cec4;
	--ts-link-color: var(--ts-primary);
	--ts-success: #198754;
	--ts-info: #0d6efd;
	--ts-warning: #f5a623;
	--ts-danger: #d0021b;
	--ts-overlay: rgb(0 0 0 / 45%);
	--ts-overlay-dark: rgb(0 0 0 / 60%);
	--ts-spacer: 1rem;
	--ts-spacer-0: 0;
	--ts-spacer-1: 0.25rem;
	--ts-spacer-2: 0.5rem;
	--ts-spacer-3: 1rem;
	--ts-spacer-4: 1.5rem;
	--ts-spacer-5: 3rem;
	--ts-border-radius-sm: 0.25rem;
	--ts-border-radius: 0.5rem;
	--ts-border-radius-lg: 0.75rem;
	--ts-border-radius-pill: 50rem;
	--ts-font-size-xs: 0.75rem;
	--ts-font-size-sm: 0.875rem;
	--ts-font-size-base: 1rem;
	--ts-font-size-lg: 1.125rem;
	--ts-line-height-base: 1.5;
	--ts-font-body: "Inter", ui-sans-serif, system-ui, sans-serif;
	--ts-font-heading: "Playfair Display", Georgia, "Times New Roman", serif;
	--ts-container-max: 72rem;
	--ts-shadow-sm: 0 1px 2px rgb(0 0 0 / 4%);
	--ts-shadow-md: 0 4px 12px rgb(0 0 0 / 8%);
	--ts-shadow-lg: 0 12px 32px rgb(0 0 0 / 12%);
	--ts-control-min-height: 2.5rem;
	--ts-btn-padding-y: 0.5rem;
	--ts-btn-padding-x: 1rem;
	color-scheme: light;
}
CSS;
	}

	public static function render_shortcode_metabox( WP_Post $post ): void {
		$slug     = (string) $post->post_name;
		$post_id  = (int) $post->ID;
		$is_saved = $post_id > 0 && 'auto-draft' !== $post->post_status;
		?>
		<p>
			<label for="ts_block_slug"><strong><?php esc_html_e( 'Slug', 'tailwindscore' ); ?></strong></label>
		</p>
		<input
			type="text"
			id="ts_block_slug"
			name="ts_block_slug"
			value="<?php echo esc_attr( $slug ); ?>"
			class="widefat code"
			autocomplete="off"
			spellcheck="false"
		/>
		<p class="description">
			<?php esc_html_e( '标题可用任意语言；slug 建议英文短横线（如 seo-faq-cta）。留空则保存时由标题自动生成。更改 slug 后请同步更新页面中已嵌入的短代码。', 'tailwindscore' ); ?>
		</p>

		<p><strong><?php esc_html_e( '短代码（slug）', 'tailwindscore' ); ?></strong></p>
		<?php if ( '' !== $slug ) : ?>
			<textarea
				readonly
				class="large-text code"
				rows="2"
				style="width:100%; background:#f6f7f7;"
				onclick="this.select();"
			>[block id="<?php echo esc_attr( $slug ); ?>"]</textarea>
		<?php else : ?>
			<p class="description"><?php esc_html_e( '保存后生成。', 'tailwindscore' ); ?></p>
		<?php endif; ?>

		<p><strong><?php esc_html_e( '短代码（ID）', 'tailwindscore' ); ?></strong></p>
		<?php if ( $is_saved ) : ?>
			<textarea
				readonly
				class="large-text code"
				rows="2"
				style="width:100%; background:#f6f7f7;"
				onclick="this.select();"
			>[block id="<?php echo esc_attr( (string) $post_id ); ?>"]</textarea>
			<p class="description">
				<?php esc_html_e( '数字 ID 为稳定引用；slug 变更不影响此短代码。', 'tailwindscore' ); ?>
			</p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( '保存后可用。', 'tailwindscore' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param array<string, mixed> $data    Slashed post data.
	 * @param array<string, mixed> $postarr Raw post array.
	 * @return array<string, mixed>
	 */
	public static function filter_post_data( array $data, array $postarr ): array {
		if ( Tailwindscore_Content_Block_Cpt::POST_TYPE !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return $data;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return $data;
		}

		$post_id = (int) ( $postarr['ID'] ?? 0 );
		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			return $data;
		}
		if ( 0 === $post_id && ! current_user_can( 'edit_posts' ) ) {
			return $data;
		}

		if ( isset( $_POST['ts_block_html'] ) ) {
			$html = self::sanitize_html( wp_unslash( (string) $_POST['ts_block_html'] ) );
			list( $html, $extracted_css ) = Tailwindscore_Content_Block_Content::extract_styles_from_html( $html );
			self::$pending_extracted_css   = $extracted_css;
			$data['post_content']          = $html;
		}

		if ( isset( $_POST['ts_block_slug'] ) ) {
			$slug = sanitize_title( wp_unslash( (string) $_POST['ts_block_slug'] ) );
			if ( '' !== $slug ) {
				$post_status = (string) ( $data['post_status'] ?? 'draft' );
				$post_parent = (int) ( $data['post_parent'] ?? 0 );
				$data['post_name'] = wp_unique_post_slug(
					$slug,
					$post_id,
					$post_status,
					Tailwindscore_Content_Block_Cpt::POST_TYPE,
					$post_parent
				);
			}
		}

		return $data;
	}

	public static function save_block_meta( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['ts_block_css'] ) ) {
			return;
		}

		$css = self::sanitize_css( wp_unslash( (string) $_POST['ts_block_css'] ) );
		$css = Tailwindscore_Content_Block_Content::merge_css( $css, self::$pending_extracted_css );
		self::$pending_extracted_css = '';

		if ( '' === $css ) {
			delete_post_meta( $post_id, Tailwindscore_Content_Block_Cpt::CSS_META_KEY );
			return;
		}

		update_post_meta( $post_id, Tailwindscore_Content_Block_Cpt::CSS_META_KEY, $css );
	}

	/**
	 * @param array<string, string> $columns List table columns.
	 * @return array<string, string>
	 */
	public static function register_list_columns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['shortcode'] = __( '短代码', 'tailwindscore' );
			}
		}

		return $new_columns;
	}

	public static function render_list_column( string $column, int $post_id ): void {
		if ( 'shortcode' !== $column ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$slug      = (string) $post->post_name;
		$shortcode = '' !== $slug
			? '[block id="' . $slug . '"]'
			: '[block id="' . $post_id . '"]';
		?>
		<textarea
			readonly
			style="min-width:100%; max-height:32px; background:#eee; font-family: monospace;"
			onclick="this.select();"
		><?php echo esc_textarea( $shortcode ); ?></textarea>
		<p style="margin:4px 0 0; color:#646970; font-size:12px;">
			<?php
			printf(
				/* translators: %d: content block post ID */
				esc_html__( 'ID: %d', 'tailwindscore' ),
				$post_id
			);
			?>
		</p>
		<?php
	}

	private static function sanitize_html( string $html ): string {
		if ( current_user_can( 'unfiltered_html' ) ) {
			return $html;
		}

		return wp_kses_post( $html );
	}

	private static function sanitize_css( string $css ): string {
		if ( current_user_can( 'unfiltered_html' ) ) {
			return $css;
		}

		return wp_strip_all_tags( $css );
	}
}
