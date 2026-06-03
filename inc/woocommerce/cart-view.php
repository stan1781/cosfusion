<?php
declare(strict_types=1);

// Purchase-flow rendering belongs in inc/woocommerce/cart-view.php.
// Generic site helpers stay in inc/template-tags.php.

/**
 * Load a WooCommerce template part from template-parts/woocommerce/.
 *
 * Uses require() so $args is available inside the partial (WP 6.9 load_template no longer extracts $args).
 */
function tailwindscore_load_woocommerce_template_part( string $slug, array $args = array() ): void {
	$template = get_template_directory() . '/template-parts/woocommerce/' . $slug . '.php';

	if ( ! is_readable( $template ) ) {
		return;
	}

	require $template;
}

/**
 * Build cart line item meta rows (variation attributes + custom item data).
 *
 * @param array<string, mixed> $cart_item     Cart item.
 * @param string               $cart_item_key Cart item key.
 * @return array<int, array{type: string, label: string, value: string}>
 */
function tailwindscore_get_cart_line_item_meta( array $cart_item, string $cart_item_key = '' ): array {
	$meta    = array();
	$product = $cart_item['data'] ?? null;

	if ( $product instanceof WC_Product && $product->is_type( 'variation' ) && ! empty( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ) {
		foreach ( $cart_item['variation'] as $name => $value ) {
			$name  = (string) $name;
			$value = (string) $value;

			if ( '' === $value ) {
				continue;
			}

			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					$value = (string) apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $taxonomy, $product );
				}
				$label = wc_attribute_label( $taxonomy );
			} else {
				$value = (string) apply_filters( 'woocommerce_variation_option_name', $value, null, $taxonomy, $product );
				$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $product );
			}

			if ( function_exists( 'wc_is_attribute_in_product_name' ) && wc_is_attribute_in_product_name( $value, $product->get_name() ) ) {
				continue;
			}

			$meta[] = array(
				'type'  => 'variation',
				'label' => (string) $label,
				'value' => $value,
			);
		}
	}

	$item_data = apply_filters( 'woocommerce_get_item_data', array(), $cart_item );

	foreach ( $item_data as $data ) {
		if ( ! is_array( $data ) || ! empty( $data['hidden'] ) ) {
			continue;
		}

		$label = ! empty( $data['key'] ) ? (string) $data['key'] : (string) ( $data['name'] ?? '' );
		$value = ! empty( $data['display'] ) ? (string) $data['display'] : (string) ( $data['value'] ?? '' );

		if ( '' === $label || '' === $value ) {
			continue;
		}

		$meta[] = array(
			'type'  => 'custom',
			'label' => $label,
			'value' => $value,
		);
	}

	/**
	 * Filter cart line item meta rows displayed below the product name.
	 *
	 * Each row: [ 'type' => 'variation'|'custom', 'label' => string, 'value' => string ].
	 *
	 * @param array<int, array{type: string, label: string, value: string}> $meta
	 * @param array<string, mixed>                                           $cart_item
	 * @param string                                                         $cart_item_key
	 */
	return (array) apply_filters( 'tailwindscore_cart_line_item_meta', $meta, $cart_item, $cart_item_key );
}

function tailwindscore_render_cart_line_item_meta( array $cart_item, string $cart_item_key = '' ): void {
	$meta = tailwindscore_get_cart_line_item_meta( $cart_item, $cart_item_key );

	if ( empty( $meta ) ) {
		return;
	}

	tailwindscore_load_woocommerce_template_part(
		'cart-line-item-meta',
		array(
			'meta'          => $meta,
			'cart_item'     => $cart_item,
			'cart_item_key' => $cart_item_key,
		)
	);
}

function tailwindscore_render_cart_line_item( array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'cart-line-item', $args );
}

function tailwindscore_render_cart_summary( array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'cart-summary', $args );
}

function tailwindscore_render_cart_feedback( array $args = array() ): void {
	tailwindscore_load_woocommerce_template_part( 'cart-feedback', $args );
}

function tailwindscore_render_coupon_form( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'context' => 'drawer',
		)
	);

	tailwindscore_load_woocommerce_template_part( 'coupon-form', $args );
}

function tailwindscore_render_cart_drawer_content(): void {
	tailwindscore_load_woocommerce_template_part( 'cart-drawer-content' );
}

function tailwindscore_render_cart_page_content(): void {
	tailwindscore_load_woocommerce_template_part( 'cart-page-content' );
}

function tailwindscore_get_cart_fragment( string $callback ): string {
	ob_start();
	$callback();

	return (string) ob_get_clean();
}

function tailwindscore_render_cart_drawer(): void {
	tailwindscore_render_cart_drawer_content();
}
