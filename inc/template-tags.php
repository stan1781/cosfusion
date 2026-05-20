<?php
declare(strict_types=1);

function tailwindscore_site_name(): string {
	return get_bloginfo( 'name' );
}

function tailwindscore_has_sidebar(): bool {
	return is_active_sidebar( 'sidebar-1' ) && ! is_404();
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
		'trash'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 12h10l1-12"></path><path d="M9 7V5h6v2"></path></svg>',
	);

	return $icons[ $name ] ?? '';
}

function tailwindscore_account_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return wp_login_url( home_url( '/' ) );
}

function tailwindscore_cart_url(): string {
	if ( function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}

	return home_url( '/' );
}

function tailwindscore_cart_count(): int {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

function tailwindscore_checkout_url(): string {
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}

	return home_url( '/' );
}

function tailwindscore_cart_summary_text(): string {
	$count = tailwindscore_cart_count();

	return sprintf(
		/* translators: %d: cart item count */
		_n( '%d item ready for checkout', '%d items ready for checkout', $count, 'tailwindscore' ),
		$count
	);
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

function tailwindscore_get_cart_fragment( string $callback ): string {
	ob_start();
	$callback();

	return (string) ob_get_clean();
}

function tailwindscore_render_cart_drawer(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$cart = WC()->cart;
	?>
	<div class="ts-cart-drawer-cart<?php echo $cart->is_empty() ? '' : ' ts-cart-drawer-cart--filled'; ?>">
		<?php if ( $cart->is_empty() ) : ?>
			<div class="ts-cart-drawer-cart__empty ts-stack">
				<p><?php esc_html_e( 'Your cart is empty.', 'tailwindscore' ); ?></p>
				<a class="ts-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'tailwindscore' ); ?></a>
			</div>
		<?php else : ?>
			<div class="ts-cart-drawer-cart__body">
				<ul class="ts-cart-drawer-cart__items">
					<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
						<?php
						$product = $cart_item['data'] ?? null;

						if ( ! $product instanceof WC_Product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
							continue;
						}

						$product_name      = $product->get_name();
						$product_permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
						$product_price     = WC()->cart->get_product_price( $product );
						$product_thumbnail = $product->get_image( 'woocommerce_thumbnail' );
						$product_subtotal  = WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] );
						$max_quantity      = $product->backorders_allowed() ? '' : (string) $product->get_max_purchase_quantity();
						$remove_url        = wc_get_cart_remove_url( $cart_item_key );
						?>
						<li class="ts-cart-drawer-cart__item" data-ts-cart-item data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
							<div class="ts-cart-drawer-cart__media">
								<?php if ( $product_permalink ) : ?>
									<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_thumbnail ); ?></a>
								<?php else : ?>
									<?php echo wp_kses_post( $product_thumbnail ); ?>
								<?php endif; ?>
							</div>
							<div class="ts-cart-drawer-cart__content ts-stack">
								<div class="ts-cart-drawer-cart__row">
									<div class="ts-cart-drawer-cart__meta ts-stack">
										<?php if ( $product_permalink ) : ?>
											<a class="ts-cart-drawer-cart__name" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
										<?php else : ?>
											<span class="ts-cart-drawer-cart__name"><?php echo esc_html( $product_name ); ?></span>
										<?php endif; ?>
										<span class="ts-cart-drawer-cart__quantity">
											<?php
											printf(
												/* translators: 1: quantity 2: price */
												esc_html__( '%1$d x %2$s', 'tailwindscore' ),
												(int) $cart_item['quantity'],
												wp_strip_all_tags( $product_price )
											);
											?>
										</span>
									</div>
									<a
										class="ts-cart-drawer-cart__remove"
										href="<?php echo esc_url( $remove_url ); ?>"
										data-ts-cart-remove
										data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
										aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from your cart', 'woocommerce' ), $product_name ) ); ?>"
									>
										<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'trash' ); ?></span>
									</a>
								</div>
								<div class="ts-cart-drawer-cart__controls" data-ts-cart-quantity-controls>
									<button type="button" class="ts-cart-drawer-cart__quantity-button" data-ts-cart-quantity-step="-1" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Decrease quantity', 'tailwindscore' ); ?>">
										<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'minus' ); ?></span>
									</button>
									<label class="screen-reader-text" for="ts-cart-qty-<?php echo esc_attr( $cart_item_key ); ?>"><?php esc_html_e( 'Quantity', 'tailwindscore' ); ?></label>
									<input
										id="ts-cart-qty-<?php echo esc_attr( $cart_item_key ); ?>"
										class="ts-cart-drawer-cart__quantity-input"
										type="number"
										inputmode="numeric"
										min="0"
										<?php if ( '' !== $max_quantity ) : ?>
											max="<?php echo esc_attr( $max_quantity ); ?>"
										<?php endif; ?>
										step="1"
										value="<?php echo esc_attr( (string) $cart_item['quantity'] ); ?>"
										data-ts-cart-quantity-input
										data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
									>
									<button type="button" class="ts-cart-drawer-cart__quantity-button" data-ts-cart-quantity-step="1" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Increase quantity', 'tailwindscore' ); ?>">
										<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'plus' ); ?></span>
									</button>
								</div>
								<div class="ts-cart-drawer-cart__subtotal"><?php echo wp_kses_post( $product_subtotal ); ?></div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="ts-cart-drawer-cart__footer ts-stack">
				<div class="ts-cart-drawer-cart__totals">
					<span><?php esc_html_e( 'Subtotal', 'tailwindscore' ); ?></span>
					<strong><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></strong>
				</div>
				<a class="ts-button" href="<?php echo esc_url( tailwindscore_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'tailwindscore' ); ?></a>
				<a class="ts-cart-drawer-cart__view-cart" href="<?php echo esc_url( tailwindscore_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'tailwindscore' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
