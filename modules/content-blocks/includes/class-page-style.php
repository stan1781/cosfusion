<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 页面正文内联 <style> 抽离：独立 meta 字段 + 前台输出。
 *
 * 与内容区块共用 Tailwindscore_Content_Block_Content 规范化逻辑，
 * 便于经典编辑器 Code 模式粘贴整段 HTML+CSS（如落地页）。
 */
final class Tailwindscore_Page_Style {

	public const CSS_META_KEY = '_ts_page_css';

	private const NONCE_ACTION = 'tailwindscore_page_style_save';
	private const NONCE_FIELD  = 'tailwindscore_page_style_nonce';

	/** @var string 保存时从正文提取、待合并进 CSS meta 的样式。 */
	private static string $pending_extracted_css = '';

	public static function init(): void {
		add_action( 'add_meta_boxes', array( self::class, 'register_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'filter_post_data' ), 10, 2 );
		add_action( 'save_post_page', array( self::class, 'save_page_css' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_front_css' ), 30 );
	}

	public static function register_meta_box(): void {
		add_meta_box(
			'ts-page-style',
			__( '页面 CSS', 'tailwindscore' ),
			array( self::class, 'render_meta_box' ),
			'page',
			'normal',
			'high'
		);
	}

	public static function enqueue_admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
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
				'(function(){var s=%s;document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("ts_page_css");if(el&&window.wp&&wp.codeEditor){wp.codeEditor.initialize(el,s);}});})();',
				wp_json_encode( $settings )
			),
			'after'
		);
	}

	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$css = get_post_meta( $post->ID, self::CSS_META_KEY, true );
		$css = is_string( $css ) ? $css : '';
		?>
		<p class="description">
			<?php esc_html_e( '正文中的 <style>…</style> 会在保存时自动移入此栏，并于前台输出。亦可直接贴纯 CSS 或带 <style> 包裹的片段。', 'tailwindscore' ); ?>
		</p>
		<textarea
			id="ts_page_css"
			name="ts_page_css"
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
		if ( 'page' !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		$post_id = (int) ( $postarr['ID'] ?? 0 );
		if ( $post_id > 0 && ! current_user_can( 'edit_page', $post_id ) ) {
			return $data;
		}
		if ( 0 === $post_id && ! current_user_can( 'edit_pages' ) ) {
			return $data;
		}

		// wp_insert_post_data 约定为 slashed；先 unslash 再抽取，写回时 slash。
		$content = isset( $data['post_content'] ) ? wp_unslash( (string) $data['post_content'] ) : '';
		if ( '' === $content || ! preg_match( '/<style\b/i', $content ) ) {
			return $data;
		}

		list( $cleaned, $extracted ) = Tailwindscore_Content_Block_Content::extract_styles_from_html( $content );
		self::$pending_extracted_css = $extracted;
		$data['post_content']        = wp_slash( $cleaned );

		return $data;
	}

	public static function save_page_css( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		$from_field = null;

		if ( isset( $_POST[ self::NONCE_FIELD ] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
			&& isset( $_POST['ts_page_css'] )
		) {
			$from_field = self::sanitize_css( wp_unslash( (string) $_POST['ts_page_css'] ) );
		}

		$existing = get_post_meta( $post_id, self::CSS_META_KEY, true );
		$existing = is_string( $existing ) ? $existing : '';

		$primary = null !== $from_field ? $from_field : $existing;
		$css     = Tailwindscore_Content_Block_Content::merge_css( $primary, self::$pending_extracted_css );
		self::$pending_extracted_css = '';

		if ( '' === $css ) {
			delete_post_meta( $post_id, self::CSS_META_KEY );
			return;
		}

		update_post_meta( $post_id, self::CSS_META_KEY, $css );
	}

	public static function enqueue_front_css(): void {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$css = get_post_meta( $post_id, self::CSS_META_KEY, true );
		$css = is_string( $css ) ? Tailwindscore_Content_Block_Content::normalize_css( $css ) : '';
		if ( '' === $css ) {
			return;
		}

		if ( wp_style_is( 'tailwindscore-app', 'enqueued' ) || wp_style_is( 'tailwindscore-app', 'registered' ) ) {
			wp_add_inline_style( 'tailwindscore-app', $css );
			return;
		}

		// 主题主样式未挂载时的兜底（极少）。
		add_action(
			'wp_head',
			static function () use ( $css ): void {
				echo '<style id="ts-page-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS stored from privileged editors; tags stripped for others.
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
}
