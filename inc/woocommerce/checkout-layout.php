<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Default billing checkout field layout (Shopify-style sections).
 *
 * @return array<string, mixed>
 */
function tailwindscore_get_default_checkout_billing_layout(): array {
	return array(
		'sections' => array(
			'contact'  => array(
				'title' => __( 'Contact', 'tailwindscore' ),
				'items' => array(
					array(
						'key'   => 'billing_email',
						'width' => 'full',
					),
				),
			),
			'delivery' => array(
				'title' => __( 'Delivery', 'tailwindscore' ),
				'items' => array(
					array(
						'key'   => 'billing_country',
						'width' => 'full',
					),
					array(
						'key'   => 'billing_first_name',
						'width' => 'half',
					),
					array(
						'key'   => 'billing_last_name',
						'width' => 'half',
					),
					array(
						'key'   => 'billing_company',
						'width' => 'full',
					),
					array(
						'key'   => 'billing_address_1',
						'width' => 'full',
					),
					array(
						'key'   => 'billing_address_2',
						'width' => 'full',
					),
					array(
						'type'   => 'row',
						'width'  => 'third',
						'fields' => array( 'billing_city', 'billing_state', 'billing_postcode' ),
					),
					array(
						'key'   => 'billing_phone',
						'width' => 'full',
					),
				),
			),
		),
		'fallback' => array(
			'enabled' => true,
			'title'   => __( 'Additional information', 'tailwindscore' ),
		),
	);
}

/**
 * Default shipping checkout field layout.
 *
 * @return array<string, mixed>
 */
function tailwindscore_get_default_checkout_shipping_layout(): array {
	return array(
		'sections' => array(
			'delivery' => array(
				'title' => __( 'Shipping address', 'tailwindscore' ),
				'items' => array(
					array(
						'key'   => 'shipping_country',
						'width' => 'full',
					),
					array(
						'key'   => 'shipping_first_name',
						'width' => 'half',
					),
					array(
						'key'   => 'shipping_last_name',
						'width' => 'half',
					),
					array(
						'key'   => 'shipping_company',
						'width' => 'full',
					),
					array(
						'key'   => 'shipping_address_1',
						'width' => 'full',
					),
					array(
						'key'   => 'shipping_address_2',
						'width' => 'full',
					),
					array(
						'type'   => 'row',
						'width'  => 'third',
						'fields' => array( 'shipping_city', 'shipping_state', 'shipping_postcode' ),
					),
				),
			),
		),
		'fallback' => array(
			'enabled' => true,
			'title'   => __( 'Additional information', 'tailwindscore' ),
		),
	);
}

/**
 * @return array<string, mixed>
 */
function tailwindscore_get_checkout_billing_layout(): array {
	$layout = tailwindscore_get_default_checkout_billing_layout();

	/**
	 * Filter billing checkout field layout schema.
	 *
	 * @param array<string, mixed> $layout Layout schema.
	 */
	return (array) apply_filters( 'tailwindscore_checkout_billing_layout', $layout );
}

/**
 * @return array<string, mixed>
 */
function tailwindscore_get_checkout_shipping_layout(): array {
	$layout = tailwindscore_get_default_checkout_shipping_layout();

	/**
	 * Filter shipping checkout field layout schema.
	 *
	 * @param array<string, mixed> $layout Layout schema.
	 */
	return (array) apply_filters( 'tailwindscore_checkout_shipping_layout', $layout );
}

/**
 * Collect field keys declared in a layout schema.
 *
 * @param array<string, mixed> $layout Layout schema.
 * @return list<string>
 */
