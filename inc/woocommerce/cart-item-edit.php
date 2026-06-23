<?php
declare(strict_types=1);

/**
 * Cart drawer line-item edit (variable products).
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a cart line item can be edited in the drawer modal.
 *
 * @param array<string, mixed> $cart_item Cart item.
 * @param string               $key       Cart item key.
 */
function tailwindscore_cart_item_is_editable( array $cart_item, string $key = '' ): bool {
	$product = $cart_item['data'] ?? null;

	if ( ! $product instanceof WC_Product || ! $product->exists() ) {
		return false;
	}

	$editable = $product->is_type( 'variation' );

	/**
	 * Filter whether a cart line item shows the edit control.
	 *
	 * @param bool                 $editable  Default editability.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @param string               $key       Cart item key.
	 */
	return (bool) apply_filters( 'tailwindscore_cart_item_is_editable', $editable, $cart_item, $key );
}

/**
 * Extract cart_item_data to preserve when replacing a variation line.
 *
 * @param array<string, mixed> $item Cart item.
 * @return array<string, mixed>
 */
function tailwindscore_cart_item_edit_preserve_data( array $item ): array {
	$excluded = array(
		'key',
		'product_id',
		'variation_id',
		'variation',
		'quantity',
		'data',
		'data_hash',
		'line_tax_data',
		'line_subtotal',
		'line_subtotal_tax',
		'line_total',
		'line_tax',
	);

	$custom = array();

	foreach ( $item as $k => $v ) {
		$key = (string) $k;
		if ( in_array( $key, $excluded, true ) ) {
			continue;
		}
		$custom[ $key ] = $v;
	}

	/**
	 * Filter cart item data preserved when editing a line item variation.
	 *
	 * @param array<string, mixed> $custom Preserved data.
	 * @param array<string, mixed> $item   Full cart item.
	 */
	return (array) apply_filters( 'tailwindscore_cart_item_edit_preserve_data', $custom, $item );
}

/**
 * Find a matching variation ID for attribute selection.
 *
 * @param WC_Product_Variable  $product Variable parent product.
 * @param array<string,string> $desired Attribute key => value.
 */
function tailwindscore_find_matching_variation_id( WC_Product_Variable $product, array $desired ): int {
	if ( class_exists( 'WC_Data_Store' ) ) {
		$store = WC_Data_Store::load( 'product' );
		if ( $store && method_exists( $store, 'find_matching_product_variation' ) ) {
			$match = (int) $store->find_matching_product_variation( $product, $desired );
			if ( $match > 0 ) {
				return $match;
			}
		}
	}

	foreach ( $product->get_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation instanceof WC_Product_Variation || ! $variation->exists() ) {
			continue;
		}

		$attrs = $variation->get_attributes();
		$ok    = true;

		foreach ( $desired as $attr_key => $wanted ) {
			$wanted = (string) $wanted;
			if ( '' === $wanted ) {
				continue;
			}

			$normalized_key = tailwindscore_normalize_variation_attr_key( (string) $attr_key );
			$attr_slug      = str_starts_with( $normalized_key, 'attribute_' ) ? substr( $normalized_key, 10 ) : $normalized_key;

			$variation_value = '';
			if ( isset( $attrs[ $attr_key ] ) ) {
				$variation_value = (string) $attrs[ $attr_key ];
			} elseif ( isset( $attrs[ $normalized_key ] ) ) {
				$variation_value = (string) $attrs[ $normalized_key ];
			} elseif ( isset( $attrs[ $attr_slug ] ) ) {
				$variation_value = (string) $attrs[ $attr_slug ];
			}

			if ( '' === $variation_value ) {
				continue;
			}

			if ( ! tailwindscore_variation_value_matches( $variation_value, $wanted, (string) $attr_key ) ) {
				$ok = false;
				break;
			}
		}

		if ( $ok ) {
			return (int) $child_id;
		}
	}

	return 0;
}

/**
 * Normalize a variation attribute key to attribute_* form.
 */
function tailwindscore_normalize_variation_attr_key( string $key ): string {
	$key = (string) $key;

	if ( str_starts_with( $key, 'attribute_' ) ) {
		return $key;
	}

	return 'attribute_' . $key;
}

/**
 * Whether two variation attribute values refer to the same option.
 */
function tailwindscore_variation_value_matches( string $stored, string $desired, string $attr_key ): bool {
	$stored  = wc_clean( $stored );
	$desired = wc_clean( $desired );

	if ( $stored === $desired ) {
		return true;
	}

	$normalized_key = tailwindscore_normalize_variation_attr_key( $attr_key );
	$taxonomy       = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $normalized_key ) ) );

	$forms = static function ( string $value ) use ( $taxonomy ): array {
		if ( '' === $value ) {
			return array();
		}

		$candidates = array( strtolower( $value ), strtolower( sanitize_title( $value ) ) );

		if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) ) {
			$term = get_term_by( 'slug', $value, $taxonomy );
			if ( ! $term instanceof WP_Term ) {
				$term = get_term_by( 'name', $value, $taxonomy );
			}

			if ( $term instanceof WP_Term ) {
				$candidates[] = strtolower( (string) $term->slug );
				$candidates[] = strtolower( (string) $term->name );
			}
		}

		return array_values( array_unique( array_filter( $candidates, 'strlen' ) ) );
	};

	return ! empty( array_intersect( $forms( $stored ), $forms( $desired ) ) );
}

