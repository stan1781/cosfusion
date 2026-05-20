<?php
declare(strict_types=1);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ts-site-body' ); ?>>
<?php wp_body_open(); ?>
<?php $ts_cart_count = tailwindscore_cart_count(); ?>
<div id="page" class="ts-site">
	<header class="ts-site-header" x-data="siteShell()" @keydown.escape.window="closeAll();">
		<div class="ts-container ts-site-header__inner">
			<?php get_template_part( 'template-parts/header/site-branding' ); ?>
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
			<div class="ts-site-header__nav-inline">
				<?php
				get_template_part(
					'template-parts/navigation/primary-nav',
					null,
					array(
						'nav_id'    => 'ts-primary-nav-desktop',
						'nav_class' => 'ts-nav ts-nav--desktop',
					)
				);
				?>
			</div>
		</div>
		<div
			class="ts-site-search-modal"
			x-cloak
			x-show="searchOpen"
			x-transition.opacity
			@click.self="closeSearch()"
		>
			<button class="ts-site-search-modal__backdrop" type="button" @click="closeSearch()" aria-label="<?php esc_attr_e( 'Close search', 'tailwindscore' ); ?>"></button>
			<div
				id="ts-site-search-panel"
				class="ts-site-search-modal__dialog ts-stack"
				role="dialog"
				aria-modal="true"
				aria-labelledby="ts-site-search-title"
				@click.outside="closeSearch()"
			>
				<div class="ts-site-search-modal__header">
					<h2 id="ts-site-search-title" class="ts-site-search-modal__title"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></h2>
					<button class="ts-icon-button ts-site-search-modal__close" type="button" @click="closeSearch()" aria-label="<?php esc_attr_e( 'Close search', 'tailwindscore' ); ?>">
						<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'close' ); ?></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Close search', 'tailwindscore' ); ?></span>
					</button>
				</div>
				<?php get_search_form(); ?>
			</div>
		</div>
		<div class="ts-site-menu-drawer" x-cloak :data-open="menuOpen ? 'true' : 'false'">
			<button class="ts-site-menu-drawer__backdrop" type="button" @click="closeMenu()" aria-label="<?php esc_attr_e( 'Close menu', 'tailwindscore' ); ?>"></button>
			<div id="ts-site-menu-panel" class="ts-site-menu-drawer__panel ts-stack" role="dialog" aria-modal="true" aria-labelledby="ts-site-menu-title">
				<div class="ts-site-menu-drawer__header">
					<h2 id="ts-site-menu-title" class="ts-site-menu-drawer__title"><?php esc_html_e( 'Menu', 'tailwindscore' ); ?></h2>
					<button class="ts-icon-button ts-site-menu-drawer__close" type="button" @click="closeMenu()" aria-label="<?php esc_attr_e( 'Close menu', 'tailwindscore' ); ?>">
						<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'close' ); ?></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'tailwindscore' ); ?></span>
					</button>
				</div>
				<?php
				get_template_part(
					'template-parts/navigation/primary-nav',
					null,
					array(
						'nav_id'    => 'ts-primary-nav-mobile',
						'nav_class' => 'ts-nav ts-nav--mobile',
					)
				);
				?>
			</div>
		</div>
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
							<?php tailwindscore_render_cart_drawer(); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</header>