function tailwindscore_checkout_layout_collect_keys( array $layout ): array {
	$keys     = array();
	$sections = isset( $layout['sections'] ) && is_array( $layout['sections'] ) ? $layout['sections'] : array();

	foreach ( $sections as $section ) {
		if ( ! is_array( $section ) || empty( $section['items'] ) || ! is_array( $section['items'] ) ) {
			continue;
		}

		foreach ( $section['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( isset( $item['type'] ) && 'row' === $item['type'] && ! empty( $item['fields'] ) && is_array( $item['fields'] ) ) {
				foreach ( $item['fields'] as $field_key ) {
					if ( is_string( $field_key ) && '' !== $field_key ) {
						$keys[] = $field_key;
					}
				}
				continue;
			}

			if ( ! empty( $item['key'] ) && is_string( $item['key'] ) ) {
				$keys[] = $item['key'];
			}
		}
	}

	return array_values( array_unique( $keys ) );
}

/**
 * Wrapper class for checkout field grids.
 */
function tailwindscore_get_checkout_field_wrapper_class( string $group ): string {
	return 'billing' === $group
		? 'woocommerce-billing-fields__field-wrapper'
		: 'woocommerce-shipping-fields__field-wrapper';
}

/**
 * Apply layout width classes and data attribute to a checkout field.
 *
 * @param array<string, mixed> $field Field args.
 * @param string               $width full|half|third.
 * @param bool                 $is_half_first Whether this half field is first in a pair.
 */
function tailwindscore_apply_checkout_field_layout( array $field, string $width, bool $is_half_first = true ): array {
	$classes = array();

	if ( isset( $field['class'] ) ) {
		if ( is_string( $field['class'] ) ) {
			$classes = array_filter( explode( ' ', $field['class'] ) );
		} elseif ( is_array( $field['class'] ) ) {
			$classes = $field['class'];
		}
	}

	$classes[] = 'ts-checkout-field--' . $width;

	if ( 'full' === $width ) {
		$classes[] = 'checkout-grid-full';
	}

	if ( 'half' === $width ) {
		$classes[] = $is_half_first ? 'form-row-first' : 'form-row-last';
	}

	$field['class'] = array_values( array_unique( $classes ) );

	if ( ! isset( $field['custom_attributes'] ) || ! is_array( $field['custom_attributes'] ) ) {
		$field['custom_attributes'] = array();
	}

	$field['custom_attributes']['data-checkout-width'] = $width;

	return $field;
}

/**
 * Prepare billing email field label for guests (Sign in link).
 *
 * @param array<string, mixed> $field Field args.
 */
function tailwindscore_prepare_checkout_billing_email_field( array $field ): array {
	if ( is_user_logged_in() ) {
		return $field;
	}

	$email_label_text = isset( $field['label'] ) ? (string) $field['label'] : '';
	$field['label']   = sprintf(
		'<span class="checkout-email-label-text">%1$s</span><a class="checkout-email-signin-link" href="#" data-ts-login-modal-open>%2$s</a>',
		esc_html( $email_label_text ),
		esc_html__( 'Sign in', 'tailwindscore' )
	);

	if ( empty( $field['label_class'] ) ) {
		$field['label_class'] = array();
	}
	if ( is_string( $field['label_class'] ) ) {
		$field['label_class'] = array( $field['label_class'] );
	}
	$field['label_class'][] = 'checkout-email-label';

	return $field;
}

/**
 * Render a single checkout field.
 *
 * @param WC_Checkout          $checkout Checkout instance.
 * @param string               $group billing|shipping.
 * @param string               $key Field key.
 * @param array<string, mixed> $fields All fields for the group.
 * @param string               $width Layout width.
 * @param bool                 $is_half_first Half-field column position.
 */
function tailwindscore_render_checkout_layout_field(
	WC_Checkout $checkout,
	string $group,
	string $key,
	array $fields,
	string $width,
	bool $is_half_first = true
): void {
	if ( ! isset( $fields[ $key ] ) ) {
		return;
	}

	$field = $fields[ $key ];

	if ( 'billing' === $group && 'billing_email' === $key ) {
		$field = tailwindscore_prepare_checkout_billing_email_field( $field );
	}

	$field = tailwindscore_apply_checkout_field_layout( $field, $width, $is_half_first );

	/**
	 * Fires before a layout checkout field is rendered.
	 *
	 * @param string               $key Field key.
	 * @param array<string, mixed> $field Field args.
	 * @param WC_Checkout          $checkout Checkout instance.
	 * @param string               $group billing|shipping.
	 */
	do_action( 'tailwindscore_checkout_before_field', $key, $field, $checkout, $group );

	woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );

	/**
	 * Fires after a layout checkout field is rendered.
	 *
	 * @param string               $key Field key.
	 * @param array<string, mixed> $field Field args.
	 * @param WC_Checkout          $checkout Checkout instance.
	 * @param string               $group billing|shipping.
	 */
	do_action( 'tailwindscore_checkout_after_field', $key, $field, $checkout, $group );
}

