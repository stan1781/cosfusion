<?php
declare(strict_types=1);

$is_checkout = function_exists( 'is_checkout' ) && is_checkout();
$is_cart     = function_exists( 'is_cart' ) && is_cart();
?>
<div class="ts-site-header__actions" aria-label="<?php esc_attr_e( 'Site actions', 'tailwindscore' ); ?>">
	<?php if ( $is_checkout ) : ?>
		<?php
		$back_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
		?>
		<a
			class="ts-icon-button ts-site-header__action ts-site-header__action--back"
			href="<?php echo esc_url( $back_url ); ?>"
			aria-label="<?php esc_attr_e( 'Return to cart', 'tailwindscore' ); ?>"
		>
			<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'cart' ); ?></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Return to cart', 'tailwindscore' ); ?></span>
		</a>
	<?php else : ?>
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
		<?php if ( ! ( function_exists( 'is_account_page' ) && is_account_page() ) ) : ?>
			<?php if ( is_user_logged_in() ) : ?>
				<div class="ts-site-header__account" :class="{ 'is-open': accountOpen }">
					<button
						class="ts-icon-button ts-site-header__action ts-site-header__action--account"
						type="button"
						@click="toggleAccount()"
						:aria-expanded="accountOpen.toString()"
						aria-controls="ts-account-menu-panel"
						aria-haspopup="true"
						aria-label="<?php esc_attr_e( 'My account', 'tailwindscore' ); ?>"
					>
						<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'user' ); ?></span>
						<span class="screen-reader-text"><?php esc_html_e( 'My account', 'tailwindscore' ); ?></span>
					</button>
					<?php get_template_part( 'template-parts/header/account-menu-dropdown' ); ?>
				</div>
			<?php else : ?>
				<button
					class="ts-icon-button ts-site-header__action ts-site-header__action--account"
					type="button"
					data-ts-login-modal-open
					aria-controls="ts-account-login-modal"
					aria-label="<?php esc_attr_e( 'My account', 'tailwindscore' ); ?>"
				>
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'user' ); ?></span>
					<span class="screen-reader-text"><?php esc_html_e( 'My account', 'tailwindscore' ); ?></span>
				</button>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( function_exists( 'wc_get_cart_url' ) && ! $is_cart ) : ?>
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
