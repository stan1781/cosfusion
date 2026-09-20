<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Singular（Page / 特稿 Post）正文 CSS：独立 meta + 前台输出。
 *
 * HTML → post_content；CSS → _ts_singular_css（读兼容旧 _ts_page_css）。
 * 与内容区块共用 Tailwindscore_Content_Block_Content 规范化逻辑。
 */
final class Tailwindscore_Singular_Style {

	public const CSS_META_KEY = '_ts_singular_css';

	/** @deprecated 读兼容；写时只使用 CSS_META_KEY。 */
	public const LEGACY_CSS_META_KEY = '_ts_page_css';

	public const FEATURE_META_KEY = '_ts_feature_layout';

	public const LAYOUT_TEMPLATE_META_KEY = '_ts_layout_template';

	public const CSS_MODE_META_KEY = '_ts_content_css_mode';

	private const NONCE_ACTION = 'tailwindscore_singular_style_save';
	private const NONCE_FIELD  = 'tailwindscore_singular_style_nonce';

	/** @var string 保存时从正文提取、待合并进 CSS meta 的样式。 */
	private static string $pending_extracted_css = '';

	public static function init(): void {
		add_action( 'add_meta_boxes', array( self::class, 'register_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'filter_post_data' ), 10, 2 );
		add_action( 'save_post_page', array( self::class, 'save_singular_css' ), 10, 2 );
		add_action( 'save_post_post', array( self::class, 'save_singular_css' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_front_css' ), 30 );
	}

	/**
	 * 读取 CSS（新 key 优先，否则回退旧 Page key）。
	 */
	public static function get_css( int $post_id ): string {
		$css = get_post_meta( $post_id, self::CSS_META_KEY, true );
		if ( is_string( $css ) && '' !== $css ) {
			return $css;
		}

		$legacy = get_post_meta( $post_id, self::LEGACY_CSS_META_KEY, true );
		return is_string( $legacy ) ? $legacy : '';
	}

	public static function is_feature_post( int $post_id ): bool {
		return '1' === (string) get_post_meta( $post_id, self::FEATURE_META_KEY, true );
	}

	public static function register_meta_boxes(): void {
		add_meta_box(
			'ts-singular-style',
			__( '页面 CSS', 'tailwindscore' ),
			array( self::class, 'render_css_meta_box' ),
			'page',
			'normal',
			'high'
		);

		add_meta_box(
			'ts-feature-layout',
			__( 'Feature layout', 'tailwindscore' ),
			array( self::class, 'render_feature_meta_box' ),
			'post',
			'side',
			'default'
		);

		$post_id = self::editing_post_id();
		if ( $post_id > 0 && ( self::is_feature_post( $post_id ) || '' !== self::get_css( $post_id ) ) ) {
			add_meta_box(
				'ts-singular-style',
				__( '文章 CSS', 'tailwindscore' ),
				array( self::class, 'render_css_meta_box' ),
				'post',
				'normal',
				'high'
			);
		}
	}

	public static function enqueue_admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, array( 'page', 'post' ), true ) ) {
			return;
		}

		if ( 'post' === $screen->post_type ) {
			$post_id = self::editing_post_id();
			if ( $post_id <= 0 || ( ! self::is_feature_post( $post_id ) && '' === self::get_css( $post_id ) ) ) {
				return;
			}
		}

		$settings = wp_enqueue_code_editor(
			array(
				'type'       => 'text/css',
				'codemirror' => array(
					'indentUnit'   => 2,
					'tabSize'      => 2,
					'lineNumbers'  => true,
					'lineWrapping' => true,
				),
			)
		);

		if ( ! $settings ) {
			return;
		}

		wp_add_inline_script(
			'code-editor',
			sprintf(
				'(function(){var s=%s;document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("ts_singular_css");if(el&&window.wp&&wp.codeEditor){wp.codeEditor.initialize(el,s);}});})();',
				wp_json_encode( $settings )
			),
			'after'
		);
	}

	public static function render_feature_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		$checked = self::is_feature_post( (int) $post->ID ) || '' !== self::get_css( (int) $post->ID );
		?>
		<label for="ts_feature_layout">
			<input
				type="checkbox"
				id="ts_feature_layout"
				name="ts_feature_layout"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php esc_html_e( 'Enable scoped article CSS (feature layout)', 'tailwindscore' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, an Article CSS box appears after save. Prefer selectors under .ts-content-root and var(--ts-*).', 'tailwindscore' ); ?>
		</p>
		<?php
	}

	public static function render_css_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$css = self::get_css( (int) $post->ID );
		?>
		<p class="description">
			<?php esc_html_e( '正文中的 <style>…</style> 会在保存时自动移入此栏，并于前台输出。亦可直接贴纯 CSS 或带 <style> 包裹的片段。选择器请挂在 .ts-content-root 下。', 'tailwindscore' ); ?>
		</p>
		<textarea
			id="ts_singular_css"
			name="ts_singular_css"
			class="large-text code"
			rows="12"
			style="width:100%; font-family: monospace;"
		><?php echo esc_textarea( $css ); ?></textarea>
		<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?>
			<p class="description">
				<?php esc_html_e( '当前账号无 unfiltered_html 权限：正文内的 <style> 可能被 WordPress 过滤；此栏会保留纯 CSS 规则文字。', 'tailwindscore' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param array<string, mixed> $data    Slashed post data.
	 * @param array<string, mixed> $postarr Raw post array.
	 * @return array<string, mixed>
	 */
	public static function filter_post_data( array $data, array $postarr ): array {
		$post_type = (string) ( $data['post_type'] ?? '' );
		if ( ! in_array( $post_type, array( 'page', 'post' ), true ) ) {
			return $data;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		$post_id = (int) ( $postarr['ID'] ?? 0 );
		if ( 'page' === $post_type ) {
			if ( $post_id > 0 && ! current_user_can( 'edit_page', $post_id ) ) {
				return $data;
			}
			if ( 0 === $post_id && ! current_user_can( 'edit_pages' ) ) {
				return $data;
			}
		} else {
			if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
				return $data;
			}
			if ( 0 === $post_id && ! current_user_can( 'edit_posts' ) ) {
				return $data;
			}
		}

		$content = isset( $data['post_content'] ) ? wp_unslash( (string) $data['post_content'] ) : '';
		if ( '' === $content || ! preg_match( '/<style\b/i', $content ) ) {
			return $data;
		}

		list( $cleaned, $extracted ) = Tailwindscore_Content_Block_Content::extract_styles_from_html( $content );
		self::$pending_extracted_css = $extracted;
		$data['post_content']        = wp_slash( $cleaned );

		return $data;
	}

	public static function save_singular_css( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = $post->post_type;
		if ( ! in_array( $post_type, array( 'page', 'post' ), true ) ) {
			return;
		}

		$cap_ok = 'page' === $post_type
			? current_user_can( 'edit_page', $post_id )
			: current_user_can( 'edit_post', $post_id );
		if ( ! $cap_ok ) {
			return;
		}

		$nonce_ok = isset( $_POST[ self::NONCE_FIELD ] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION );

		if ( 'post' === $post_type && $nonce_ok ) {
			$feature = isset( $_POST['ts_feature_layout'] ) && '1' === (string) $_POST['ts_feature_layout'];
			if ( $feature ) {
				update_post_meta( $post_id, self::FEATURE_META_KEY, '1' );
			} else {
				delete_post_meta( $post_id, self::FEATURE_META_KEY );
			}
		}

		$from_field = null;
		if ( $nonce_ok && isset( $_POST['ts_singular_css'] ) ) {
			$from_field = self::sanitize_css( wp_unslash( (string) $_POST['ts_singular_css'] ) );
		}

		$existing = self::get_css( $post_id );
		$primary  = null !== $from_field ? $from_field : $existing;
		$css      = Tailwindscore_Content_Block_Content::merge_css( $primary, self::$pending_extracted_css );
		self::$pending_extracted_css = '';

		if ( '' === $css ) {
			delete_post_meta( $post_id, self::CSS_META_KEY );
			delete_post_meta( $post_id, self::LEGACY_CSS_META_KEY );
			return;
		}

		update_post_meta( $post_id, self::CSS_META_KEY, $css );
		delete_post_meta( $post_id, self::LEGACY_CSS_META_KEY );

		if ( 'post' === $post_type ) {
			update_post_meta( $post_id, self::FEATURE_META_KEY, '1' );
		}
	}

	public static function enqueue_front_css(): void {
		if ( ! is_singular( array( 'page', 'post' ) ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$css = Tailwindscore_Content_Block_Content::normalize_css( self::get_css( $post_id ) );
		if ( '' === $css ) {
			return;
		}

		if ( wp_style_is( 'tailwindscore-app', 'enqueued' ) || wp_style_is( 'tailwindscore-app', 'registered' ) ) {
			wp_add_inline_style( 'tailwindscore-app', $css );
			return;
		}

		add_action(
			'wp_head',
			static function () use ( $css ): void {
				echo '<style id="ts-singular-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS from privileged editors; tags stripped for others.
			},
			20
		);
	}

	private static function sanitize_css( string $css ): string {
		if ( current_user_can( 'unfiltered_html' ) ) {
			return $css;
		}

		return wp_strip_all_tags( $css );
	}

	private static function editing_post_id(): int {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return (int) $_GET['post']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return 0;
	}
}

/**
 * @deprecated 保留类名别名，避免旧引用断裂。
 */
class_alias( Tailwindscore_Singular_Style::class, 'Tailwindscore_Page_Style' );