/**
 * Render checkout fields from a layout schema.
 *
 * @param WC_Checkout          $checkout Checkout instance.
 * @param string               $group billing|shipping.
 * @param array<string, mixed> $layout Layout schema.
 */
function tailwindscore_render_checkout_field_group( WC_Checkout $checkout, string $group, array $layout ): void {
	$wc_fields = $checkout->get_checkout_fields( $group );

	if ( empty( $wc_fields ) || ! is_array( $wc_fields ) ) {
		return;
	}

	$layout           = tailwindscore_checkout_layout_with_fallback( $layout, $wc_fields );
	$wrapper_class    = tailwindscore_get_checkout_field_wrapper_class( $group );
	$sections         = isset( $layout['sections'] ) && is_array( $layout['sections'] ) ? $layout['sections'] : array();
	$half_toggle      = false;

	foreach ( $sections as $section_id => $section ) {
		if ( ! is_array( $section ) || empty( $section['items'] ) || ! is_array( $section['items'] ) ) {
			continue;
		}

		$section_id = is_string( $section_id ) ? $section_id : 'section';
		$title      = isset( $section['title'] ) ? (string) $section['title'] : '';

		/**
		 * Fires before a checkout layout section is rendered.
		 *
		 * @param string      $section_id Section identifier.
		 * @param string      $group billing|shipping.
		 * @param WC_Checkout $checkout Checkout instance.
		 */
		do_action( 'tailwindscore_checkout_before_section', $section_id, $group, $checkout );

		echo '<section class="ts-checkout-layout__section" data-checkout-section="' . esc_attr( $section_id ) . '">';

		if ( '' !== $title ) {
			echo '<h3 class="ts-wc-checkout__section-title">' . esc_html( $title ) . '</h3>';
		}

		echo '<div class="' . esc_attr( $wrapper_class ) . ' ts-checkout-layout__grid">';

		foreach ( $section['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( isset( $item['type'] ) && 'row' === $item['type'] ) {
				$row_fields = isset( $item['fields'] ) && is_array( $item['fields'] ) ? $item['fields'] : array();
				$row_width  = isset( $item['width'] ) ? (string) $item['width'] : 'third';
				$row_class  = 'third' === $row_width
					? 'ts-checkout-layout__row ts-checkout-layout__row--thirds checkout-grid-full'
					: 'ts-checkout-layout__row checkout-grid-full';

				echo '<div class="' . esc_attr( $row_class ) . '">';

				$row_half_toggle = false;
				foreach ( $row_fields as $row_key ) {
					if ( ! is_string( $row_key ) || ! isset( $wc_fields[ $row_key ] ) ) {
						continue;
					}

					if ( 'half' === $row_width ) {
						tailwindscore_render_checkout_layout_field(
							$checkout,
							$group,
							$row_key,
							$wc_fields,
							'half',
							! $row_half_toggle
						);
						$row_half_toggle = ! $row_half_toggle;
						continue;
					}

					tailwindscore_render_checkout_layout_field(
						$checkout,
						$group,
						$row_key,
						$wc_fields,
						'third' === $row_width ? 'third' : 'full',
						true
					);
				}

				echo '</div>';
				continue;
			}

			if ( empty( $item['key'] ) || ! is_string( $item['key'] ) ) {
				continue;
			}

			$key   = $item['key'];
			$width = isset( $item['width'] ) ? (string) $item['width'] : 'full';

			if ( ! isset( $wc_fields[ $key ] ) ) {
				continue;
			}

			$is_half_first = true;
			if ( 'half' === $width ) {
				$is_half_first = ! $half_toggle;
				$half_toggle   = ! $half_toggle;
			} else {
				$half_toggle = false;
			}

			tailwindscore_render_checkout_layout_field(
				$checkout,
				$group,
				$key,
				$wc_fields,
				$width,
				$is_half_first
			);
		}

		echo '</div>';
		echo '</section>';

		/**
		 * Fires after a checkout layout section is rendered.
		 *
		 * @param string      $section_id Section identifier.
		 * @param string      $group billing|shipping.
		 * @param WC_Checkout $checkout Checkout instance.
		 */
		do_action( 'tailwindscore_checkout_after_section', $section_id, $group, $checkout );
	}
}