/**
 * Whether selected variation attributes differ from what is stored on the cart line.
 *
 * @param array<string, mixed> $item    Cart line item.
 * @param array<string, string> $desired Desired attributes.
 */
function tailwindscore_cart_item_variation_changed( array $item, array $desired ): bool {
	$stored_raw = (array) ( $item['variation'] ?? array() );
	$stored     = array();

	foreach ( $stored_raw as $key => $value ) {
		$stored[ tailwindscore_normalize_variation_attr_key( (string) $key ) ] = (string) $value;
	}

	$wanted = array();
	foreach ( $desired as $key => $value ) {
		$wanted[ tailwindscore_normalize_variation_attr_key( (string) $key ) ] = (string) $value;
	}

	$keys = array_unique( array_merge( array_keys( $stored ), array_keys( $wanted ) ) );

	foreach ( $keys as $key ) {
		$current = $stored[ $key ] ?? '';
		$next    = $wanted[ $key ] ?? '';

		if ( ! tailwindscore_variation_value_matches( $current, $next, $key ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Normalize cart item variation array for template preselection.
 *
 * @param array<string, mixed> $item Cart item.
 * @return array<string, string>
 */
function tailwindscore_cart_item_edit_selected_attributes( array $item ): array {
	$selected = array();

	if ( empty( $item['variation'] ) || ! is_array( $item['variation'] ) ) {
		return $selected;
	}

	foreach ( $item['variation'] as $k => $v ) {
		$key = (string) $k;
		if ( '' === $key ) {
			continue;
		}

		$selected[ tailwindscore_normalize_variation_attr_key( $key ) ] = (string) $v;
	}

	return $selected;
}

/**
 * Read JSON or POST payload for cart item edit requests.
 *
 * @return array<string, mixed>
 */
function tailwindscore_cart_item_edit_read_payload(): array {
	$raw = json_decode( (string) file_get_contents( 'php://input' ), true );
	if ( is_array( $raw ) && ! empty( $raw ) ) {
		return $raw;
	}

	$payload = array();
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	foreach ( $_POST as $key => $value ) {
		$payload[ (string) $key ] = is_string( $value ) ? wp_unslash( $value ) : $value;
	}

	return $payload;
}

/**
 * Verify cart item edit nonce from payload.
 *
 * @param array<string, mixed> $payload Request payload.
 */
function tailwindscore_cart_item_edit_verify_nonce( array $payload ): bool {
	$nonce = isset( $payload['nonce'] ) ? sanitize_text_field( (string) $payload['nonce'] ) : '';

	return '' !== $nonce && wp_verify_nonce( $nonce, 'tailwindscore_cart_item_edit' );
}

/**
 * WC-Ajax: load edit form HTML for a cart line item.
 */
function tailwindscore_ajax_get_cart_item_edit_form(): void {
	if ( ! defined( 'WC_DOING_AJAX' ) ) {
		define( 'WC_DOING_AJAX', true );
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Invalid cart.', 'tailwindscore' ),
			)
		);
	}

	$payload = tailwindscore_cart_item_edit_read_payload();

	if ( ! tailwindscore_cart_item_edit_verify_nonce( $payload ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Security check failed.', 'tailwindscore' ),
			)
		);
	}

	$key = isset( $payload['cart_item_key'] ) ? sanitize_text_field( (string) $payload['cart_item_key'] ) : '';
	if ( '' === $key ) {
		$key = isset( $payload['key'] ) ? sanitize_text_field( (string) $payload['key'] ) : '';
	}

	if ( '' === $key ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Item not found.', 'tailwindscore' ),
			)
		);
	}

	$cart = WC()->cart->get_cart();
	if ( empty( $cart[ $key ] ) || ! is_array( $cart[ $key ] ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Item not found.', 'tailwindscore' ),
			)
		);
	}

	$cart_item = $cart[ $key ];

	if ( ! tailwindscore_cart_item_is_editable( $cart_item, $key ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'This item cannot be edited.', 'tailwindscore' ),
			)
		);
	}

	$product_id     = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
	$parent_product = $product_id > 0 ? wc_get_product( $product_id ) : false;

	if ( ! $parent_product instanceof WC_Product_Variable ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'This item cannot be edited.', 'tailwindscore' ),
			)
		);
	}

	ob_start();
	tailwindscore_load_woocommerce_template_part(
		'cart-item-edit-form',
		array(
			'cart_item_key' => $key,
			'cart_item'     => $cart_item,
			'product'       => $parent_product,
		)
	);
	$html = (string) ob_get_clean();

	if ( '' === trim( $html ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Failed to load item options.', 'tailwindscore' ),
			)
		);
	}

	wp_send_json_success(
		array(
			'html' => $html,
		)
	);
}

