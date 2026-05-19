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
	<header class="ts-site-header" x-data="siteShell()" @keydown.escape.window="closeMenu(); closeSearch();">
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
					<a class="ts-icon-button ts-site-header__action ts-site-header__action--cart" href="<?php echo esc_url( tailwindscore_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'tailwindscore' ); ?>">
						<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'cart' ); ?></span>
						<?php if ( $ts_cart_count > 0 ) : ?>
							<span class="ts-site-header__badge" aria-hidden="true"><?php echo esc_html( (string) $ts_cart_count ); ?></span>
						<?php endif; ?>
						<span class="screen-reader-text">
							<?php
							printf(
								/* translators: %d: cart item count */
								esc_html__( 'Cart, %d items', 'tailwindscore' ),
								$ts_cart_count
							);
							?>
						</span>
					</a>
				<?php endif; ?>
				<button
					class="ts-icon-button ts-site-header__action ts-site-header__action--menu ts-site-header__toggle--menu"
					type="button"
					@click="toggleMenu()"
					:aria-expanded="menuOpen.toString()"
					aria-controls="ts-primary-nav"
					aria-label="<?php esc_attr_e( 'Open menu', 'tailwindscore' ); ?>"
				>
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'menu' ); ?></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'tailwindscore' ); ?></span>
				</button>
			</div>
			<div class="ts-site-header__nav-shell" :data-open="menuOpen ? 'true' : 'false'">
				<?php get_template_part( 'template-parts/navigation/primary-nav' ); ?>
			</div>
		</div>
		<div
			id="ts-site-search-panel"
			class="ts-container ts-site-header__search-panel"
			x-cloak
			x-show="searchOpen"
			x-transition.opacity
		>
			<?php get_search_form(); ?>
		</div>
	</header>