/**
 * Append unmapped WC fields to a fallback section.
 *
 * @param array<string, mixed> $layout Layout schema.
 * @param array<string, mixed> $wc_fields WC checkout fields.
 * @return array<string, mixed>
 */
function tailwindscore_checkout_layout_with_fallback( array $layout, array $wc_fields ): array {
	$fallback = isset( $layout['fallback'] ) && is_array( $layout['fallback'] ) ? $layout['fallback'] : array();

	if ( empty( $fallback['enabled'] ) ) {
		return $layout;
	}

	$mapped   = tailwindscore_checkout_layout_collect_keys( $layout );
	$unmapped = array_diff( array_keys( $wc_fields ), $mapped );

	if ( empty( $unmapped ) ) {
		return $layout;
	}

	if ( ! isset( $layout['sections'] ) || ! is_array( $layout['sections'] ) ) {
		$layout['sections'] = array();
	}

	$items = array();
	foreach ( $unmapped as $key ) {
		$items[] = array(
			'key'   => $key,
			'width' => 'full',
		);
	}

	$layout['sections']['fallback'] = array(
		'title' => isset( $fallback['title'] ) ? (string) $fallback['title'] : __( 'Additional information', 'tailwindscore' ),
		'items' => $items,
	);

	return $layout;
}

/**
 * Render create-account and registration fields after billing email (guests).
 *
 * @param string               $key Field key.
 * @param array<string, mixed> $field Field args.
 * @param WC_Checkout          $checkout Checkout instance.
 * @param string               $group billing|shipping.
 */
function tailwindscore_checkout_render_billing_account_fields( string $key, array $field, WC_Checkout $checkout, string $group ): void {
	unset( $field );

	if ( 'billing' !== $group || 'billing_email' !== $key || is_user_logged_in() || ! $checkout->is_registration_enabled() ) {
		return;
	}

	$account_fields = $checkout->get_checkout_fields( 'account' );

	// Optional registration uses the Sign in link; skip the grid row when there are no account fields.
	if ( empty( $account_fields ) ) {
		do_action( 'woocommerce_before_checkout_registration_form', $checkout );
		do_action( 'woocommerce_after_checkout_registration_form', $checkout );
		return;
	}
	?>
	<div class="woocommerce-account-fields checkout-grid-full">
		<?php if ( ! $checkout->is_registration_required() ) : ?>
			<p class="form-row form-row-wide create-account checkout-grid-full screen-reader-text">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input
						class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
						id="createaccount"
						<?php
						checked(
							(
								true === $checkout->get_value( 'createaccount' ) ||
								( true === apply_filters( 'woocommerce_create_account_default_checked', false ) )
							),
							true
						);
						?>
						type="checkbox"
						name="createaccount"
						value="1"
					/>
					<span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>
		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<div class="create-account">
			<?php foreach ( $account_fields as $account_key => $account_field ) : ?>
				<?php woocommerce_form_field( $account_key, $account_field, $checkout->get_value( $account_key ) ); ?>
			<?php endforeach; ?>
			<div class="clear"></div>
		</div>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
	<?php
}
add_action( 'tailwindscore_checkout_after_field', 'tailwindscore_checkout_render_billing_account_fields', 10, 4 );