/**
 * WC-Ajax: save cart line item variation changes.
 */
function tailwindscore_ajax_update_cart_item_edit(): void {
	if ( ! defined( 'WC_DOING_AJAX' ) ) {
		define( 'WC_DOING_AJAX', true );
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Invalid cart.', 'tailwindscore' ),
			)
		);
	}

	$payload = tailwindscore_cart_item_edit_read_payload();

	if ( ! tailwindscore_cart_item_edit_verify_nonce( $payload ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Security check failed.', 'tailwindscore' ),
			)
		);
	}

	$key = isset( $payload['key'] ) ? sanitize_text_field( (string) $payload['key'] ) : '';
	if ( '' === $key ) {
		$key = isset( $payload['cart_item_key'] ) ? sanitize_text_field( (string) $payload['cart_item_key'] ) : '';
	}

	if ( '' === $key ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Item not found.', 'tailwindscore' ),
			)
		);
	}

	$cart = WC()->cart;

	if ( empty( $cart->cart_contents[ $key ] ) || ! is_array( $cart->cart_contents[ $key ] ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Item not found.', 'tailwindscore' ),
			)
		);
	}

	$item         = $cart->cart_contents[ $key ];
	$product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
	$variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
	$qty          = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;

	if ( $qty < 1 ) {
		$qty = 1;
	}

	$product = $product_id > 0 ? wc_get_product( $product_id ) : null;

	if ( ! $product instanceof WC_Product_Variable ) {
		wp_send_json_success(
			array(
				'message' => 'OK',
			)
		);
	}

	$posted_variation = isset( $payload['variation'] ) && is_array( $payload['variation'] ) ? $payload['variation'] : array();
	$desired          = array();

	foreach ( $posted_variation as $k => $v ) {
		$attr_key = sanitize_text_field( (string) $k );
		if ( '' === $attr_key ) {
			continue;
		}

		if ( ! str_starts_with( $attr_key, 'attribute_' ) ) {
			$attr_key = 'attribute_' . $attr_key;
		}

		$desired[ $attr_key ] = sanitize_text_field( (string) $v );
	}

	if ( ! empty( $item['variation'] ) && is_array( $item['variation'] ) ) {
		foreach ( $item['variation'] as $k => $v ) {
			$stored_key = (string) $k;
			if ( '' === $stored_key ) {
				continue;
			}

			$prefixed_key = str_starts_with( $stored_key, 'attribute_' ) ? $stored_key : 'attribute_' . $stored_key;

			if ( ! array_key_exists( $prefixed_key, $desired ) ) {
				$desired[ $prefixed_key ] = (string) $v;
			}
		}
	}

	$match = tailwindscore_find_matching_variation_id( $product, $desired );

	if ( $match < 1 ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'No matching variation found.', 'tailwindscore' ),
			)
		);
	}

	if ( $match === $variation_id && ! tailwindscore_cart_item_variation_changed( $item, $desired ) ) {
		wp_send_json_success(
			array(
				'message' => 'OK',
				'key'     => $key,
			)
		);
	}

	$preserve_data = tailwindscore_cart_item_edit_preserve_data( $item );
	$new_key       = $cart->add_to_cart( $product_id, $qty, $match, $desired, $preserve_data );

	if ( ! $new_key ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Failed to update variation.', 'tailwindscore' ),
			)
		);
	}

	$cart->remove_cart_item( $key );
	$cart->calculate_totals();
	$cart->set_session();

	/**
	 * Fires after a cart line item was edited via the drawer modal.
	 *
	 * @param string               $new_key New cart item key.
	 * @param string               $old_key Previous cart item key.
	 * @param array<string, mixed> $item    Original cart item before replacement.
	 */
	do_action( 'tailwindscore_cart_item_edit_saved', $new_key, $key, $item );

	wp_send_json_success(
		array(
			'message' => 'OK',
			'key'     => $new_key,
		)
	);
}

/**
 * Render cart item edit modal shell in footer.
 */
function tailwindscore_render_cart_item_edit_modal(): void {
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		return;
	}

	get_template_part( 'template-parts/woocommerce/cart-item-edit-modal' );
}

if ( class_exists( 'WooCommerce' ) ) {
	add_action( 'wc_ajax_tailwindscore_get_cart_item_edit_form', 'tailwindscore_ajax_get_cart_item_edit_form' );
	add_action( 'wc_ajax_tailwindscore_update_cart_item_edit', 'tailwindscore_ajax_update_cart_item_edit' );
	add_action( 'wp_footer', 'tailwindscore_render_cart_item_edit_modal', 55 );
}
