<?php
declare(strict_types=1);

/**
 * 结账页 Terms / Privacy：原生 dialog 展示页面全文，替代跳转与 Woo 内联条款。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 结账上下文（表单页，不含 order-received）。
 */
function tailwindscore_is_checkout_legal_modals_context(): bool {
	return function_exists( 'is_checkout' )
		&& is_checkout()
		&& ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) );
}

/**
 * @param WP_Post $page 条款或隐私页。
 * @param string  $fallback 标题为空时的回退文案。
 */
function tailwindscore_get_checkout_legal_page_title( WP_Post $page, string $fallback ): string {
	$title = trim( get_the_title( $page ) );

	return '' !== $title ? $title : $fallback;
}

/**
 * @param WP_Post|null $page 页面。
 */
function tailwindscore_checkout_legal_page_is_renderable( ?WP_Post $page ): bool {
	if ( ! $page instanceof WP_Post ) {
		return false;
	}

	if ( 'publish' !== $page->post_status || '' === trim( (string) $page->post_content ) ) {
		return false;
	}

	if ( has_shortcode( $page->post_content, 'woocommerce_checkout' ) ) {
		return false;
	}

	return true;
}

/**
 * 生成触发 modal 的链接 HTML。
 *
 * @param string $url       无 JS 时的真实页面 URL。
 * @param string $label     链接文案。
 * @param string $class     链接 class。
 * @param string $dialog_id Dialog 元素 id。
 */
function tailwindscore_get_checkout_legal_modal_link(
	string $url,
	string $label,
	string $class,
	string $dialog_id
): string {
	return sprintf(
		'<a href="%1$s" class="%2$s" data-ts-modal-open="%3$s" role="button">%4$s</a>',
		esc_url( $url ),
		esc_attr( $class ),
		esc_attr( $dialog_id ),
		esc_html( $label )
	);
}

/**
 * 允许条款 / 隐私链接上的 data-ts-modal-open（wp_kses_post 默认会剥掉）。
 *
 * @param array<string, array<string, bool>> $tags 允许的标签。
 * @param string                             $context 上下文。
 * @return array<string, array<string, bool>>
 */
function tailwindscore_checkout_legal_modals_kses_allowed_html( array $tags, string $context ): array {
	if ( 'post' !== $context || ! isset( $tags['a'] ) || ! is_array( $tags['a'] ) ) {
		return $tags;
	}

	$tags['a']['data-ts-modal-open'] = true;
	$tags['a']['role']               = true;

	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'tailwindscore_checkout_legal_modals_kses_allowed_html', 10, 2 );

/**
 * 将 [terms] 换成 modal 触发链接（仅结账）。
 *
 * @param string $text Checkbox 文案模板。
 */
function tailwindscore_checkout_terms_checkbox_text( string $text ): string {
	if ( ! tailwindscore_is_checkout_legal_modals_context() || ! str_contains( $text, '[terms]' ) ) {
		return $text;
	}

	if ( ! function_exists( 'wc_terms_and_conditions_page_id' ) ) {
		return $text;
	}

	$terms_page_id = wc_terms_and_conditions_page_id();
	$page          = $terms_page_id ? get_post( $terms_page_id ) : null;

	if ( ! tailwindscore_checkout_legal_page_is_renderable( $page instanceof WP_Post ? $page : null ) ) {
		return $text;
	}

	/** @var WP_Post $page */
	$url   = get_permalink( $page );
	$label = tailwindscore_get_checkout_legal_page_title(
		$page,
		__( 'terms and conditions', 'woocommerce' )
	);

	if ( ! is_string( $url ) || '' === $url ) {
		return $text;
	}

	$link = tailwindscore_get_checkout_legal_modal_link(
		$url,
		$label,
		'woocommerce-terms-and-conditions-link',
		'ts-checkout-terms-modal'
	);

	return str_replace( '[terms]', $link, $text );
}
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'tailwindscore_checkout_terms_checkbox_text', 20 );

/**
 * 结账隐私文案：预先替换 [privacy_policy]，避免 Woo 生成 target=_blank 外链。
 *
 * @param string $text 隐私说明模板。
 * @param string $type checkout|registration|''.
 */
