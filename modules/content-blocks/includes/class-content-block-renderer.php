<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 内容区块 — 前台查询与 HTML 输出。
 */
final class Tailwindscore_Content_Block_Renderer {

	public static function render( string $id ): string {
		$id = trim( $id );
		if ( '' === $id ) {
			return self::render_error( __( 'Block ID is missing.', 'tailwindscore' ) );
		}

		$post = self::get_block_post( $id );
		if ( ! $post instanceof WP_Post ) {
			return self::render_error(
				sprintf(
					/* translators: %s: block id or slug */
					__( 'Content block “%s” was not found.', 'tailwindscore' ),
					$id
				)
			);
		}

		$html = (string) $post->post_content;
		$css  = get_post_meta( $post->ID, Tailwindscore_Content_Block_Cpt::CSS_META_KEY, true );
		$css  = is_string( $css ) ? Tailwindscore_Content_Block_Content::normalize_css( $css ) : '';
		$slug = (string) $post->post_name;

		$output  = '<div class="content-block content-block--' . esc_attr( sanitize_html_class( $slug, 'block' ) ) . '" data-ts-block="' . esc_attr( $slug ) . '">';
		$output .= do_shortcode( $html );
		$output .= '</div>';

		if ( '' !== $css ) {
			$output .= '<style>' . $css . '</style>';
		}

		return $output;
	}

	private static function get_block_post( string $id ): ?WP_Post {
		if ( is_numeric( $id ) ) {
			$post = get_post( (int) $id );
			if ( $post instanceof WP_Post && Tailwindscore_Content_Block_Cpt::POST_TYPE === $post->post_type && 'publish' === $post->post_status ) {
				return $post;
			}

			return null;
		}

		$post = get_page_by_path( sanitize_title( $id ), OBJECT, Tailwindscore_Content_Block_Cpt::POST_TYPE );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			return $post;
		}

		return null;
	}

	private static function render_error( string $message ): string {
		if ( ! current_user_can( 'edit_pages' ) ) {
			return '';
		}

		return '<div class="content-block content-block--error"><p>' . esc_html( $message ) . '</p></div>';
	}
}
