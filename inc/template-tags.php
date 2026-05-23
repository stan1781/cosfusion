<?php
declare(strict_types=1);

// Purchase-flow rendering belongs in inc/woocommerce/cart-view.php.
// Generic site helpers stay in inc/template-tags.php.

function tailwindscore_site_name(): string {
	return get_bloginfo( 'name' );
}

function tailwindscore_has_sidebar(): bool {
	if ( is_404() ) {
		return false;
	}

	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return false;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return false;
	}

	if ( is_page( 'register' ) ) {
		return false;
	}

	return is_active_sidebar( 'sidebar-1' );
}

function tailwindscore_icon( string $name ): string {
	$icons = array(
		'search' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><path d="M20 20L16.65 16.65"></path></svg>',
		'user'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>',
		'cart'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.5"></circle><circle cx="18" cy="20" r="1.5"></circle><path d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h9.8a1 1 0 0 0 1-.8L21 7H7"></path></svg>',
		'menu'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h16"></path></svg>',
		'close'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12"></path><path d="M18 6l-12 12"></path></svg>',
		'plus'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
		'minus'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"></path></svg>',
		'trash'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 12h10l1-12"></path><path d="M9 7V5h6v2"></path></svg>',
		'chevron-left'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 6l-6 6 6 6"></path></svg>',
		'chevron-right' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 6l6 6-6 6"></path></svg>',
		'package'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3 3 7.5v9L12 21l9-4.5v-9L12 3z"></path><path d="M12 12 21 7.5"></path><path d="M12 12v9"></path><path d="M12 12 3 7.5"></path></svg>',
		'map-pin'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>',
		'truck'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 7h11v8H3z"></path><path d="M14 10h4l3 3v2h-7v-5z"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg>',
		'circle-check'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M8 12l2.5 2.5L16 9"></path></svg>',
		'clipboard'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5h6a2 2 0 0 1 2 2v1H7V7a2 2 0 0 1 2-2z"></path><path d="M7 8h10v11H7z"></path></svg>',
		'log-out'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"></path><path d="M15 12H3"></path><path d="M6 9l-3 3 3 3"></path></svg>',
	);

	return $icons[ $name ] ?? '';
}

function tailwindscore_account_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return wp_login_url( home_url( '/' ) );
}

function tailwindscore_search_mode(): string {
	$default_mode = function_exists( 'WC' ) ? 'product' : 'site';
	$mode         = apply_filters( 'tailwindscore_search_mode', $default_mode );

	if ( ! is_string( $mode ) ) {
		return $default_mode;
	}

	$mode = sanitize_key( $mode );

	if ( ! in_array( $mode, array( 'product', 'site', 'mixed' ), true ) ) {
		return $default_mode;
	}

	if ( 'product' === $mode && ! function_exists( 'WC' ) ) {
		return 'site';
	}

	if ( 'mixed' === $mode && ! function_exists( 'WC' ) ) {
		return 'site';
	}

	return $mode;
}

function tailwindscore_search_post_types(): array {
	$mode = tailwindscore_search_mode();

	if ( 'product' === $mode ) {
		return array( 'product' );
	}

	if ( 'mixed' === $mode ) {
		return array( 'product', 'post', 'page' );
	}

	return array( 'post', 'page' );
}

function tailwindscore_search_placeholder_text(): string {
	if ( 'product' === tailwindscore_search_mode() ) {
		return __( 'Search products', 'tailwindscore' );
	}

	return __( 'Search the site', 'tailwindscore' );
}

function tailwindscore_search_submit_text(): string {
	if ( 'product' === tailwindscore_search_mode() ) {
		return __( 'Search products', 'tailwindscore' );
	}

	return __( 'Search', 'tailwindscore' );
}

function tailwindscore_render_search_hidden_fields(): void {
	$post_types = tailwindscore_search_post_types();

	if ( 1 !== count( $post_types ) ) {
		return;
	}
	?>
	<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_types[0] ); ?>">
	<?php
}

function tailwindscore_render_cart_badge(): void {
	$count = tailwindscore_cart_count();

	if ( $count <= 0 ) {
		return;
	}
	?>
	<span class="ts-site-header__badge" aria-hidden="true"><?php echo esc_html( (string) $count ); ?></span>
	<?php
}

function tailwindscore_render_cart_screen_reader_text(): void {
	$count = tailwindscore_cart_count();

	?>
	<span class="screen-reader-text">
		<?php
		printf(
			/* translators: %d: cart item count */
			esc_html__( 'Cart, %d items', 'tailwindscore' ),
			$count
		);
		?>
	</span>
	<?php
}

/**
 * Render the sitewide dismiss/close control (icon + screen-reader label).
 *
 * @param string               $label Accessible label.
 * @param array<string, mixed> $args  Optional class and extra attributes.
 * @return void
 */
function tailwindscore_render_close_button( string $label, array $args = array() ): void {
	$classes = array( 'ts-close-button' );

	if ( ! empty( $args['class'] ) && is_string( $args['class'] ) ) {
		$classes[] = $args['class'];
	}

	$attributes = array(
		'type'       => 'button',
		'class'      => implode( ' ', $classes ),
		'aria-label' => $label,
	);

	if ( ! empty( $args['attrs'] ) && is_array( $args['attrs'] ) ) {
		foreach ( $args['attrs'] as $key => $value ) {
			if ( is_string( $key ) && ( is_string( $value ) || is_numeric( $value ) ) ) {
				$attributes[ $key ] = (string) $value;
			}
		}
	}

	echo '<button';
	foreach ( $attributes as $key => $value ) {
		printf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}
	echo '>';
	echo '<span class="ts-icon" aria-hidden="true">';
	echo tailwindscore_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</span>';
	echo '<span class="screen-reader-text">';
	echo esc_html( $label );
	echo '</span>';
	echo '</button>';
}