function tailwindscore_checkout_privacy_policy_text( string $text, string $type ): string {
	if ( 'checkout' !== $type || ! tailwindscore_is_checkout_legal_modals_context() ) {
		return $text;
	}

	if ( ! str_contains( $text, '[privacy_policy]' ) || ! function_exists( 'wc_privacy_policy_page_id' ) ) {
		return $text;
	}

	$privacy_page_id = wc_privacy_policy_page_id();
	$page            = $privacy_page_id ? get_post( $privacy_page_id ) : null;

	if ( ! tailwindscore_checkout_legal_page_is_renderable( $page instanceof WP_Post ? $page : null ) ) {
		return $text;
	}

	/** @var WP_Post $page */
	$url   = get_permalink( $page );
	$label = tailwindscore_get_checkout_legal_page_title(
		$page,
		__( 'privacy policy', 'woocommerce' )
	);

	if ( ! is_string( $url ) || '' === $url ) {
		return $text;
	}

	$link = tailwindscore_get_checkout_legal_modal_link(
		$url,
		$label,
		'woocommerce-privacy-policy-link',
		'ts-checkout-privacy-modal'
	);

	return str_replace( '[privacy_policy]', $link, $text );
}
add_filter( 'woocommerce_get_privacy_policy_text', 'tailwindscore_checkout_privacy_policy_text', 20, 2 );

/**
 * 渲染单个法律文档 dialog。
 *
 * @param WP_Post $page           页面。
 * @param string  $dialog_id      Dialog id。
 * @param string  $title_fallback 标题回退。
 */
function tailwindscore_render_checkout_legal_modal( WP_Post $page, string $dialog_id, string $title_fallback ): void {
	if ( ! class_exists( '\Automattic\WooCommerce\Internal\Utilities\HtmlSanitizer' ) || ! function_exists( 'wc_get_container' ) ) {
		return;
	}

	$sanitizer = wc_get_container()->get( \Automattic\WooCommerce\Internal\Utilities\HtmlSanitizer::class );
	$title     = tailwindscore_get_checkout_legal_page_title( $page, $title_fallback );
	$title_id  = $dialog_id . '-title';
	?>
	<dialog
		class="checkout-legal-modal"
		id="<?php echo esc_attr( $dialog_id ); ?>"
		aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
	>
		<div class="checkout-legal-modal__panel">
			<header class="checkout-legal-modal__header">
				<h2 class="checkout-legal-modal__title" id="<?php echo esc_attr( $title_id ); ?>">
					<?php echo esc_html( $title ); ?>
				</h2>
				<?php
				tailwindscore_render_close_button(
					__( 'Close', 'tailwindscore' ),
					array(
						'attrs' => array(
							'data-ts-modal-close' => '',
						),
					)
				);
				?>
			</header>
			<div class="checkout-legal-modal__body entry__content">
				<?php echo wc_format_content( $sanitizer->styled_post_content( $page->post_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<footer class="checkout-legal-modal__footer">
				<button type="button" class="btn btn-primary" data-ts-modal-close>
					<?php esc_html_e( 'Got it', 'tailwindscore' ); ?>
				</button>
			</footer>
		</div>
	</dialog>
	<?php
}

/**
 * 输出 Terms / Privacy dialog（放在结账表单外，避免 #payment fragment 刷新丢失）。
 */
function tailwindscore_render_checkout_legal_modals(): void {
	if ( ! tailwindscore_is_checkout_legal_modals_context() ) {
		return;
	}

	static $rendered = false;

	if ( $rendered ) {
		return;
	}

	$rendered = true;

	if ( function_exists( 'wc_terms_and_conditions_page_id' ) ) {
		$terms_page_id = wc_terms_and_conditions_page_id();
		$terms_page    = $terms_page_id ? get_post( $terms_page_id ) : null;

		if ( tailwindscore_checkout_legal_page_is_renderable( $terms_page instanceof WP_Post ? $terms_page : null ) ) {
			/** @var WP_Post $terms_page */
			tailwindscore_render_checkout_legal_modal(
				$terms_page,
				'ts-checkout-terms-modal',
				__( 'terms and conditions', 'woocommerce' )
			);
		}
	}

	if ( function_exists( 'wc_privacy_policy_page_id' ) ) {
		$privacy_page_id = wc_privacy_policy_page_id();
		$privacy_page    = $privacy_page_id ? get_post( $privacy_page_id ) : null;

		if ( tailwindscore_checkout_legal_page_is_renderable( $privacy_page instanceof WP_Post ? $privacy_page : null ) ) {
			/** @var WP_Post $privacy_page */
			tailwindscore_render_checkout_legal_modal(
				$privacy_page,
				'ts-checkout-privacy-modal',
				__( 'privacy policy', 'woocommerce' )
			);
		}
	}
}

/**
 * 始终卸掉 Woo 内联条款（含 wc-ajax update_order_review：此时 wp 阶段 is_checkout 仍为 false）。
 */
function tailwindscore_setup_checkout_legal_modals(): void {
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
}
add_action( 'init', 'tailwindscore_setup_checkout_legal_modals', 20 );

add_action( 'woocommerce_after_checkout_form', 'tailwindscore_render_checkout_legal_modals', 20 );
