<?php
declare(strict_types=1);
?>
<div class="ts-site-header__actions" aria-label="<?php esc_attr_e( 'Site actions', 'tailwindscore' ); ?>">
	<button
		class="ts-icon-button ts-site-header__action ts-site-header__action--search"
		type="button"
		@click="toggleSearch()"
		:aria-expanded="searchOpen.toString()"
		aria-controls="ts-site-search-panel"
		aria-label="<?php esc_attr_e( 'Open search', 'tailwindscore' ); ?>"
	>
		<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'search' ); ?></span>
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></span>
	</button>
	<a class="ts-icon-button ts-site-header__action ts-site-header__action--account" href="<?php echo esc_url( tailwindscore_account_url() ); ?>" aria-label="<?php esc_attr_e( 'My account', 'tailwindscore' ); ?>">
		<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'user' ); ?></span>
		<span class="screen-reader-text"><?php esc_html_e( 'My account', 'tailwindscore' ); ?></span>
	</a>
	<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
		<button
			class="ts-icon-button ts-site-header__action ts-site-header__action--cart"
			type="button"
			@click="toggleCart()"
			:aria-expanded="cartOpen.toString()"
			aria-controls="ts-site-cart-panel"
			aria-label="<?php esc_attr_e( 'Open cart', 'tailwindscore' ); ?>"
		>
			<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'cart' ); ?></span>
			<span class="ts-site-header__badge-slot" data-ts-cart-badge>
				<?php tailwindscore_render_cart_badge(); ?>
			</span>
			<span data-ts-cart-screen-reader>
				<?php tailwindscore_render_cart_screen_reader_text(); ?>
			</span>
		</button>
	<?php endif; ?>
	<button
		class="ts-icon-button ts-site-header__action ts-site-header__action--menu ts-site-header__toggle--menu"
		type="button"
		@click="toggleMenu()"
		:aria-expanded="menuOpen.toString()"
		aria-controls="ts-site-menu-panel"
		aria-label="<?php esc_attr_e( 'Open menu', 'tailwindscore' ); ?>"
	>
		<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'menu' ); ?></span>
		<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'tailwindscore' ); ?></span>
	</button>
</div>
