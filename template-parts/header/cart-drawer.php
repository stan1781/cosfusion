<?php
declare(strict_types=1);
?>
<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
	<div class="ts-site-cart-drawer" x-cloak :data-open="cartOpen ? 'true' : 'false'">
		<button class="ts-site-cart-drawer__backdrop" type="button" @click="closeCart()" aria-label="<?php esc_attr_e( 'Close cart', 'tailwindscore' ); ?>"></button>
		<div id="ts-site-cart-panel" class="ts-site-cart-drawer__panel ts-stack" role="dialog" aria-modal="true" aria-labelledby="ts-site-cart-title">
			<div class="ts-site-cart-drawer__header">
				<div class="ts-site-cart-drawer__heading ts-stack">
					<h2 id="ts-site-cart-title" class="ts-site-cart-drawer__title"><?php esc_html_e( 'Cart', 'tailwindscore' ); ?></h2>
					<p class="ts-site-cart-drawer__summary" data-ts-cart-summary>
						<?php echo esc_html( tailwindscore_cart_summary_text() ); ?>
					</p>
				</div>
				<button class="ts-icon-button ts-site-cart-drawer__close" type="button" @click="closeCart()" aria-label="<?php esc_attr_e( 'Close cart', 'tailwindscore' ); ?>">
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'close' ); ?></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Close cart', 'tailwindscore' ); ?></span>
				</button>
			</div>
			<div
				data-ts-cart-drawer-content
				data-ts-cart-config
				data-store-api-url="<?php echo esc_url( rest_url( 'wc/store/v1/' ) ); ?>"
				data-store-api-nonce="<?php echo esc_attr( wp_create_nonce( 'wc_store_api' ) ); ?>"
				data-cart-url="<?php echo esc_url( tailwindscore_cart_url() ); ?>"
				data-checkout-url="<?php echo esc_url( tailwindscore_checkout_url() ); ?>"
				data-shop-url="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"
			>
				<div data-ts-cart-drawer-inner>
					<?php tailwindscore_render_cart_drawer_content(); ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
